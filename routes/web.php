<?php

use App\Http\Controllers\ContractController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\IqamaController;
use App\Http\Controllers\LicenseController;
use App\Http\Controllers\OrganizationSwitchController;
use App\Http\Controllers\SettingController;
use Illuminate\Support\Facades\Route;

// الصفحة الرئيسية - الداشبورد
Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

// التبديل بين المؤسسات (ترقيم / تفنين)
Route::post('/organizations/switch/{id}', [OrganizationSwitchController::class, 'switch'])->name('organizations.switch');

// وحدة الإقامات
Route::prefix('iqamas')->name('iqamas.')->group(function () {
    Route::get('/', [IqamaController::class, 'index'])->name('index');
    Route::post('/', [IqamaController::class, 'store'])->name('store');
    Route::put('/{iqama}', [IqamaController::class, 'update'])->name('update');
    Route::delete('/{iqama}', [IqamaController::class, 'destroy'])->name('destroy');
    Route::get('/export/csv', [IqamaController::class, 'export'])->name('export');
});

// وحدة العقود الوظيفية
Route::prefix('contracts')->name('contracts.')->group(function () {
    Route::get('/', [ContractController::class, 'index'])->name('index');
    Route::post('/', [ContractController::class, 'store'])->name('store');
    Route::put('/{contract}', [ContractController::class, 'update'])->name('update');
    Route::delete('/{contract}', [ContractController::class, 'destroy'])->name('destroy');
    Route::get('/export/csv', [ContractController::class, 'export'])->name('export');
});

// وحدة التراخيص والشهادات
Route::prefix('licenses')->name('licenses.')->group(function () {
    Route::get('/', [LicenseController::class, 'index'])->name('index');
    Route::post('/', [LicenseController::class, 'store'])->name('store');
    Route::put('/{license}', [LicenseController::class, 'update'])->name('update');
    Route::delete('/{license}', [LicenseController::class, 'destroy'])->name('destroy');
    Route::get('/export/csv', [LicenseController::class, 'export'])->name('export');
});

// وحدة الاستيراد من إكسل
Route::prefix('imports')->name('imports.')->group(function () {
    Route::get('/', [ImportController::class, 'index'])->name('index');
    Route::post('/upload', [ImportController::class, 'store'])->name('store');
    Route::get('/templates/{type}', [ImportController::class, 'downloadTemplate'])->name('template');
});

// الإعدادات والتنبيهات
Route::prefix('settings')->name('settings.')->group(function () {
    Route::get('/', [SettingController::class, 'index'])->name('index');
    Route::post('/update', [SettingController::class, 'update'])->name('update');
    Route::post('/trigger-alerts', [SettingController::class, 'runAlertsNow'])->name('triggerAlerts');
});
