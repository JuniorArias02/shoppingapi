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
 * En Hostinger, el comando 'storage:link' suele fallar o dar problemas con subdominios.
 * Esta ruta intercepta cualquier pedido a /storage/ y entrega el archivo manualmente
 * buscando en la carpeta privada 'storage/app/public'.
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
