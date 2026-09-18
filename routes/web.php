<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Mars;
use App\Http\Controllers\Saturnus;
use App\Http\Controllers\Settings;

/*
|--------------------------------------------------------------------------
| Web Routes - MAI Unified Warehouse Portal
|--------------------------------------------------------------------------
*/

// Root redirect
Route::get('/', function () {
    return auth()->check() ? redirect()->route('dashboard.index') : redirect()->route('login');
});

// Authentication Routes
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
Route::get('/guest-login', [AuthController::class, 'guestLogin'])->name('guest.login');
Route::post('/guest-login', [AuthController::class, 'guestLogin']);

// Protected Routes
Route::middleware(['auth'])->group(function () {

    // Unified Executive Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard.index');
    Route::get('/dashboard-overview', fn() => redirect()->route('dashboard.index'))->name('dashboard');

    /*
    |--------------------------------------------------------------------------
    | MARS MODULE ROUTES (Prefix: /mars)
    |--------------------------------------------------------------------------
    */
    Route::prefix('mars')->name('mars.')->middleware(['permission:mars.dashboard.view,mars.master.view,mars.master.manage,mars.po.view,mars.po.manage,mars.outstanding.view,mars.outstanding.manage,mars.minim.view,mars.minim.manage,mars.kedatangan.view,mars.kedatangan.manage,mars.history.view,mars.history.manage'])->group(function () {
        Route::get('/', [Mars\DashboardController::class, 'index'])->name('dashboard')->middleware('permission:mars.dashboard.view');

        // Item Master
        Route::get('/item-master', [Mars\ItemMasterController::class, 'index'])->name('item_master.index')->middleware('permission:mars.master.view');
        Route::post('/item-master', [Mars\ItemMasterController::class, 'store'])->name('item_master.store')->middleware('permission:mars.master.manage');
        Route::put('/item-master/{id}', [Mars\ItemMasterController::class, 'update'])->name('item_master.update')->middleware('permission:mars.master.manage');
        Route::delete('/item-master/{id}', [Mars\ItemMasterController::class, 'destroy'])->name('item_master.destroy')->middleware('permission:mars.master.manage');
        Route::post('/item-master/import', [Mars\ItemMasterController::class, 'importExcel'])->name('item_master.import')->middleware('permission:mars.master.manage');
        Route::post('/item-master/delete-all', [Mars\ItemMasterController::class, 'deleteAll'])->name('item_master.delete_all')->middleware('permission:mars.master.manage');
        Route::match(['put', 'post'], '/item-master/{id}/update-note', [Mars\ItemMasterController::class, 'updateNote'])->name('item_master.update_note')->middleware('permission:mars.master.manage');
        Route::match(['put', 'post'], '/item-master/note/{id}', [Mars\ItemMasterController::class, 'updateNote'])->middleware('permission:mars.master.manage');
        Route::match(['put', 'post'], '/item-master/{id}/update-follow', [Mars\ItemMasterController::class, 'updateFollow'])->name('item_master.update_follow')->middleware('permission:mars.master.manage');
        Route::match(['put', 'post'], '/item-master/{id}/update-pengiriman-tanggal', [Mars\ItemMasterController::class, 'updatePengirimanTanggal'])->name('item_master.update_pengiriman_tanggal')->middleware('permission:mars.master.manage');
        Route::match(['put', 'post'], '/item-master/{id}/update-request-whc', [Mars\ItemMasterController::class, 'updateRequestWhc'])->name('item_master.update_request_whc')->middleware('permission:mars.master.manage');
        Route::match(['put', 'post'], '/item-master/{id}/update-request-whc-date', [Mars\ItemMasterController::class, 'updateRequestWhcDate'])->name('item_master.update_request_whc_date')->middleware('permission:mars.master.manage');
        Route::get('/item-master/export', [Mars\ItemMasterController::class, 'export'])->name('item_master.export')->middleware('permission:mars.master.view');

        // Data PO
        Route::get('/data-po', [Mars\DataPOController::class, 'index'])->name('data_po.index')->middleware('permission:mars.po.view');
        Route::post('/data-po/import', [Mars\DataPOController::class, 'importExcel'])->name('data_po.import')->middleware('permission:mars.po.manage');
        Route::post('/data-po/delete-all', [Mars\DataPOController::class, 'deleteAll'])->name('data_po.delete_all')->middleware('permission:mars.po.manage');
        Route::delete('/data-po/{id}', [Mars\DataPOController::class, 'destroy'])->name('data_po.destroy')->middleware('permission:mars.po.manage');
        Route::post('/data-po/{id}/delete', [Mars\DataPOController::class, 'destroy'])->name('data_po.post_destroy')->middleware('permission:mars.po.manage');

        // Item Outstanding
        Route::get('/item-outstanding', [Mars\OutstandingController::class, 'index'])->name('item_outstanding.index')->middleware('permission:mars.outstanding.view');
        Route::post('/item-outstanding', [Mars\OutstandingController::class, 'store'])->name('item_outstanding.store')->middleware('permission:mars.outstanding.manage');
        Route::post('/item-outstanding/import', [Mars\OutstandingController::class, 'importExcel'])->name('item_outstanding.import')->middleware('permission:mars.outstanding.manage');
        Route::match(['put', 'post'], '/item-outstanding/{id}/update-note', [Mars\OutstandingController::class, 'updateNote'])->name('item_outstanding.update_note')->middleware('permission:mars.outstanding.manage');
        Route::match(['put', 'post'], '/item-outstanding/note/{id}', [Mars\OutstandingController::class, 'updateNote'])->middleware('permission:mars.outstanding.manage');
        Route::match(['put', 'post'], '/item-outstanding/{id}/update-follow', [Mars\OutstandingController::class, 'updateFollow'])->name('item_outstanding.update_follow')->middleware('permission:mars.outstanding.manage');
        Route::match(['put', 'post'], '/item-outstanding/update-follow/{id}', [Mars\OutstandingController::class, 'updateFollow'])->middleware('permission:mars.outstanding.manage');
        Route::match(['put', 'post'], '/item-outstanding/{id}/update-pengiriman-tanggal', [Mars\OutstandingController::class, 'updatePengirimanTanggal'])->name('item_outstanding.update_pengiriman_tanggal')->middleware('permission:mars.outstanding.manage');
        Route::match(['put', 'post'], '/item-outstanding/update-pengiriman-tanggal/{id}', [Mars\OutstandingController::class, 'updatePengirimanTanggal'])->middleware('permission:mars.outstanding.manage');
        Route::match(['put', 'post'], '/item-outstanding/{id}/update-request-whc', [Mars\OutstandingController::class, 'updateRequestWhc'])->name('item_outstanding.update_request_whc')->middleware('permission:mars.outstanding.manage');
        Route::match(['put', 'post'], '/item-outstanding/update-request-whc/{id}', [Mars\OutstandingController::class, 'updateRequestWhc'])->middleware('permission:mars.outstanding.manage');
        Route::match(['put', 'post'], '/item-outstanding/{id}/update-request-whc-date', [Mars\OutstandingController::class, 'updateRequestWhcDate'])->name('item_outstanding.update_request_whc_date')->middleware('permission:mars.outstanding.manage');
        Route::match(['put', 'post'], '/item-outstanding/update-request-whc-date/{id}', [Mars\OutstandingController::class, 'updateRequestWhcDate'])->middleware('permission:mars.outstanding.manage');
        Route::match(['put', 'post'], '/item-outstanding/{id}/update-follow-up', [Mars\OutstandingController::class, 'updateFollowUp'])->name('item_outstanding.update_follow_up')->middleware('permission:mars.outstanding.manage');
        Route::match(['put', 'post'], '/item-outstanding/update-follow-up/{id}', [Mars\OutstandingController::class, 'updateFollowUp'])->middleware('permission:mars.outstanding.manage');

        // Item Minim
        Route::get('/item-minim', [Mars\ItemMinimController::class, 'index'])->name('item_minim.index')->middleware('permission:mars.minim.view');
        Route::put('/item-minim/{id}', [Mars\ItemMinimController::class, 'update'])->name('item_minim.update')->middleware('permission:mars.minim.manage');
        Route::delete('/item-minim/{id}', [Mars\ItemMinimController::class, 'destroy'])->name('item_minim.destroy')->middleware('permission:mars.minim.manage');
        Route::match(['put', 'post'], '/item-minim/{id}/update-note', [Mars\ItemMinimController::class, 'updateNote'])->name('item_minim.update_note')->middleware('permission:mars.minim.manage');
        Route::match(['put', 'post'], '/item-minim/note/{id}', [Mars\ItemMinimController::class, 'updateNote'])->middleware('permission:mars.minim.manage');
        Route::match(['put', 'post'], '/item-minim/{id}/update-follow-up', [Mars\ItemMinimController::class, 'updateFollowUp'])->name('item_minim.update_follow_up')->middleware('permission:mars.minim.manage');
        Route::match(['put', 'post'], '/item-minim/update-follow-up/{id}', [Mars\ItemMinimController::class, 'updateFollowUp'])->middleware('permission:mars.minim.manage');
        Route::get('/item-minim/export', [Mars\ItemMinimController::class, 'export'])->name('item_minim.export')->middleware('permission:mars.minim.view');

        // Kedatangan Barang
        Route::get('/kedatangan-barang', [Mars\KedatanganBarangController::class, 'index'])->name('kedatangan_barang.index')->middleware('permission:mars.kedatangan.view');
        Route::post('/kedatangan-barang/import', [Mars\KedatanganBarangController::class, 'importExcel'])->name('kedatangan_barang.import')->middleware('permission:mars.kedatangan.manage');

        // History
        Route::get('/history', [Mars\HistoryController::class, 'index'])->name('history.index')->middleware('permission:mars.history.view');
        Route::put('/history/{id}', [Mars\HistoryController::class, 'update'])->name('history.update')->middleware('permission:mars.history.manage');
        Route::delete('/history/{id}', [Mars\HistoryController::class, 'destroy'])->name('history.destroy')->middleware('permission:mars.history.manage');
        Route::post('/history/bulk-delete', [Mars\HistoryController::class, 'bulkDestroy'])->name('history.bulk_destroy')->middleware('permission:mars.history.manage');
        Route::get('/history/export', [Mars\HistoryController::class, 'export'])->name('history.export')->middleware('permission:mars.history.view');
    });

    /*
    |--------------------------------------------------------------------------
    | SATURNUS MODULE ROUTES (Prefix: /saturnus)
    |--------------------------------------------------------------------------
    */
    Route::prefix('saturnus')->name('saturnus.')->middleware(['permission:saturnus.directory.view,saturnus.registrasi.view,saturnus.registrasi.approve,saturnus.registrasi.create,saturnus.unregistrasi.view,saturnus.unregistrasi.approve,saturnus.unregistrasi.create,saturnus.items.manage'])->group(function () {
        Route::get('/', [Saturnus\ItemController::class, 'index'])->name('dashboard')->middleware('permission:saturnus.directory.view,saturnus.registrasi.view,saturnus.unregistrasi.view');
        Route::post('/items', [Saturnus\ItemController::class, 'store'])->name('items.store')->middleware('permission:saturnus.items.manage');
        Route::post('/items/{id}/unregister', [Saturnus\ItemController::class, 'unregister'])->name('items.unregister')->middleware('permission:saturnus.items.manage');

        // Form Registrasi & Dedicated Views
        Route::get('/form-registrasi', [Saturnus\ItemController::class, 'formRegistrasi'])->name('form_registrasi')->middleware('permission:saturnus.registrasi.view');
        Route::get('/proses-approval', [Saturnus\ItemController::class, 'prosesApproval'])->name('proses_approval')->middleware('permission:saturnus.registrasi.approve,saturnus.registrasi.view');
        Route::get('/data-view', [Saturnus\ItemController::class, 'dataView'])->name('data_view')->middleware('permission:saturnus.directory.view,saturnus.registrasi.view');
        Route::post('/form-registrasi/item', [Saturnus\ItemController::class, 'storeFormItem'])->name('form_registrasi.item.store')->middleware('permission:saturnus.registrasi.create');
        Route::post('/form-registrasi/approve', [Saturnus\ItemController::class, 'approveForm'])->name('form_registrasi.approve')->middleware('permission:saturnus.registrasi.approve');
        Route::match(['delete', 'post'], '/form-registrasi/delete-checksheet', [Saturnus\ItemController::class, 'deleteFormChecksheet'])->name('form_registrasi.delete_checksheet')->middleware('permission:saturnus.registrasi.create');
        Route::delete('/form-registrasi/item/{id}', [Saturnus\ItemController::class, 'deleteFormItem'])->name('form_registrasi.item.delete')->middleware('permission:saturnus.registrasi.create');
        Route::post('/form-registrasi/comment', [Saturnus\ItemController::class, 'storeComment'])->name('form_registrasi.comment.store')->middleware('permission:saturnus.registrasi.view');
        Route::delete('/form-registrasi/comment/{id}', [Saturnus\ItemController::class, 'deleteComment'])->name('form_registrasi.comment.delete')->middleware('permission:saturnus.registrasi.view');

        // Form Unregistrasi & Dedicated Views
        Route::get('/form-unregistrasi', [Saturnus\FormUnregistrasiController::class, 'formUnregistrasi'])->name('form_unregistrasi')->middleware('permission:saturnus.unregistrasi.view');
        Route::get('/unregistrasi-approval', [Saturnus\FormUnregistrasiController::class, 'prosesApproval'])->name('unregistrasi_approval')->middleware('permission:saturnus.unregistrasi.approve,saturnus.unregistrasi.view');
        Route::get('/unregistrasi/approval', [Saturnus\FormUnregistrasiController::class, 'prosesApproval'])->name('unregistrasi.approval')->middleware('permission:saturnus.unregistrasi.approve,saturnus.unregistrasi.view');
        Route::get('/unregistrasi-history', [Saturnus\FormUnregistrasiController::class, 'history'])->name('unregistrasi_history')->middleware('permission:saturnus.unregistrasi.view');
        Route::get('/unregistrasi/history', [Saturnus\FormUnregistrasiController::class, 'history'])->name('unregistrasi.history')->middleware('permission:saturnus.unregistrasi.view');
        Route::get('/unregistrasi/export-excel', [Saturnus\FormUnregistrasiController::class, 'exportExcel'])->name('unregistrasi.export')->middleware('permission:saturnus.unregistrasi.view');
        Route::post('/form-unregistrasi/item', [Saturnus\FormUnregistrasiController::class, 'storeFormItem'])->name('form_unregistrasi.item.store')->middleware('permission:saturnus.unregistrasi.create');
        Route::post('/form-unregistrasi/approve', [Saturnus\FormUnregistrasiController::class, 'approveForm'])->name('form_unregistrasi.approve')->middleware('permission:saturnus.unregistrasi.approve');
        Route::match(['delete', 'post'], '/form-unregistrasi/delete-checksheet', [Saturnus\FormUnregistrasiController::class, 'deleteFormChecksheet'])->name('form_unregistrasi.delete_checksheet')->middleware('permission:saturnus.unregistrasi.create');
        Route::post('/form-unregistrasi/comment', [Saturnus\FormUnregistrasiController::class, 'storeComment'])->name('form_unregistrasi.comment.store')->middleware('permission:saturnus.unregistrasi.view');
        Route::delete('/form-unregistrasi/comment/{id}', [Saturnus\FormUnregistrasiController::class, 'deleteComment'])->name('form_unregistrasi.comment.delete')->middleware('permission:saturnus.unregistrasi.view');

        // Email Reminder & Notification Routes
        Route::get('/email-reminder', [\App\Http\Controllers\EmailReminderController::class, 'index'])->name('email_reminder');
        Route::post('/email-reminder/send', [\App\Http\Controllers\EmailReminderController::class, 'send'])->name('email_reminder.send');
        Route::post('/email-reminder/preview', [\App\Http\Controllers\EmailReminderController::class, 'preview'])->name('email_reminder.preview');
        Route::get('/email-reminder/pending-for-user/{userId}', [\App\Http\Controllers\EmailReminderController::class, 'getPendingForUser'])->name('email_reminder.pending_for_user');
        Route::post('/email-reminder/schedule/save', [\App\Http\Controllers\EmailReminderController::class, 'saveScheduleSettings'])->name('email_reminder.schedule_save');
        Route::post('/email-reminder/schedule/run-now', [\App\Http\Controllers\EmailReminderController::class, 'runScheduleNow'])->name('email_reminder.schedule_run_now');
        Route::get('/email-reminder/schedule/status', [\App\Http\Controllers\EmailReminderController::class, 'getScheduleStatus'])->name('email_reminder.schedule_status');
    });

    Route::get('/email-reminder', fn() => redirect()->route('saturnus.email_reminder'));

    /*
    |--------------------------------------------------------------------------
    | SETTINGS & USER MANAGEMENT ROUTES
    |--------------------------------------------------------------------------
    */
    Route::prefix('settings')->name('settings.')->middleware(['permission:settings.users.manage,settings.roles.manage'])->group(function () {
        Route::get('/users', [Settings\UserController::class, 'index'])->name('users.index')->middleware('permission:settings.users.manage');
        Route::post('/users', [Settings\UserController::class, 'store'])->name('users.store')->middleware('permission:settings.users.manage');
        Route::put('/users/{id}', [Settings\UserController::class, 'update'])->name('users.update')->middleware('permission:settings.users.manage');
        Route::delete('/users/{id}', [Settings\UserController::class, 'destroy'])->name('users.destroy')->middleware('permission:settings.users.manage');

        Route::get('/roles', [Settings\RolePermissionController::class, 'index'])->name('roles.index')->middleware('permission:settings.roles.manage');
        Route::post('/roles', [Settings\RolePermissionController::class, 'store'])->name('roles.store')->middleware('permission:settings.roles.manage');
        Route::put('/roles/{id}', [Settings\RolePermissionController::class, 'update'])->name('roles.update')->middleware('permission:settings.roles.manage');
        Route::delete('/roles/{id}', [Settings\RolePermissionController::class, 'destroy'])->name('roles.destroy')->middleware('permission:settings.roles.manage');
    });

    /*
    |--------------------------------------------------------------------------
    | LEGACY & RETRO-COMPATIBILITY ROUTES (ZERO BREAKING CHANGES)
    |--------------------------------------------------------------------------
    */
    // MARS Legacy Underscore & Hyphen Endpoints
    Route::middleware(['permission:mars.master.view'])->group(function() {
        Route::prefix('item_master')->group(function() {
            Route::get('/', fn() => redirect()->route('mars.item_master.index'))->name('item_master.index');
            Route::post('/import-excel', [Mars\ItemMasterController::class, 'importExcel'])->name('item_master.importExcel');
            Route::post('/delete-all-items', [Mars\ItemMasterController::class, 'deleteAll'])->name('item_master.deleteAllItems');
            Route::match(['put', 'post'], '/note/{id}', [Mars\ItemMasterController::class, 'updateNote'])->name('item_master.updateNote');
            Route::put('/{id}', [Mars\ItemMasterController::class, 'update'])->name('item_master.update');
            Route::delete('/{id}', [Mars\ItemMasterController::class, 'destroy'])->name('item_master.destroy');
            Route::post('/{id}/destroy', [Mars\ItemMasterController::class, 'destroy']);
        });
        Route::prefix('item-master')->group(function() {
            Route::get('/', fn() => redirect()->route('mars.item_master.index'));
            Route::post('/import-excel', [Mars\ItemMasterController::class, 'importExcel']);
            Route::post('/delete-all-items', [Mars\ItemMasterController::class, 'deleteAll']);
            Route::match(['put', 'post'], '/note/{id}', [Mars\ItemMasterController::class, 'updateNote']);
            Route::match(['put', 'post'], '/{id}/update-note', [Mars\ItemMasterController::class, 'updateNote']);
            Route::match(['put', 'post'], '/{id}/update-follow', [Mars\ItemMasterController::class, 'updateFollow']);
            Route::match(['put', 'post'], '/{id}/update-pengiriman-tanggal', [Mars\ItemMasterController::class, 'updatePengirimanTanggal']);
            Route::match(['put', 'post'], '/{id}/update-request-whc', [Mars\ItemMasterController::class, 'updateRequestWhc']);
            Route::match(['put', 'post'], '/{id}/update-request-whc-date', [Mars\ItemMasterController::class, 'updateRequestWhcDate']);
        });
    });

    Route::middleware(['permission:mars.po.view'])->group(function() {
        Route::prefix('data_po')->group(function() {
            Route::get('/', fn() => redirect()->route('mars.data_po.index'))->name('data_po.index');
            Route::post('/import-excel', [Mars\DataPOController::class, 'importExcel'])->name('data_po.importExcel');
            Route::post('/delete-all', [Mars\DataPOController::class, 'deleteAll'])->name('data_po.deleteAll');
            Route::delete('/{id}', [Mars\DataPOController::class, 'destroy'])->name('data_po.destroy');
            Route::post('/{id}/destroy', [Mars\DataPOController::class, 'destroy']);
        });
        Route::prefix('data-po')->group(function() {
            Route::get('/', fn() => redirect()->route('mars.data_po.index'));
            Route::post('/import-excel', [Mars\DataPOController::class, 'importExcel']);
            Route::post('/delete-all', [Mars\DataPOController::class, 'deleteAll']);
            Route::delete('/{id}', [Mars\DataPOController::class, 'destroy']);
        });
    });

    Route::middleware(['permission:mars.outstanding.view'])->group(function() {
        Route::prefix('item_outstanding')->group(function() {
            Route::get('/', fn() => redirect()->route('mars.item_outstanding.index'))->name('item_outstanding.index');
            Route::post('/', [Mars\OutstandingController::class, 'store'])->name('item_outstanding.store');
            Route::match(['put', 'post'], '/note/{id}', [Mars\OutstandingController::class, 'updateNote'])->name('item_outstanding.updateNote');
            Route::match(['put', 'post'], '/update-follow/{id}', [Mars\OutstandingController::class, 'updateFollow'])->name('item_outstanding.updateFollow');
            Route::match(['put', 'post'], '/update-pengiriman-tanggal/{id}', [Mars\OutstandingController::class, 'updatePengirimanTanggal'])->name('item_outstanding.updatePengirimanTanggal');
            Route::match(['put', 'post'], '/update-follow-up/{id}', [Mars\OutstandingController::class, 'updateFollowUp'])->name('item_outstanding.updateFollowUp');
            Route::match(['put', 'post'], '/update-request-whc/{id}', [Mars\OutstandingController::class, 'updateRequestWhc'])->name('item_outstanding.updateRequestWhc');
            Route::match(['put', 'post'], '/update-request-whc-date/{id}', [Mars\OutstandingController::class, 'updateRequestWhcDate'])->name('item_outstanding.updateRequestWhcDate');
        });
        Route::prefix('item-outstanding')->group(function() {
            Route::get('/', fn() => redirect()->route('mars.item_outstanding.index'));
            Route::match(['put', 'post'], '/note/{id}', [Mars\OutstandingController::class, 'updateNote']);
            Route::match(['put', 'post'], '/{id}/update-note', [Mars\OutstandingController::class, 'updateNote']);
            Route::match(['put', 'post'], '/update-follow/{id}', [Mars\OutstandingController::class, 'updateFollow']);
            Route::match(['put', 'post'], '/{id}/update-follow', [Mars\OutstandingController::class, 'updateFollow']);
            Route::match(['put', 'post'], '/update-pengiriman-tanggal/{id}', [Mars\OutstandingController::class, 'updatePengirimanTanggal']);
            Route::match(['put', 'post'], '/{id}/update-pengiriman-tanggal', [Mars\OutstandingController::class, 'updatePengirimanTanggal']);
            Route::match(['put', 'post'], '/update-follow-up/{id}', [Mars\OutstandingController::class, 'updateFollowUp']);
            Route::match(['put', 'post'], '/{id}/update-follow-up', [Mars\OutstandingController::class, 'updateFollowUp']);
            Route::match(['put', 'post'], '/update-request-whc/{id}', [Mars\OutstandingController::class, 'updateRequestWhc']);
            Route::match(['put', 'post'], '/{id}/update-request-whc', [Mars\OutstandingController::class, 'updateRequestWhc']);
            Route::match(['put', 'post'], '/update-request-whc-date/{id}', [Mars\OutstandingController::class, 'updateRequestWhcDate']);
            Route::match(['put', 'post'], '/{id}/update-request-whc-date', [Mars\OutstandingController::class, 'updateRequestWhcDate']);
        });
    });

    Route::middleware(['permission:mars.minim.view'])->group(function() {
        Route::prefix('item_minim')->group(function() {
            Route::get('/', fn() => redirect()->route('mars.item_minim.index'))->name('item_minim.index');
            Route::get('/export', [Mars\ItemMinimController::class, 'export'])->name('item_minim.export');
            Route::match(['put', 'post'], '/note/{id}', [Mars\ItemMinimController::class, 'updateNote'])->name('item_minim.updateNote');
            Route::match(['put', 'post'], '/update-follow-up/{id}', [Mars\ItemMinimController::class, 'updateFollowUp'])->name('item_minim.updateFollowUp');
            Route::put('/{id}', [Mars\ItemMinimController::class, 'update'])->name('item_minim.update');
            Route::delete('/{id}', [Mars\ItemMinimController::class, 'destroy'])->name('item_minim.destroy');
        });
        Route::prefix('item-minim')->group(function() {
            Route::get('/', fn() => redirect()->route('mars.item_minim.index'));
            Route::get('/export-excel', [Mars\ItemMinimController::class, 'export']);
            Route::match(['put', 'post'], '/note/{id}', [Mars\ItemMinimController::class, 'updateNote']);
            Route::match(['put', 'post'], '/{id}/update-note', [Mars\ItemMinimController::class, 'updateNote']);
            Route::match(['put', 'post'], '/update-follow-up/{id}', [Mars\ItemMinimController::class, 'updateFollowUp']);
            Route::match(['put', 'post'], '/{id}/update-follow-up', [Mars\ItemMinimController::class, 'updateFollowUp']);
        });
    });

    Route::middleware(['permission:mars.kedatangan.view'])->group(function() {
        Route::prefix('kedatangan_barang')->group(function() {
            Route::get('/', fn() => redirect()->route('mars.kedatangan_barang.index'))->name('kedatangan_barang.index');
            Route::post('/import-excel', [Mars\KedatanganBarangController::class, 'importExcel'])->name('kedatangan_barang.importExcel');
        });
        Route::prefix('kedatangan-barang')->group(function() {
            Route::get('/', fn() => redirect()->route('mars.kedatangan_barang.index'));
            Route::post('/import-excel', [Mars\KedatanganBarangController::class, 'importExcel']);
        });
    });

    Route::middleware(['permission:mars.history.view'])->group(function() {
        Route::prefix('history')->group(function() {
            Route::get('/', fn() => redirect()->route('mars.history.index'))->name('history.index');
            Route::get('/export', [Mars\HistoryController::class, 'export'])->name('history.export');
            Route::get('/export-excel', [Mars\HistoryController::class, 'export']);
            Route::post('/bulk-destroy', [Mars\HistoryController::class, 'bulkDestroy'])->name('history.bulkDestroy');
            Route::post('/bulk-destroy-items', [Mars\HistoryController::class, 'bulkDestroy']);
            Route::match(['put', 'post'], '/{id}', [Mars\HistoryController::class, 'update'])->name('history.update');
            Route::match(['delete', 'post'], '/{id}/destroy', [Mars\HistoryController::class, 'destroy'])->name('history.destroy');
        });
    });

    // SATURNUS Legacy Endpoints
    Route::middleware(['permission:saturnus.registrasi.view'])->group(function() {
        Route::get('/form-registrasi', fn(Illuminate\Http\Request $req) => redirect()->route('saturnus.form_registrasi', $req->query()))->name('form-registrasi');
        Route::post('/form-registrasi', [Saturnus\ItemController::class, 'storeFormItem'])->name('form-registrasi.store');
        Route::post('/form-registrasi/store-item', [Saturnus\ItemController::class, 'storeFormItem']);
        Route::post('/form-registrasi/approve', [Saturnus\ItemController::class, 'approveForm'])->name('form-registrasi.approve');
        Route::post('/form-registrasi/approve-action', [Saturnus\ItemController::class, 'approveForm']);
        Route::match(['delete', 'post'], '/form-registrasi/form/delete', [Saturnus\ItemController::class, 'deleteFormChecksheet'])->name('form-registrasi.delete-checksheet');
        Route::match(['delete', 'post'], '/form-registrasi/delete-checksheet-action', [Saturnus\ItemController::class, 'deleteFormChecksheet']);
        Route::delete('/form-registrasi/{id}', [Saturnus\ItemController::class, 'deleteFormItem'])->name('form-registrasi.delete');
        Route::post('/form-registrasi/comments', [Saturnus\ItemController::class, 'storeComment'])->name('form-registrasi.comments.store');
        Route::post('/form-registrasi/comments/store-action', [Saturnus\ItemController::class, 'storeComment']);
        Route::delete('/form-registrasi/comments/{id}', [Saturnus\ItemController::class, 'deleteComment'])->name('form-registrasi.comments.delete');
    });

    Route::middleware(['permission:saturnus.registrasi.approve,saturnus.registrasi.view'])->group(function() {
        Route::get('/proses-approval', fn(Illuminate\Http\Request $req) => redirect()->route('saturnus.proses_approval', $req->query()))->name('proses-approval');
    });

    Route::middleware(['permission:saturnus.directory.view,saturnus.registrasi.view'])->group(function() {
        Route::get('/data-view', fn(Illuminate\Http\Request $req) => redirect()->route('saturnus.data_view', $req->query()))->name('data-view');
    });

    Route::middleware(['permission:saturnus.unregistrasi.view'])->group(function() {
        Route::get('/form-unregistrasi', fn(Illuminate\Http\Request $req) => redirect()->route('saturnus.form_unregistrasi', $req->query()))->name('form-unregistrasi');
        Route::get('/unregistrasi-approval', fn(Illuminate\Http\Request $req) => redirect()->route('saturnus.unregistrasi_approval', $req->query()));
        Route::get('/unregistrasi-history', fn(Illuminate\Http\Request $req) => redirect()->route('saturnus.unregistrasi_history', $req->query()));
        Route::post('/form-unregistrasi', [Saturnus\FormUnregistrasiController::class, 'storeFormItem'])->name('form-unregistrasi.store');
        Route::post('/form-unregistrasi/store-item', [Saturnus\FormUnregistrasiController::class, 'storeFormItem']);
        Route::post('/form-unregistrasi/approve', [Saturnus\FormUnregistrasiController::class, 'approveForm'])->name('form-unregistrasi.approve');
        Route::post('/form-unregistrasi/approve-action', [Saturnus\FormUnregistrasiController::class, 'approveForm']);
        Route::match(['delete', 'post'], '/form-unregistrasi/form/delete', [Saturnus\FormUnregistrasiController::class, 'deleteFormChecksheet'])->name('form-unregistrasi.delete-checksheet');
        Route::match(['delete', 'post'], '/form-unregistrasi/delete-checksheet-action', [Saturnus\FormUnregistrasiController::class, 'deleteFormChecksheet']);
        Route::delete('/form-unregistrasi/{id}', [Saturnus\FormUnregistrasiController::class, 'deleteFormItem'])->name('form-unregistrasi.delete');
        Route::post('/form-unregistrasi/comments', [Saturnus\FormUnregistrasiController::class, 'storeComment'])->name('form-unregistrasi.comments.store');
        Route::post('/form-unregistrasi/comments/store-action', [Saturnus\FormUnregistrasiController::class, 'storeComment']);
        Route::delete('/form-unregistrasi/comments/{id}', [Saturnus\FormUnregistrasiController::class, 'deleteComment'])->name('form-unregistrasi.comments.delete');
    });

    Route::get('/account-master', fn() => redirect()->route('settings.users.index'))->name('account-master');
    Route::post('/users', [Settings\UserController::class, 'store'])->name('users.store')->middleware('permission:settings.users.manage');
    Route::put('/users/{id}', [Settings\UserController::class, 'update'])->name('users.update')->middleware('permission:settings.users.manage');
    Route::delete('/users/{id}', [Settings\UserController::class, 'destroy'])->name('users.delete')->middleware('permission:settings.users.manage');
    Route::delete('/users/{id}/destroy', [Settings\UserController::class, 'destroy'])->name('users.destroy')->middleware('permission:settings.users.manage');
});
