<?php

use App\Http\Controllers\PodDashboardController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/pod');

// TODO: bungkus dengan middleware auth + RBAC (scope cabang) sebelum produksi.
Route::prefix('pod')->name('pod.')->controller(PodDashboardController::class)->group(function () {
    Route::get('/', 'index')->name('index');
    Route::get('/detail', 'detail')->name('detail');
    Route::get('/export', 'export')->middleware('throttle:10,1')->name('export');
});
