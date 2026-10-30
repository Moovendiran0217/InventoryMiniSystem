<?php

namespace App\Services;

use App\Jobs\SendOrderConfirmation;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateOrder
{
    /**
     * @param array{
     *     customer: array{name: string, email: string},
     *     items: array<int, array{product_id: int, quantity: int}>
     * } $data
     */
    public function execute(array $data): Order
    {
        $order = DB::transaction(function () use ($data): Order {
            $productIds = array_map(
                static fn (array $item): int => (int) $item['product_id'],
                $data['items'],
            );
            sort($productIds);

            $products = Product::query()
                ->whereIn('id', $productIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            if ($products->count() !== count($productIds)) {
                throw ValidationException::withMessages([
                    'items' => 'One or more selected products are no longer available.',
                ]);
            }

            $preparedItems = [];
            $subtotalCents = 0;
            $taxTotalCents = 0;

            foreach ($data['items'] as $index => $item) {
                $product = $products->get((int) $item['product_id']);
                $quantity = (int) $item['quantity'];

                if ($product->stock_on_hand < $quantity) {
                    throw ValidationException::withMessages([
                        "items.{$index}.quantity" => "Insufficient stock for {$product->name}; {$product->stock_on_hand} available.",
                    ]);
                }

                $unitPriceCents = self::toCents($product->unit_price);
                $lineSubtotalCents = $unitPriceCents * $quantity;
                $taxBasisPoints = (int) round((float) $product->tax_percentage * 100);
                $lineTaxCents = intdiv(($lineSubtotalCents * $taxBasisPoints) + 5000, 10000);

                $preparedItems[] = [
                    'product' => $product,
                    'quantity' => $quantity,
                    'unit_price' => self::formatCents($unitPriceCents),
                    'tax_percentage' => $product->tax_percentage,
                    'line_subtotal' => self::formatCents($lineSubtotalCents),
                    'line_tax' => self::formatCents($lineTaxCents),
                    'line_total' => self::formatCents($lineSubtotalCents + $lineTaxCents),
                ];

                $subtotalCents += $lineSubtotalCents;
                $taxTotalCents += $lineTaxCents;
            }

            $customer = Customer::query()->firstOrCreate(
                ['email' => mb_strtolower(trim($data['customer']['email']))],
                ['name' => trim($data['customer']['name'])],
            );

            $order = $customer->orders()->create([
                'subtotal' => self::formatCents($subtotalCents),
                'tax_total' => self::formatCents($taxTotalCents),
                'grand_total' => self::formatCents($subtotalCents + $taxTotalCents),
            ]);

            foreach ($preparedItems as $item) {
                $product = $item['product'];
                $quantity = $item['quantity'];

                $updatedRows = Product::query()
                    ->whereKey($product->id)
                    ->where('stock_on_hand', '>=', $quantity)
                    ->decrement('stock_on_hand', $quantity);

                if ($updatedRows !== 1) {
                    throw ValidationException::withMessages([
                        'items' => "Insufficient stock for {$product->name}. Please refresh the catalog and try again.",
                    ]);
                }

                $order->items()->create([
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                    'unit_price' => $item['unit_price'],
                    'tax_percentage' => $item['tax_percentage'],
                    'line_subtotal' => $item['line_subtotal'],
                    'line_tax' => $item['line_tax'],
                    'line_total' => $item['line_total'],
                ]);
            }

            return $order->load(['customer', 'items.product']);
        }, attempts: 3);

        SendOrderConfirmation::dispatch($order);

        return $order;
    }

    private static function toCents(string|float|int $amount): int
    {
        return (int) round((float) $amount * 100);
    }

    private static function formatCents(int $amount): string
    {
        return sprintf('%d.%02d', intdiv($amount, 100), $amount % 100);
    }
}
