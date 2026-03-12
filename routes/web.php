<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Mail;
use App\Models\User;


Route::get('/', function () {
    return view('welcome');
});

// Storage Bridge: Serves storage files even if the symlink is broken (Common for Hostinger)
Route::get('/storage/{path}', function ($path) {
    $path = storage_path('app/public/' . $path);

    if (!file_exists($path)) {
        abort(404);
    }

    $file = file_get_contents($path);
    $type = mime_content_type($path);

    return response($file)->header('Content-Type', $type);
})->where('path', '.*');
