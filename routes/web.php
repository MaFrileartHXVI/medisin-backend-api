<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'app' => 'Medisin SINTASI REST API',
        'status' => 'online',
        'api_docs' => '/api',
    ]);
});
