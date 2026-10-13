<?php

use App\Http\Controllers\Admin\DashboardController as AdminDashboard;
use App\Http\Controllers\Admin\NodeController as AdminNodeController;
use App\Http\Controllers\Admin\RequestController as AdminRequestController;
use App\Http\Controllers\Admin\ImportController as AdminImportController;
use App\Http\Controllers\Admin\MemberController as AdminMemberController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\NodeRequestController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TreeController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/

// Landing page
Route::get('/', [HomeController::class, 'index'])->name('home');

// Public genealogy tree
Route::get('/pohon', [TreeController::class, 'index'])->name('tree.index');
Route::get('/api/nodes/{node}/ancestors', [TreeController::class, 'ancestors'])->name('tree.ancestors');
Route::get('/api/nodes/search', [TreeController::class, 'search'])->name('tree.search');

// Public node request form
Route::get('/request', [NodeRequestController::class, 'create'])->name('request.create');
Route::post('/request', [NodeRequestController::class, 'store'])->name('request.store');

/*
|--------------------------------------------------------------------------
| Auth Routes (Breeze — includes email verification)
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

/*
|--------------------------------------------------------------------------
| Member Routes (requires auth + verified email)
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'verified'])->prefix('member')->name('member.')->group(function () {
    Route::get('/dashboard', [MemberController::class, 'dashboard'])->name('dashboard');
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
    Route::post('nodes/{node}', [AdminNodeController::class, 'update'])->name('nodes.update.post');

    // Import
    Route::get('import/template', [AdminImportController::class, 'downloadTemplate'])->name('import.template');
    Route::post('import/preview', [AdminImportController::class, 'preview'])->name('import.preview');
    Route::post('import/confirm', [AdminImportController::class, 'confirm'])->name('import.confirm');

    // Requests management
    Route::get('/requests', [AdminRequestController::class, 'index'])->name('requests.index');
    Route::get('/requests/history', [AdminRequestController::class, 'history'])->name('requests.history');
    Route::post('/requests/{nodeRequest}/accept', [AdminRequestController::class, 'accept'])->name('requests.accept');
    Route::post('/requests/{nodeRequest}/reject', [AdminRequestController::class, 'reject'])->name('requests.reject');
    Route::delete('/requests/{nodeRequest}', [AdminRequestController::class, 'destroy'])->name('requests.destroy');

    // Members management
    Route::get('/members', [AdminMemberController::class, 'index'])->name('members.index');
    Route::post('/members/{user}/approve', [AdminMemberController::class, 'approve'])->name('members.approve');
    Route::post('/members/{user}/reject', [AdminMemberController::class, 'reject'])->name('members.reject');
    Route::delete('/members/{user}', [AdminMemberController::class, 'destroy'])->name('members.destroy');
});

require __DIR__ . '/auth.php';
