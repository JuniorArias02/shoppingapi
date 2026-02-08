<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            CategoriaSeeder::class,
        ]);

        $rolAdmin = \App\Models\Rol::where('nombre', 'administrador')->first();

        User::factory()->create([
            'nombre' => 'Admin',
            'email' => 'junior.arias02yt@gmail.com',
            'password' => \Illuminate\Support\Facades\Hash::make('admin123'),
            'rol_id' => $rolAdmin->id,
            'estado' => 'activo',
        ]);
    }
}
