<?php

use App\Http\Controllers\AdministrationController;
use App\Http\Controllers\AgendaController;
use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\AreaCorrectionController;
use App\Http\Controllers\AssetController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\ContentPhotoController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FinanceController;
use App\Http\Controllers\GalleryController;
use App\Http\Controllers\MyHouseholdController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicHomeController;
use App\Http\Controllers\ResidentController;
use App\Http\Controllers\ResidentImportController;
use App\Http\Controllers\ServiceRequestController;
use App\Http\Controllers\StoredFileController;
use App\Http\Middleware\EnsurePasswordChanged;
use App\Http\Middleware\PreventPrivateCaching;
use Illuminate\Support\Facades\Route;

Route::get('/', PublicHomeController::class)->name('home');
Route::get('/gallery', [GalleryController::class, 'index'])->middleware(PreventPrivateCaching::class)->name('gallery.index');
Route::get('/content-photos/{storedFile}', [ContentPhotoController::class, 'show'])->name('content-photos.show');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])
        ->middleware('throttle:login')
        ->name('login.store');
});

Route::middleware(['auth', 'active', EnsurePasswordChanged::class, PreventPrivateCaching::class])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::patch('/profile/password', [ProfileController::class, 'password'])
        ->middleware('throttle:5,1')->name('profile.password');

    Route::middleware('role:super_admin,rw_operator,rt_operator')->group(function () {
        Route::get('/users', [AdministrationController::class, 'index'])->name('users.index');
        Route::post('/users', [AdministrationController::class, 'storeUser'])->name('users.store');
        Route::patch('/users/{user}', [AdministrationController::class, 'updateUser'])->name('users.update');
        Route::patch('/users/{user}/status', [AdministrationController::class, 'setUserStatus'])->name('users.status');
        Route::patch('/users/{user}/password', [AdministrationController::class, 'resetPassword'])
            ->middleware('throttle:5,1')->name('users.password');
        Route::get('/residents', [ResidentController::class, 'index'])->name('residents.index');
        Route::get('/residents/import', [ResidentImportController::class, 'index'])->name('residents.import.index');
        Route::get('/residents/import/template', [ResidentImportController::class, 'template'])->middleware('throttle:10,1')->name('residents.import.template');
        Route::post('/residents/import/preview', [ResidentImportController::class, 'preview'])->middleware('throttle:3,1')->name('residents.import.preview');
        Route::post('/residents/import/confirm', [ResidentImportController::class, 'store'])->middleware('throttle:2,1')->name('residents.import.store');
        Route::post('/households', [ResidentController::class, 'storeHousehold'])->middleware('throttle:10,1')->name('households.store');
        Route::patch('/households/{household}/identity', [ResidentController::class, 'updateFamilyCard'])->middleware('throttle:10,1')->name('households.identity');
        Route::patch('/households/{household}/head', [ResidentController::class, 'updateHead'])->middleware('throttle:10,1')->name('households.head');
        Route::get('/households/{household}/residents/create', [ResidentController::class, 'createResident'])->name('residents.people.create');
        Route::post('/households/{household}/residents', [ResidentController::class, 'storeResident'])->middleware('throttle:10,1')->name('residents.people.store');
        Route::get('/residents/{resident}/edit', [ResidentController::class, 'editResident'])->name('residents.edit');
        Route::patch('/residents/{resident}/details', [ResidentController::class, 'updateDetails'])->middleware('throttle:10,1')->name('residents.details');
        Route::patch('/residents/{resident}', [ResidentController::class, 'updateResident'])->name('residents.update');

        Route::get('/announcements', [AnnouncementController::class, 'index'])->name('announcements.index');
        Route::post('/announcements', [AnnouncementController::class, 'store'])->name('announcements.store');
        Route::patch('/announcements/{announcement}', [AnnouncementController::class, 'update'])->name('announcements.update');

        Route::get('/agenda', [AgendaController::class, 'index'])->name('agenda.index');
        Route::post('/agenda', [AgendaController::class, 'store'])->name('agenda.store');
        Route::patch('/agenda/{event}', [AgendaController::class, 'update'])->name('agenda.update');

        Route::post('/gallery', [GalleryController::class, 'store'])->middleware('throttle:10,1')->name('gallery.store');
        Route::patch('/gallery/{galleryItem}', [GalleryController::class, 'update'])->middleware('throttle:10,1')->name('gallery.update');

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
        Route::patch('/rt-areas/{rtArea}', [AdministrationController::class, 'updateRt'])->name('rt.update');
        Route::patch('/rw-areas/{rwArea}', [AdministrationController::class, 'updateRw'])->name('rw.update');
        Route::post('/rw-areas/{rwArea}/convert-to-rt', [AreaCorrectionController::class, 'convertRwToRt'])->name('rw.convert-to-rt');
        Route::patch('/rt-areas/{rtArea}/chairman', [AdministrationController::class, 'updateRtChairman'])->name('rt.chairman');
        Route::patch('/rw-areas/{rwArea}/chairman', [AdministrationController::class, 'updateRwChairman'])->name('rw.chairman');
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
    Route::middleware('role:resident')->get('/my-household', MyHouseholdController::class)->name('households.mine');
});
