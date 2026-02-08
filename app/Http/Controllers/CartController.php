<?php

namespace App\Http\Controllers;

use App\Models\Carrito;
use App\Models\CarritoItem;
use App\Models\ProductoVariante;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CartController extends Controller
{
    /**
     * Get the user's cart items.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        // Ensure user has a cart
        $cart = Carrito::firstOrCreate(['usuario_id' => $user->id]);

        // Load items with product variant details
        // Assuming relationships: 
        // Carrito -> hasMany CarritoItem
        // CarritoItem -> belongsTo ProductoVariante
        // ProductoVariante -> belongsTo Product (or similar, need to verify variant structure for images/names)

        $cartItems = CarritoItem::where('carrito_id', $cart->id)
            ->with(['variante.producto.imagenes', 'variante.producto.categoria', 'variante.valores.atributo']) // Eager load necesary data
            ->get();

        // Transform data for frontend
        $formattedItems = $cartItems->map(function ($item) {
            $variant = $item->variante;
            $product = $variant->producto;

            // Resolve attributes using the 'valores' relationship we saw in ProductDetail thinking
            $attributes = [];
            foreach ($variant->valores as $valor) {
                $attributes[$valor->atributo->nombre] = $valor->valor;
            }

            // Resolve image (prefer variant image if exists? Logic usually falls back to product image)
            // For simplicity, taking first product image.
            $image = $product->imagenes->first();
            $imageUrl = $image ? ($image->url_imagen ?? $image) : null;

            $discount = $product->descuento ?? 0;
            $basePrice = $variant->precio;
            $finalPrice = $basePrice * (1 - ($discount / 100));

            return [
                'id' => $item->id,
                'carrito_id' => $item->carrito_id,
                'producto_variante_id' => $item->producto_variante_id,
                'cantidad' => $item->cantidad,
                'producto_nombre' => $product->nombre,
                'variante_sku' => $variant->sku ?? 'N/A',
                'modelo' => $variant->modelo ?? 'N/A',
                'imagen' => $imageUrl,
                'atributos' => $attributes,
                'precio_unitario' => $finalPrice,
                'precio_base' => $basePrice,
                'descuento' => $discount,
                'stock_max' => $variant->stock,
            ];
        });

        return response()->json($formattedItems);
    }

    /**
     * Add item to cart.
     */
    public function addToCart(Request $request)
    {
        $request->validate([
            'producto_variante_id' => 'required|exists:producto_variantes,id',
            'cantidad' => 'required|integer|min:1',
        ]);

        $user = $request->user();
        $cart = Carrito::firstOrCreate(['usuario_id' => $user->id]);

        $variant = ProductoVariante::find($request->producto_variante_id);

        if ($variant->stock < $request->cantidad) {
            return response()->json(['message' => 'Stock insuficiente'], 400);
        }

        // Check if item exists in cart
        $cartItem = CarritoItem::where('carrito_id', $cart->id)
            ->where('producto_variante_id', $request->producto_variante_id)
            ->first();

        if ($cartItem) {
            // Update quantity
            $newQuantity = $cartItem->cantidad + $request->cantidad;

            if ($variant->stock < $newQuantity) {
                return response()->json(['message' => 'Stock insuficiente para la cantidad total solicitada'], 400);
            }

            $cartItem->cantidad = $newQuantity;
            $cartItem->save();
        } else {
            // Create new item
            $cartItem = CarritoItem::create([
                'carrito_id' => $cart->id,
                'producto_variante_id' => $request->producto_variante_id,
                'cantidad' => $request->cantidad,
            ]);
        }

        return response()->json(['message' => 'Producto agregado al carrito', 'item' => $cartItem]);
    }

    /**
     * Update item quantity.
     */
    public function updateItem(Request $request, $itemId)
    {
        $request->validate([
            'cantidad' => 'required|integer|min:1',
        ]);

        $user = $request->user();
        // Verify item belongs to user's cart
        $cart = Carrito::where('usuario_id', $user->id)->first();

        if (!$cart) {
            return response()->json(['message' => 'Carrito no encontrado'], 404);
        }

        $cartItem = CarritoItem::where('id', $itemId)
            ->where('carrito_id', $cart->id)
            ->first();

        if (!$cartItem) {
            return response()->json(['message' => 'Item no encontrado'], 404);
        }

        // Check stock
        $variant = $cartItem->variante; // Assumes relationship exists in model, verified in previous step
        if ($variant->stock < $request->cantidad) {
            return response()->json(['message' => 'Stock insuficiente'], 400);
        }

        $cartItem->cantidad = $request->cantidad;
        $cartItem->save();

        return response()->json(['message' => 'Cantidad actualizada', 'item' => $cartItem]);
    }

    /**
     * Remove item from cart.
     */
    public function removeItem(Request $request, $itemId)
    {
        $user = $request->user();
        $cart = Carrito::where('usuario_id', $user->id)->first();

        if (!$cart) {
            return response()->json(['message' => 'Carrito no encontrado'], 404);
        }

        $cartItem = CarritoItem::where('id', $itemId)
            ->where('carrito_id', $cart->id)
            ->first();

        if ($cartItem) {
            $cartItem->delete();
            return response()->json(['message' => 'Item eliminado']);
        }

        return response()->json(['message' => 'Item no encontrado'], 404);
    }
}
