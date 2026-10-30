<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $products = [
            ['name' => 'Everyday Notebook', 'code' => 'PAP-1001', 'unit_price' => '4.50', 'tax_percentage' => '5.00', 'stock_on_hand' => 28],
            ['name' => 'Aluminum Water Bottle', 'code' => 'KIT-2004', 'unit_price' => '18.95', 'tax_percentage' => '8.25', 'stock_on_hand' => 14],
            ['name' => 'Canvas Tote Bag', 'code' => 'ACC-3012', 'unit_price' => '12.00', 'tax_percentage' => '8.25', 'stock_on_hand' => 3],
            ['name' => 'Desk Organizer', 'code' => 'OFF-4108', 'unit_price' => '24.99', 'tax_percentage' => '8.25', 'stock_on_hand' => 8],
            ['name' => 'Gel Pen Set', 'code' => 'PAP-1205', 'unit_price' => '7.25', 'tax_percentage' => '5.00', 'stock_on_hand' => 46],
            ['name' => 'Compact Umbrella', 'code' => 'OUT-5003', 'unit_price' => '21.50', 'tax_percentage' => '8.25', 'stock_on_hand' => 0],
            ['name' => 'Ceramic Mug', 'code' => 'KIT-2201', 'unit_price' => '11.75', 'tax_percentage' => '8.25', 'stock_on_hand' => 19],
            ['name' => 'Pocket Planner', 'code' => 'PAP-1309', 'unit_price' => '9.99', 'tax_percentage' => '5.00', 'stock_on_hand' => 2],
        ];

        foreach ($products as $product) {
            Product::query()->firstOrCreate(
                ['code' => $product['code']],
                $product,
            );
        }
    }
}
