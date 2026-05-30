<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'success' => true,
        'message' => 'Welcome to the API',
        'data' => [
            'version' => '1.0.0',
            'documentation' => 'https://api.example.com/docs',
        ],
    ]);
});
