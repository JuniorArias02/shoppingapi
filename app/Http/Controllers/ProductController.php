<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use App\Models\ProductoImagen;
use App\Models\ProductoVariante;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Models\Atributo;
use App\Models\AtributoValor;
use App\Models\VarianteAtributo;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $query = Producto::with(['categoria', 'imagenes', 'variantes']);

        // Filtrar por categoría
        if ($request->has('categoria_id')) {
            $query->where('categoria_id', $request->categoria_id);
        }

        // Search by name
        if ($request->has('search')) {
            $query->where('nombre', 'like', '%' . $request->search . '%');
        }

        // Filter by offers (discount > 0)
        if ($request->has('ofertas') && $request->boolean('ofertas')) {
            $query->where('descuento', '>', 0);
        }

        $productos = $query->paginate(20);

        return response()->json($productos);
    }

    public function store(Request $request)
    {
        // Decode variantes if sent as JSON string (common in FormData)
        if ($request->has('variantes') && is_string($request->variantes)) {
            $request->merge(['variantes' => json_decode($request->variantes, true)]);
        }

        $validator = Validator::make($request->all(), [
            'categoria_id' => 'required|exists:categorias,id',
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'marca' => 'nullable|string|max:255',
            'descuento' => 'nullable|integer|min:0|max:100',
            // Allow both file uploads and URL strings (for flexibility)
            'imagenes' => 'nullable|array',
            'imagenes.*' => 'image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'variantes' => 'array',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        DB::beginTransaction();
        try {
            $producto = Producto::create($request->only('categoria_id', 'nombre', 'descripcion', 'marca', 'descuento'));

            // Guardar imágenes
            if ($request->hasFile('imagenes')) {
                foreach ($request->file('imagenes') as $file) {
                    $path = $file->store('products', 'public');
                    ProductoImagen::create([
                        'producto_id' => $producto->id,
                        'url_imagen' => \Illuminate\Support\Facades\Storage::url($path)
                    ]);
                }
            }

            // Guardar variantes iniciales
            if ($request->has('variantes')) {
                foreach ($request->variantes as $varianteData) {
                    $variante = ProductoVariante::create([
                        'producto_id' => $producto->id,
                        'sku' => $varianteData['sku'] ?? null, // Model handles generation if null
                        'precio' => $varianteData['precio'],
                        'stock' => $varianteData['stock'] ?? 0,
                    ]);

                    // Guardar Atributos (Color, Talla)
                    $atributosMap = [
                        'Color' => $varianteData['color'] ?? null,
                        'Talla' => $varianteData['talla'] ?? null
                    ];

                    foreach ($atributosMap as $nombre => $valor) {
                        if ($valor) {
                            $atributo = Atributo::firstOrCreate(['nombre' => $nombre]);
                            $atributoValor = AtributoValor::firstOrCreate([
                                'atributo_id' => $atributo->id,
                                'valor' => $valor
                            ]);

                            VarianteAtributo::create([
                                'producto_variante_id' => $variante->id,
                                'atributo_valor_id' => $atributoValor->id
                            ]);
                        }
                    }
                }
            }

            DB::commit();
            return response()->json($producto->load('imagenes', 'variantes'), 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Error creating product: ' . $e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        $producto = Producto::with(['categoria', 'imagenes', 'variantes.valores.atributo', 'calificaciones.usuario', 'likes'])
            ->withCount('likes')
            ->withAvg('calificaciones', 'rating')
            ->find($id);

        if (!$producto) {
            return response()->json(['message' => 'Product not found'], 404);
        }

        return response()->json($producto);
    }

    public function rate(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'usuario_id' => 'required|exists:usuarios,id',
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $producto = Producto::find($id);
        if (!$producto) {
            return response()->json(['message' => 'Product not found'], 404);
        }

        $rating = \App\Models\ProductRating::create([
            'producto_id' => $id,
            'usuario_id' => $request->usuario_id,
            'rating' => $request->rating,
            'comment' => $request->comment
        ]);

        return response()->json($rating, 201);
    }

    public function toggleLike(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'usuario_id' => 'required|exists:usuarios,id'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $userId = $request->usuario_id;
        $like = \App\Models\ProductLike::where('producto_id', $id)
            ->where('usuario_id', $userId)
            ->first();

        if ($like) {
            $like->delete();
            return response()->json(['message' => 'Unliked', 'liked' => false]);
        } else {
            \App\Models\ProductLike::create([
                'producto_id' => $id,
                'usuario_id' => $userId
            ]);
            return response()->json(['message' => 'Liked', 'liked' => true]);
        }
    }

    public function update(Request $request, $id)
    {
        $producto = Producto::find($id);

        if (!$producto) {
            return response()->json(['message' => 'Product not found'], 404);
        }

        $producto->update($request->only('categoria_id', 'nombre', 'descripcion', 'marca', 'activo', 'descuento'));

        return response()->json($producto);
    }

    public function destroy($id)
    {
        $producto = Producto::find($id);

        if (!$producto) {
            return response()->json(['message' => 'Product not found'], 404);
        }

        $producto->delete();
        return response()->json(['message' => 'Product deleted']);
    }
    public function favorites(Request $request)
    {
        $user = $request->user();

        // Get products liked by user
        // Assuming we want to return products with all their details formatted for ProductCard
        $products = Producto::whereHas('likes', function ($query) use ($user) {
            $query->where('usuario_id', $user->id);
        })
            ->with(['categoria', 'imagenes', 'variantes', 'likes'])
            ->withCount('likes')
            ->paginate(20);

        return response()->json($products);
    }
}
