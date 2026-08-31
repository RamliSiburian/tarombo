<?php

use App\Http\Controllers\Admin\DashboardController as AdminDashboard;
use App\Http\Controllers\Admin\NodeController as AdminNodeController;
use App\Http\Controllers\Admin\RequestController as AdminRequestController;
use App\Http\Controllers\NodeRequestController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TreeController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/

Route::get('/', [TreeController::class, 'index'])->name('tree.index');
Route::get('/api/nodes/{node}/ancestors', [TreeController::class, 'ancestors'])->name('tree.ancestors');
Route::get('/api/nodes/search', [TreeController::class, 'search'])->name('tree.search');
Route::get('/request', [NodeRequestController::class, 'create'])->name('request.create');
Route::post('/request', [NodeRequestController::class, 'store'])->name('request.store');

/*
|--------------------------------------------------------------------------
| Auth Routes (Breeze)
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [AdminDashboard::class, 'index'])->name('dashboard');

    // Nodes CRUD
    Route::resource('nodes', AdminNodeController::class);

    // Requests management
    Route::get('/requests', [AdminRequestController::class, 'index'])->name('requests.index');
    Route::get('/requests/history', [AdminRequestController::class, 'history'])->name('requests.history');
    Route::post('/requests/{nodeRequest}/accept', [AdminRequestController::class, 'accept'])->name('requests.accept');
    Route::post('/requests/{nodeRequest}/reject', [AdminRequestController::class, 'reject'])->name('requests.reject');
    Route::delete('/requests/{nodeRequest}', [AdminRequestController::class, 'destroy'])->name('requests.destroy');
});

require __DIR__.'/auth.php';
