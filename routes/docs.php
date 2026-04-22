<?php

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;

Route::view('swagger', 'swagger')->name('swagger.ui');

Route::get('openapi.yaml', function () {
    return response(
        File::get(base_path('docs/openapi.yaml')),
        200,
        [
            'Content-Type' => 'application/yaml; charset=UTF-8',
        ],
    );
})->name('swagger.spec');
