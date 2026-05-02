<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('{path}', function () {
    return response(file_get_contents(public_path('_nuxt/index.html')))
        ->header('Content-Type', 'text/html; charset=UTF-8')
        ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
        ->header('Pragma', 'no-cache')
        ->header('Expires', '0');
})->where('path', '^((?!admin|api).)*$');

/* Voyager Admin panel routes */
Route::group(['prefix' => 'admin', 'middleware' => ['superadmin.restrict']], function () {
    Voyager::routes();

    // Override voyager assets route to serve font files with correct MIME types.
    Route::get('voyager-assets', [\App\Http\Controllers\Admin\VoyagerAssetsController::class, 'assets'])
        ->name('voyager.voyager_assets');
});

Route::group(['prefix' => 'admin', 'middleware' => ['admin.user', 'superadmin.restrict']], function () {
    Route::get('weekly-orders', function () {
        return Voyager::view('voyager::weekly-orders.index');
    })->name('voyager.weekly-orders.page');

    Route::get('weekly-orders/data', 'Admin\WeeklyOrderController@index')->name('voyager.weekly-orders.data');
    Route::post('weekly-orders/{order}/confirm', 'Admin\WeeklyOrderController@confirm')->name('voyager.weekly-orders.confirm');
    Route::post('weekly-orders/{order}/deliver', 'Admin\WeeklyOrderController@deliver')->name('voyager.weekly-orders.deliver');
    Route::post('weekly-orders/{order}/returned', 'Admin\WeeklyOrderController@markReturned')->name('voyager.weekly-orders.returned');
    Route::post('weekly-orders/{order}/cancel', 'Admin\WeeklyOrderController@cancel')->name('voyager.weekly-orders.cancel');

    // Keep Voyager's default /admin/subscriptions BREAD page.
    // Custom workflow page:
    Route::get('manage-subscriptions', function () {
        return Voyager::view('voyager::manage-subscriptions.index');
    })->name('voyager.manage-subscriptions.page');

    Route::get('manage-subscriptions/data', 'Admin\SubscriptionController@index')->name('voyager.manage-subscriptions.data');
    Route::post('manage-subscriptions/{subscription}/activate', 'Admin\SubscriptionController@activate')->name('voyager.manage-subscriptions.activate');
    Route::post('manage-subscriptions/{subscription}/deactivate', 'Admin\SubscriptionController@deactivate')->name('voyager.manage-subscriptions.deactivate');

    Route::get('book-import', 'Admin\BookImportController@index')->name('voyager.book-import.page');
    Route::post('book-import', 'Admin\BookImportController@import')->name('voyager.book-import.import');
});
