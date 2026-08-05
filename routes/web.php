<?php

use App\Http\Controllers\DeployController;
use App\Http\Controllers\PublicController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// ═══════════════════════════════════════════════════
// FRONTEND (Public) Pages — no auth required
// ═══════════════════════════════════════════════════
Route::get('/', function () {
    return Inertia::render('public/welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
    ]);
})->name('home');

Route::get('/about', [PublicController::class, 'about'])->name('about');
Route::get('/services', [PublicController::class, 'services'])->name('services');
Route::get('/contact', [PublicController::class, 'contact'])->name('contact');

// ═══════════════════════════════════════════════════
// DEPLOY Endpoints — token-protected, no CSRF
// ═══════════════════════════════════════════════════
Route::post('/deploy/upload', [DeployController::class, 'upload']);
Route::post('/deploy/update-env', [DeployController::class, 'updateEnv']);
Route::get('/deploy/cache-clear', [DeployController::class, 'cacheClear']);
Route::match(['get', 'post'], '/deploy/check', [DeployController::class, 'check']);

// ═══════════════════════════════════════════════════
// CSP Report Endpoint — receives violation reports
// ═══════════════════════════════════════════════════
Route::post('/csp-report', function (Request $request) {
    report(new Exception('CSP Violation: '.$request->input('csp-report.effective-directive', 'unknown')));

    return response()->json(['status' => 'ok']);
})->middleware('throttle:30,1')->name('csp.report');

// ═══════════════════════════════════════════════════
// FEATURE ROUTES — loaded by feature service providers
// ═══════════════════════════════════════════════════
// All dashboard, admin, auth, notification, account, and media routes
// are provided by nreljed/dashboard-core (DashboardCoreServiceProvider).

// Shop feature routes
require __DIR__.'/shop.php';
