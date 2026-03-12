<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Mail;
use App\Models\User;


Route::get('/', function () {
    return view('welcome');
});

/**
 * PUENTE DE ALMACENAMIENTO (SOLUCIÓN HOSTINGER)
 * --------------------------------------------
 * Usamos esta ruta para capturar cualquier pedido a /storage/ y servirlo
 * desde la carpeta protegida de Laravel, evitando problemas de symlinks.
 */
Route::get('/storage/{path}', function ($path) {
    $path = storage_path('app/public/' . $path);

    if (!file_exists($path)) {
        abort(404);
    }

    $file = file_get_contents($path);
    $type = mime_content_type($path);

    return response($file)->header('Content-Type', $type);
})->where('path', '.*');
