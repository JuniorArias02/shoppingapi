<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CategoryController extends Controller
{
    // Listar categorías (puedes filtrar por 'parents' para ver solo principales)
    public function index(Request $request)
    {
        // Si se pide 'tree', devolver estructura de árbol
        if ($request->has('tree')) {
            $categories = Categoria::whereNull('parent_id')
                ->with('children')
                ->get();
            return response()->json($categories);
        }

        // Si se pide 'has_products', devolver solo categorías con productos
        if ($request->has('has_products')) {
            $categories = Categoria::has('productos')->get();
            return response()->json($categories);
        }

        $categories = Categoria::all();
        return response()->json($categories);
    }

    // Crear categoría
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nombre' => 'required|string|max:255',
            'parent_id' => 'nullable|exists:categorias,id',
            'activa' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $categoria = Categoria::create($request->all());

        return response()->json($categoria, 201);
    }

    // Mostrar una categoría
    public function show($id)
    {
        $categoria = Categoria::with('children')->find($id);

        if (!$categoria) {
            return response()->json(['message' => 'Category not found'], 404);
        }

        return response()->json($categoria);
    }

    // Actualizar categoría
    public function update(Request $request, $id)
    {
        $categoria = Categoria::find($id);

        if (!$categoria) {
            return response()->json(['message' => 'Category not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'nombre' => 'string|max:255',
            'parent_id' => 'nullable|exists:categorias,id',
            'activa' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $categoria->update($request->all());

        return response()->json($categoria);
    }

    // Eliminar categoría
    public function destroy($id)
    {
        $categoria = Categoria::find($id);

        if (!$categoria) {
            return response()->json(['message' => 'Category not found'], 404);
        }

        $categoria->delete();

        return response()->json(['message' => 'Category deleted']);
    }
}
