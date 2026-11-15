<?php

use App\Http\Controllers\AdministrationController;
use App\Http\Controllers\AgendaController;
use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\AssetController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FinanceController;
use App\Http\Controllers\PublicHomeController;
use App\Http\Controllers\ResidentController;
use App\Http\Controllers\ServiceRequestController;
use App\Http\Controllers\StoredFileController;
use Illuminate\Support\Facades\Route;

Route::get('/', PublicHomeController::class)->name('home');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('login.store');
});

Route::middleware(['auth', 'active'])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    Route::middleware('role:super_admin,rw_operator,rt_operator')->group(function () {
        Route::get('/residents', [ResidentController::class, 'index'])->name('residents.index');
        Route::post('/households', [ResidentController::class, 'storeHousehold'])->name('households.store');
        Route::post('/households/{household}/residents', [ResidentController::class, 'storeResident'])->name('residents.people.store');
        Route::patch('/residents/{resident}', [ResidentController::class, 'updateResident'])->name('residents.update');

        Route::get('/announcements', [AnnouncementController::class, 'index'])->name('announcements.index');
        Route::post('/announcements', [AnnouncementController::class, 'store'])->name('announcements.store');
        Route::patch('/announcements/{announcement}', [AnnouncementController::class, 'update'])->name('announcements.update');

        Route::get('/agenda', [AgendaController::class, 'index'])->name('agenda.index');
        Route::post('/agenda', [AgendaController::class, 'store'])->name('agenda.store');
        Route::patch('/agenda/{event}', [AgendaController::class, 'update'])->name('agenda.update');

        Route::post('/finance/entries', [FinanceController::class, 'storeEntry'])->name('finance.entries.store');
        Route::post('/finance/contributions', [FinanceController::class, 'storeContribution'])->name('finance.contributions.store');

        Route::post('/assets', [AssetController::class, 'store'])->name('assets.store');
        Route::patch('/asset-loans/{loan}', [AssetController::class, 'updateLoan'])->name('assets.loans.update');

        Route::patch('/service-requests/{serviceRequest}', [ServiceRequestController::class, 'update'])->name('requests.update');
    });

    Route::middleware('role:super_admin')->prefix('/admin')->name('admin.')->group(function () {
        Route::get('/setup', [AdministrationController::class, 'index'])->name('setup');
        Route::post('/rw-areas', [AdministrationController::class, 'storeRw'])->name('rw.store');
        Route::post('/rt-areas', [AdministrationController::class, 'storeRt'])->name('rt.store');
        Route::post('/users', [AdministrationController::class, 'storeUser'])->name('users.store');
        Route::patch('/users/{user}/status', [AdministrationController::class, 'setUserStatus'])->name('users.status');
    });

    Route::middleware('role:super_admin,rw_operator,rt_operator,resident')->group(function () {
        Route::get('/finance', [FinanceController::class, 'index'])->name('finance.index');
        Route::get('/assets', [AssetController::class, 'index'])->name('assets.index');
        Route::get('/service-requests', [ServiceRequestController::class, 'index'])->name('requests.index');
        Route::post('/service-requests', [ServiceRequestController::class, 'store'])
            ->middleware('throttle:5,1')
            ->name('requests.store');
        Route::post('/service-requests/{serviceRequest}/files', [StoredFileController::class, 'store'])
            ->middleware('throttle:10,1')
            ->name('requests.files.store');
        Route::get('/files/{storedFile}/download', [StoredFileController::class, 'download'])->name('files.download');
    });

    Route::middleware('role:resident')->post('/assets/{asset}/loans', [AssetController::class, 'requestLoan'])
        ->middleware('throttle:5,1')
        ->name('assets.loans.store');
});
