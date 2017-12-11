<?php

Route::get('/', 'User\Map\MapController@index')
    ->middleware('jwt.check');

Route::post('/old/login', 'User\Map\MapController@login');
Route::get('/old/logout', 'User\Map\MapController@logout');

Route::get('/docs', function () {
    return view('docs.index');
});

Route::get('logs', '\Rap2hpoutre\LaravelLogViewer\LogViewerController@index');

// QR codes legacy url
//Route::get('/scan.php/rent/{bikeNum}', 'Api\v1\QrCodes\QrCodesController@rentBike');
//Route::get('/scan.php/return/{standName}', 'Api\v1\QrCodes\QrCodesController@returnBike');
