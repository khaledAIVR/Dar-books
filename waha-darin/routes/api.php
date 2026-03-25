<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/


/*
|--------------------------------------------------------------------------
| Private data
|--------------------------------------------------------------------------
*/
Route::group(['middleware' => 'auth:api'], function () {
    Route::post('logout', 'Auth\LoginController@logout');
    Route::post("/import-books", "BookController@importBooks");
//    Route::post("/update-book", "BookController@updateBook");

    Route::get('/user', function (Request $request) {
        $user = $request->user();
        $user->load(['subscription' => function ($q) {
            $q->select('id', 'user_id', 'plan_id', 'status', 'start', 'end');
        }]);
        return $user;
    });


    Route::group(['prefix' => 'settings', 'namespace'=>'Settings'], function () {
        Route::patch('/profile', 'ProfileController@update');
        Route::patch('/password', 'PasswordController@update');
        Route::patch('/categories', 'ProfileController@categories');
        Route::patch('/authors', 'ProfileController@authors');
    });

    Route::group(['prefix' => 'cart'], function () {
        Route::get('/', 'CartController@index');
        Route::patch('/{bookId}', 'CartController@update');
        Route::delete('/{bookId}', 'CartController@delete');
    });

    Route::group(['prefix' => 'favourite'], function () {
        Route::get('/', 'FavouriteController@index');
        Route::patch('/{bookId}', 'FavouriteController@update');
        Route::delete('/{bookId}', 'FavouriteController@delete');
    });

    Route::group(['prefix' => 'subscriptions'], function () {
        Route::post('/', 'SubscriptionController@create');
    });

    Route::group(['prefix' => 'orders'], function () {
        Route::get('/', 'OrderController@index');
        Route::post('/', 'OrderController@create');
        Route::post('/{order}/return-shipment', 'OrderController@addReturnShipment');
    });
});


/*
|--------------------------------------------------------------------------
| Auth Stuff
|--------------------------------------------------------------------------
*/

Route::group(['middleware' => 'guest:api', 'namespace' => 'Auth'], function () {
    Route::post('login', 'LoginController@login');
    Route::post('register', 'RegisterController@register');

    Route::post('password/email', 'ForgotPasswordController@sendResetLinkEmail');
    Route::post('password/reset', 'ResetPasswordController@reset');

    Route::post('email/verify/{id}/{hash}', 'VerificationController@verify')->name('verification.verify');
    Route::post('email/resend', 'VerificationController@resend');

    Route::post('oauth/{driver}', 'OAuthController@redirectToProvider');
    Route::get('oauth/{driver}/callback', 'OAuthController@handleProviderCallback')->name('oauth.callback');
});

/*
|--------------------------------------------------------------------------
| Public Data
|--------------------------------------------------------------------------
*/

Route::group(['prefix' => 'categories'], function () {
    Route::get('/', 'CategoryController@index')->name('categories');
    Route::get('/{category}', 'CategoryController@show')->name('category');
});

Route::group(['prefix' => 'books'], function () {
    Route::get('/', 'BookController@index')->name('books');
    Route::get('/{book}', 'BookController@show')->name('book');
});

Route::group(['prefix' => 'events'], function () {
    Route::get('/', 'EventController@index')->name('events');
    Route::get('/{event}', 'EventController@show')->name('event');
});

Route::group(['prefix' => 'authors'], function () {
    Route::get('/', 'AuthorController@index')->name('authors');
    Route::get('/{author}', 'AuthorController@show')->name('author');
});

Route::group(['prefix' => 'publishers'], function () {
    Route::get('/', 'PublisherController@index')->name('publishers');
    Route::get('/{publisher}', 'PublisherController@show')->name('publisher');
});


Route::group(['prefix' => 'plans'], function () {
    Route::get('/', 'PlanController@index');
    Route::get('/{plan}', 'PlanController@show');
});

Route::get('bank-details', 'BankAccountDetailController@show');

/*
|--------------------------------------------------------------------------
| Payment Stuff
|--------------------------------------------------------------------------
*/

Route::group(['middleware' => 'guest:api'], function () {
    Route::post('payment-confirmation', 'PlanController@handleStripeConfirmation')->name('stripe.confirmation');
});
