<?php

namespace Tests\Feature;

use App\Jobs\SendOrderConfirmation;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class OrderCreationTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_an_order_calculates_tax_and_deducts_stock(): void
    {
        Queue::fake();
        $product = Product::factory()->create([
            'unit_price' => '10.00',
            'tax_percentage' => '10.00',
            'stock_on_hand' => 5,
        ]);

        $response = $this->postJson('/api/orders', [
            'customer' => ['name' => 'Alex Buyer', 'email' => 'ALEX@example.com'],
            'items' => [['product_id' => $product->id, 'quantity' => 2]],
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.customer.email', 'alex@example.com')
            ->assertJsonPath('data.subtotal', '20.00')
            ->assertJsonPath('data.tax_total', '2.00')
            ->assertJsonPath('data.grand_total', '22.00')
            ->assertJsonPath('data.items.0.quantity', 2);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'stock_on_hand' => 3,
        ]);
        $this->assertDatabaseHas('order_items', [
            'product_id' => $product->id,
            'quantity' => 2,
            'unit_price' => '10.00',
            'tax_percentage' => '10.00',
        ]);

        Queue::assertPushed(SendOrderConfirmation::class, fn (SendOrderConfirmation $job): bool => $job->order->id === $response->json('data.id'));
    }

    public function test_it_rejects_an_order_when_stock_is_insufficient(): void
    {
        Queue::fake();
        $product = Product::factory()->create(['stock_on_hand' => 1]);

        $this->postJson('/api/orders', [
            'customer' => ['name' => 'Alex Buyer', 'email' => 'alex@example.com'],
            'items' => [['product_id' => $product->id, 'quantity' => 2]],
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('items.0.quantity');

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'stock_on_hand' => 1,
        ]);
        Queue::assertNothingPushed();
    }

    public function test_it_does_not_partially_deduct_stock_if_any_order_line_is_unavailable(): void
    {
        Queue::fake();
        $availableProduct = Product::factory()->create(['stock_on_hand' => 10]);
        $unavailableProduct = Product::factory()->create(['stock_on_hand' => 0]);

        $this->postJson('/api/orders', [
            'customer' => ['name' => 'Alex Buyer', 'email' => 'alex@example.com'],
            'items' => [
                ['product_id' => $availableProduct->id, 'quantity' => 3],
                ['product_id' => $unavailableProduct->id, 'quantity' => 1],
            ],
        ])->assertUnprocessable();

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseHas('products', [
            'id' => $availableProduct->id,
            'stock_on_hand' => 10,
        ]);
        Queue::assertNothingPushed();
    }

    public function test_it_returns_order_history_for_a_customer_email(): void
    {
        $customer = Customer::factory()->create(['email' => 'alex@example.com']);
        Order::factory()->for($customer)->create();

        $this->getJson('/api/customers/orders?email=alex%40example.com')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.customer.email', 'alex@example.com');
    }

    public function test_it_returns_only_products_below_the_requested_stock_threshold(): void
    {
        $belowThreshold = Product::factory()->create(['stock_on_hand' => 4]);
        Product::factory()->create(['stock_on_hand' => 5]);

        $this->getJson('/api/products/low-stock?threshold=5')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $belowThreshold->id);
    }

    public function test_it_requires_at_least_one_order_line(): void
    {
        $this->postJson('/api/orders', [
            'customer' => ['name' => 'Alex Buyer', 'email' => 'alex@example.com'],
            'items' => [],
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('items');
    }

    public function test_sample_seeders_are_repeatable(): void
    {
        $this->seed();
        $this->seed();

        $this->assertDatabaseCount('customers', 5);
        $this->assertDatabaseCount('products', 8);
    }
}
