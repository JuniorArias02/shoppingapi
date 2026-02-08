<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Categoria;

class CategoriaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categorias = [
            [
                'nombre' => 'Tecnología',
                'activa' => true,
                'children' => [
                    ['nombre' => 'Smartphones', 'activa' => true],
                    ['nombre' => 'Laptops', 'activa' => true],
                    ['nombre' => 'Accesorios', 'activa' => true],
                    ['nombre' => 'Audio', 'activa' => true],
                ]
            ],
            [
                'nombre' => 'Moda',
                'activa' => true,
                'children' => [
                    ['nombre' => 'Hombres', 'activa' => true],
                    ['nombre' => 'Mujeres', 'activa' => true],
                    ['nombre' => 'Niños', 'activa' => true],
                    ['nombre' => 'Calzado', 'activa' => true],
                ]
            ],
            [
                'nombre' => 'Hogar',
                'activa' => true,
                'children' => [
                    ['nombre' => 'Muebles', 'activa' => true],
                    ['nombre' => 'Cocina', 'activa' => true],
                    ['nombre' => 'Decoración', 'activa' => true],
                ]
            ],
            [
                'nombre' => 'Deportes',
                'activa' => true,
                'children' => [
                    ['nombre' => 'Fútbol', 'activa' => true],
                    ['nombre' => 'Gimnasio', 'activa' => true],
                    ['nombre' => 'Ciclismo', 'activa' => true],
                ]
            ],
            [
                'nombre' => 'Juguetes',
                'activa' => true,
                'children' => []
            ]
        ];

        foreach ($categorias as $catData) {
            $children = $catData['children'];
            unset($catData['children']);

            $parent = Categoria::create($catData);

            foreach ($children as $childData) {
                $childData['parent_id'] = $parent->id;
                Categoria::create($childData);
            }
        }
    }
}
