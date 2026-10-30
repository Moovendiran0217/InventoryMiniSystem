<?php

use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\ProductController;
use Illuminate\Support\Facades\Route;

Route::get('/products', [ProductController::class, 'index'])->name('api.products.index');
Route::get('/products/low-stock', [ProductController::class, 'lowStock'])->name('api.products.low-stock');
Route::post('/orders', [OrderController::class, 'store'])->name('api.orders.store');
Route::get('/customers/orders', [OrderController::class, 'history'])->name('api.customers.orders');
