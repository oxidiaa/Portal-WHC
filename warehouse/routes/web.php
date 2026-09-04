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
    Route::prefix('mars')->name('mars.')->group(function () {
        Route::get('/', [Mars\DashboardController::class, 'index'])->name('dashboard');

        // Item Master
        Route::get('/item-master', [Mars\ItemMasterController::class, 'index'])->name('item_master.index');
        Route::post('/item-master', [Mars\ItemMasterController::class, 'store'])->name('item_master.store');
        Route::put('/item-master/{id}', [Mars\ItemMasterController::class, 'update'])->name('item_master.update');
        Route::delete('/item-master/{id}', [Mars\ItemMasterController::class, 'destroy'])->name('item_master.destroy');
        Route::post('/item-master/import', [Mars\ItemMasterController::class, 'importExcel'])->name('item_master.import');
        Route::post('/item-master/delete-all', [Mars\ItemMasterController::class, 'deleteAll'])->name('item_master.delete_all');
        Route::match(['put', 'post'], '/item-master/{id}/update-note', [Mars\ItemMasterController::class, 'updateNote'])->name('item_master.update_note');
        Route::match(['put', 'post'], '/item-master/note/{id}', [Mars\ItemMasterController::class, 'updateNote']);
        Route::match(['put', 'post'], '/item-master/{id}/update-follow', [Mars\ItemMasterController::class, 'updateFollow'])->name('item_master.update_follow');
        Route::match(['put', 'post'], '/item-master/{id}/update-pengiriman-tanggal', [Mars\ItemMasterController::class, 'updatePengirimanTanggal'])->name('item_master.update_pengiriman_tanggal');
        Route::match(['put', 'post'], '/item-master/{id}/update-request-whc', [Mars\ItemMasterController::class, 'updateRequestWhc'])->name('item_master.update_request_whc');
        Route::match(['put', 'post'], '/item-master/{id}/update-request-whc-date', [Mars\ItemMasterController::class, 'updateRequestWhcDate'])->name('item_master.update_request_whc_date');
        Route::get('/item-master/export', [Mars\ItemMasterController::class, 'export'])->name('item_master.export');

        // Data PO
        Route::get('/data-po', [Mars\DataPOController::class, 'index'])->name('data_po.index');
        Route::post('/data-po/import', [Mars\DataPOController::class, 'importExcel'])->name('data_po.import');
        Route::post('/data-po/delete-all', [Mars\DataPOController::class, 'deleteAll'])->name('data_po.delete_all');
        Route::delete('/data-po/{id}', [Mars\DataPOController::class, 'destroy'])->name('data_po.destroy');
        Route::post('/data-po/{id}/delete', [Mars\DataPOController::class, 'destroy'])->name('data_po.post_destroy');

        // Item Outstanding
        Route::get('/item-outstanding', [Mars\OutstandingController::class, 'index'])->name('item_outstanding.index');
        Route::post('/item-outstanding', [Mars\OutstandingController::class, 'store'])->name('item_outstanding.store');
        Route::post('/item-outstanding/import', [Mars\OutstandingController::class, 'importExcel'])->name('item_outstanding.import');
        Route::match(['put', 'post'], '/item-outstanding/{id}/update-note', [Mars\OutstandingController::class, 'updateNote'])->name('item_outstanding.update_note');
        Route::match(['put', 'post'], '/item-outstanding/note/{id}', [Mars\OutstandingController::class, 'updateNote']);
        Route::match(['put', 'post'], '/item-outstanding/{id}/update-follow', [Mars\OutstandingController::class, 'updateFollow'])->name('item_outstanding.update_follow');
        Route::match(['put', 'post'], '/item-outstanding/update-follow/{id}', [Mars\OutstandingController::class, 'updateFollow']);
        Route::match(['put', 'post'], '/item-outstanding/{id}/update-pengiriman-tanggal', [Mars\OutstandingController::class, 'updatePengirimanTanggal'])->name('item_outstanding.update_pengiriman_tanggal');
        Route::match(['put', 'post'], '/item-outstanding/update-pengiriman-tanggal/{id}', [Mars\OutstandingController::class, 'updatePengirimanTanggal']);
        Route::match(['put', 'post'], '/item-outstanding/{id}/update-request-whc', [Mars\OutstandingController::class, 'updateRequestWhc'])->name('item_outstanding.update_request_whc');
        Route::match(['put', 'post'], '/item-outstanding/update-request-whc/{id}', [Mars\OutstandingController::class, 'updateRequestWhc']);
        Route::match(['put', 'post'], '/item-outstanding/{id}/update-request-whc-date', [Mars\OutstandingController::class, 'updateRequestWhcDate'])->name('item_outstanding.update_request_whc_date');
        Route::match(['put', 'post'], '/item-outstanding/update-request-whc-date/{id}', [Mars\OutstandingController::class, 'updateRequestWhcDate']);
        Route::match(['put', 'post'], '/item-outstanding/{id}/update-follow-up', [Mars\OutstandingController::class, 'updateFollowUp'])->name('item_outstanding.update_follow_up');
        Route::match(['put', 'post'], '/item-outstanding/update-follow-up/{id}', [Mars\OutstandingController::class, 'updateFollowUp']);

        // Item Minim
        Route::get('/item-minim', [Mars\ItemMinimController::class, 'index'])->name('item_minim.index');
        Route::put('/item-minim/{id}', [Mars\ItemMinimController::class, 'update'])->name('item_minim.update');
        Route::delete('/item-minim/{id}', [Mars\ItemMinimController::class, 'destroy'])->name('item_minim.destroy');
        Route::match(['put', 'post'], '/item-minim/{id}/update-note', [Mars\ItemMinimController::class, 'updateNote'])->name('item_minim.update_note');
        Route::match(['put', 'post'], '/item-minim/note/{id}', [Mars\ItemMinimController::class, 'updateNote']);
        Route::match(['put', 'post'], '/item-minim/{id}/update-follow-up', [Mars\ItemMinimController::class, 'updateFollowUp'])->name('item_minim.update_follow_up');
        Route::match(['put', 'post'], '/item-minim/update-follow-up/{id}', [Mars\ItemMinimController::class, 'updateFollowUp']);
        Route::get('/item-minim/export', [Mars\ItemMinimController::class, 'export'])->name('item_minim.export');

        // Kedatangan Barang
        Route::get('/kedatangan-barang', [Mars\KedatanganBarangController::class, 'index'])->name('kedatangan_barang.index');
        Route::post('/kedatangan-barang/import', [Mars\KedatanganBarangController::class, 'importExcel'])->name('kedatangan_barang.import');

        // History
        Route::get('/history', [Mars\HistoryController::class, 'index'])->name('history.index');
        Route::put('/history/{id}', [Mars\HistoryController::class, 'update'])->name('history.update');
        Route::delete('/history/{id}', [Mars\HistoryController::class, 'destroy'])->name('history.destroy');
        Route::post('/history/bulk-delete', [Mars\HistoryController::class, 'bulkDestroy'])->name('history.bulk_destroy');
        Route::get('/history/export', [Mars\HistoryController::class, 'export'])->name('history.export');
    });

    /*
    |--------------------------------------------------------------------------
    | SATURNUS MODULE ROUTES (Prefix: /saturnus)
    |--------------------------------------------------------------------------
    */
    Route::prefix('saturnus')->name('saturnus.')->group(function () {
        Route::get('/', [Saturnus\ItemController::class, 'index'])->name('dashboard');
        Route::post('/items', [Saturnus\ItemController::class, 'store'])->name('items.store');
        Route::post('/items/{id}/unregister', [Saturnus\ItemController::class, 'unregister'])->name('items.unregister');

        // Form Registrasi & Dedicated Views
        Route::get('/form-registrasi', [Saturnus\ItemController::class, 'formRegistrasi'])->name('form_registrasi');
        Route::get('/proses-approval', [Saturnus\ItemController::class, 'prosesApproval'])->name('proses_approval');
        Route::get('/data-view', [Saturnus\ItemController::class, 'dataView'])->name('data_view');
        Route::get('/account-master', [Saturnus\ItemController::class, 'accountMaster'])->name('account_master');
        Route::post('/form-registrasi/item', [Saturnus\ItemController::class, 'storeFormItem'])->name('form_registrasi.item.store');
        Route::post('/form-registrasi/approve', [Saturnus\ItemController::class, 'approveForm'])->name('form_registrasi.approve');
        Route::match(['delete', 'post'], '/form-registrasi/delete-checksheet', [Saturnus\ItemController::class, 'deleteFormChecksheet'])->name('form_registrasi.delete_checksheet');
        Route::delete('/form-registrasi/item/{id}', [Saturnus\ItemController::class, 'deleteFormItem'])->name('form_registrasi.item.delete');
        Route::post('/form-registrasi/comment', [Saturnus\ItemController::class, 'storeComment'])->name('form_registrasi.comment.store');
        Route::delete('/form-registrasi/comment/{id}', [Saturnus\ItemController::class, 'deleteComment'])->name('form_registrasi.comment.delete');

        // Form Unregistrasi
        Route::get('/form-unregistrasi', [Saturnus\FormUnregistrasiController::class, 'formUnregistrasi'])->name('form_unregistrasi');
        Route::post('/form-unregistrasi/item', [Saturnus\FormUnregistrasiController::class, 'storeFormItem'])->name('form_unregistrasi.item.store');
        Route::post('/form-unregistrasi/approve', [Saturnus\FormUnregistrasiController::class, 'approveForm'])->name('form_unregistrasi.approve');
        Route::match(['delete', 'post'], '/form-unregistrasi/delete-checksheet', [Saturnus\FormUnregistrasiController::class, 'deleteFormChecksheet'])->name('form_unregistrasi.delete_checksheet');
        Route::delete('/form-unregistrasi/item/{id}', [Saturnus\FormUnregistrasiController::class, 'deleteFormItem'])->name('form_unregistrasi.item.delete');
        Route::post('/form-unregistrasi/comment', [Saturnus\FormUnregistrasiController::class, 'storeComment'])->name('form_unregistrasi.comment.store');
        Route::delete('/form-unregistrasi/comment/{id}', [Saturnus\FormUnregistrasiController::class, 'deleteComment'])->name('form_unregistrasi.comment.delete');
    });

    /*
    |--------------------------------------------------------------------------
    | SETTINGS & USER MANAGEMENT ROUTES
    |--------------------------------------------------------------------------
    */
    Route::prefix('settings')->name('settings.')->group(function () {
        Route::get('/users', [Settings\UserController::class, 'index'])->name('users.index');
        Route::post('/users', [Settings\UserController::class, 'store'])->name('users.store');
        Route::put('/users/{id}', [Settings\UserController::class, 'update'])->name('users.update');
        Route::delete('/users/{id}', [Settings\UserController::class, 'destroy'])->name('users.destroy');

        Route::get('/roles', [Settings\RolePermissionController::class, 'index'])->name('roles.index');
        Route::put('/roles/{id}', [Settings\RolePermissionController::class, 'update'])->name('roles.update');
    });

    /*
    |--------------------------------------------------------------------------
    | LEGACY & RETRO-COMPATIBILITY ROUTES (ZERO BREAKING CHANGES)
    |--------------------------------------------------------------------------
    */
    // MARS Legacy Underscore & Hyphen Endpoints
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

    Route::prefix('kedatangan_barang')->group(function() {
        Route::get('/', fn() => redirect()->route('mars.kedatangan_barang.index'))->name('kedatangan_barang.index');
        Route::post('/import-excel', [Mars\KedatanganBarangController::class, 'importExcel'])->name('kedatangan_barang.importExcel');
    });
    Route::prefix('kedatangan-barang')->group(function() {
        Route::get('/', fn() => redirect()->route('mars.kedatangan_barang.index'));
        Route::post('/import-excel', [Mars\KedatanganBarangController::class, 'importExcel']);
    });

    Route::prefix('history')->group(function() {
        Route::get('/', fn() => redirect()->route('mars.history.index'))->name('history.index');
        Route::get('/export', [Mars\HistoryController::class, 'export'])->name('history.export');
        Route::get('/export-excel', [Mars\HistoryController::class, 'export']);
        Route::post('/bulk-destroy', [Mars\HistoryController::class, 'bulkDestroy'])->name('history.bulkDestroy');
        Route::post('/bulk-destroy-items', [Mars\HistoryController::class, 'bulkDestroy']);
        Route::match(['put', 'post'], '/{id}', [Mars\HistoryController::class, 'update'])->name('history.update');
        Route::match(['delete', 'post'], '/{id}/destroy', [Mars\HistoryController::class, 'destroy'])->name('history.destroy');
    });

    // SATURNUS Legacy Endpoints
    Route::get('/form-registrasi', fn(Illuminate\Http\Request $req) => redirect()->route('saturnus.form_registrasi', $req->query()))->name('form-registrasi');
    Route::get('/proses-approval', fn(Illuminate\Http\Request $req) => redirect()->route('saturnus.proses_approval', $req->query()))->name('proses-approval');
    Route::get('/data-view', fn(Illuminate\Http\Request $req) => redirect()->route('saturnus.data_view', $req->query()))->name('data-view');
    Route::get('/account-master', fn(Illuminate\Http\Request $req) => redirect()->route('saturnus.account_master', $req->query()))->name('account-master');
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

    Route::get('/form-unregistrasi', fn(Illuminate\Http\Request $req) => redirect()->route('saturnus.form_unregistrasi', $req->query()))->name('form-unregistrasi');
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

    Route::post('/users', [Settings\UserController::class, 'store'])->name('users.store');
    Route::put('/users/{id}', [Settings\UserController::class, 'update'])->name('users.update');
    Route::delete('/users/{id}', [Settings\UserController::class, 'destroy'])->name('users.delete');
    Route::delete('/users/{id}/destroy', [Settings\UserController::class, 'destroy'])->name('users.destroy');
});
