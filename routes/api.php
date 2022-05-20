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

Route::group(['prefix' => 'auth'], function () {
  Route::post('login', 'AuthController@login');
  Route::post('register', 'AuthController@register');

  Route::group(['middleware' => 'auth:sanctum'], function() {
    Route::post('logout', 'AuthController@logout');
    Route::get('me', 'AuthController@user');
  });
});

// all routes are authenticated by sanctum
Route::group(['middleware' => ['auth:sanctum']], function () {
  // if authenticated, check user is clear to process (active and unlocked)
  Route::group(['middleware' => ['check']], function () {

    Route::group(['prefix' => 'settings'], function () {
        Route::get('/', ['middleware' => ['can:read_setting'], 'uses' => 'SettingController@all']);
        Route::get('/{id}', ['middleware' => ['can:read_setting'], 'uses' => 'SettingController@get']);

        Route::post('/', ['middleware' => ['can:create_setting'], 'uses' => 'SettingController@add']);
        Route::put('/{id}', ['middleware' => ['can:update_setting'], 'uses' => 'SettingController@put']);
        Route::delete('/{id}', ['middleware' => ['can:delete_setting'], 'uses' => 'SettingController@remove']);
    });

    Route::group(['prefix' => 'users'], function () {
      Route::get('/', ['uses' => 'UserController@all']);
      Route::get('/{id}', ['uses' => 'UserController@get']);

      /** 'can' middleware for authorization of database insert, update and delete operations
       * 'can' middleware with ability name parameter passes to Authorize middleware class
       */
      Route::post('/', ['middleware' => ['can:create_user'], 'uses' => 'UserController@add']);
      Route::put('/{id}', ['middleware' => ['can:update_user'], 'uses' => 'UserController@put']);
      Route::delete('/{id}', ['middleware' => ['can:delete_user'], 'uses' => 'UserController@remove']);
    });

    Route::group(['prefix' => 'patients'], function () {
        Route::get('/', ['middleware' => ['can:read_patient'], 'uses' => 'PatientController@all']);
        Route::get('/{id}', ['middleware' => ['can:read_patient'], 'uses' => 'PatientController@get']);

        Route::post('/', ['middleware' => ['can:create_patient'], 'uses' => 'PatientController@add']);
        Route::put('/{id}', ['middleware' => ['can:update_patient'], 'uses' => 'PatientController@put']);
        Route::delete('/{id}', ['middleware' => ['can:delete_patient'], 'uses' => 'PatientController@remove']);
    });

    Route::group(['prefix' => 'categories'], function () {
      Route::get('/', ['middleware' => ['can:read_category'], 'uses' => 'CategoryController@all']);
      Route::get('/{id}', ['middleware' => ['can:read_category'], 'uses' => 'CategoryController@get']);

      Route::post('/', ['middleware' => ['can:create_category'], 'uses' => 'CategoryController@add']);
      Route::put('/{id}', ['middleware' => ['can:update_category'], 'uses' => 'CategoryController@put']);
      Route::delete('/{id}', ['middleware' => ['can:delete_category'], 'uses' => 'CategoryController@remove']);
    });

    Route::group(['prefix' => 'units'], function () {
      Route::get('/', ['middleware' => ['can:read_unit'], 'uses' => 'UnitController@all']);
      Route::get('/{id}', ['middleware' => ['can:read_unit'], 'uses' => 'UnitController@get']);

      Route::post('/', ['middleware' => ['can:create_unit'], 'uses' => 'UnitController@add']);
      Route::put('/{id}', ['middleware' => ['can:update_unit'], 'uses' => 'UnitController@put']);
      Route::delete('/{id}', ['middleware' => ['can:delete_unit'], 'uses' => 'UnitController@remove']);
    });

    Route::group(['prefix' => 'items'], function () {
      Route::get('/', ['middleware' => ['can:read_item'], 'uses' => 'ItemController@all']);
      Route::get('/{id}', ['middleware' => ['can:read_item'], 'uses' => 'ItemController@get']);

      Route::post('/', ['middleware' => ['can:create_item'], 'uses' => 'ItemController@add']);
      Route::put('/{id}', ['middleware' => ['can:update_item'], 'uses' => 'ItemController@put']);
      Route::delete('/{id}', ['middleware' => ['can:delete_item'], 'uses' => 'ItemController@remove']);
    });


    Route::group(['prefix' => 'bills'], function () {
      Route::get('/', ['middleware' => ['can:read_bill'], 'uses' => 'BillController@all']);
      Route::get('/{id}', ['middleware' => ['can:read_bill'], 'uses' => 'BillController@get']);

      Route::post('/', ['middleware' => ['can:create_bill'], 'uses' => 'BillController@add']);
      Route::put('/{id}', ['middleware' => ['can:update_bill'], 'uses' => 'BillController@put']);
      Route::delete('/{id}', ['middleware' => ['can:delete_bill'], 'uses' => 'BillController@remove']);
    });

    Route::group(['prefix' => 'daily-closings'], function () {
      Route::get('/', ['middleware' => ['can:read_daily_closing'], 'uses' => 'DailyClosingController@all']);
      Route::get('/{id}', ['middleware' => ['can:read_daily_closing'], 'uses' => 'DailyClosingController@get']);

      Route::post('/', ['middleware' => ['can:create_daily_closing'], 'uses' => 'DailyClosingController@add']);
      Route::put('/{id}', ['middleware' => ['can:update_daily_closing'], 'uses' => 'DailyClosingController@put']);
      Route::delete('/{id}', ['middleware' => ['can:delete_daily_closing'], 'uses' => 'DailyClosingController@remove']);
    });

    Route::get('/options/{object}', ['middleware' => ['auth:sanctum'], 'uses' => 'OptionController@get']);
    Route::get('/options', ['middleware' => ['auth:sanctum'], 'uses' => 'OptionController@getMultiple']);

  });
});
