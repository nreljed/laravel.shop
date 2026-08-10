<?php

use App\Shop\Http\Controllers\StorefrontController;
use Illuminate\Support\Facades\Route;

Route::get('/store', [StorefrontController::class, 'index'])->name('store.index');
Route::get('/store/products', [StorefrontController::class, 'products'])->name('store.products');
Route::get('/store/category/{slug}', [StorefrontController::class, 'category'])->name('store.category');
Route::get('/store/{product}', [StorefrontController::class, 'show'])->name('store.show');
