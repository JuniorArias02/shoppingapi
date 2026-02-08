<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>¡No olvides tu carrito!</title>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; background-color: #f4f4f4; margin: 0; padding: 0; color: #333; }
        .container { max-width: 600px; margin: 30px auto; background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        .header { background: linear-gradient(135deg, #D9258B 0%, #a01d66 100%); padding: 40px 20px; text-align: center; color: #ffffff; }
        .header h1 { margin: 0; font-size: 26px; font-weight: 700; margin-bottom: 5px; }
        .header p { margin: 0; opacity: 0.9; font-size: 16px; }
        
        .content { padding: 40px 30px; text-align: center; }
        .intro-text { font-size: 16px; line-height: 1.6; color: #475569; margin-bottom: 30px; }
        
        .cart-preview { background-color: #f8fafc; border-radius: 12px; padding: 20px; margin-bottom: 30px; text-align: left; border: 1px solid #e2e8f0; }
        .cart-item { display: flex; align-items: center; padding: 10px 0; border-bottom: 1px solid #e2e8f0; }
        .cart-item:last-child { border-bottom: none; }
        .item-info { flex: 1; margin-left: 15px; }
        .item-name { font-weight: 600; color: #0f172a; display: block; }
        .item-price { color: #D9258B; font-weight: 700; font-size: 14px; }
        
        .btn { display: inline-block; background-color: #0f172a; color: #ffffff; padding: 15px 35px; text-decoration: none; border-radius: 50px; font-weight: 700; font-size: 16px; transition: transform 0.2s, box-shadow 0.2s; box-shadow: 0 4px 6px rgba(15, 23, 42, 0.2); }
        .btn:hover { transform: translateY(-2px); box-shadow: 0 6px 12px rgba(15, 23, 42, 0.3); background-color: #1e293b; }
        
        .footer { background-color: #f1f5f9; padding: 25px; text-align: center; font-size: 12px; color: #94a3b8; }
        .social-links { margin-top: 15px; }
        .social-links a { color: #64748b; text-decoration: none; margin: 0 10px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <img src="{{ $message->embed(public_path('logo.jpg')) }}" alt="Venezia Pizzas" style="max-width: 150px; margin-bottom: 20px; border-radius: 10px;">
            <h1>¡Tu pizza te está esperando! 🍕</h1>
            <p>Notamos que dejaste algo delicioso en tu carrito.</p>
        </div>
        
        <div class="content">
            <p class="intro-text">
                Hola <strong>{{ $user->nombre }}</strong>,<br>
                Guardamos tus productos para que no tengas que buscarlos de nuevo. <br>
                ¡Completa tu pedido antes de que se agoten!
            </p>

            <div class="cart-preview">
                @foreach($carrito->items->take(3) as $item)
                <div class="cart-item">
                    <!-- Placeholder for image if needed, keeping it simple for email client compatibility -->
                    <div style="width: 50px; height: 50px; background-color: #ddd; border-radius: 8px; overflow: hidden;">
                        @if($item->variante->producto->imagenes->first())
                            <img src="{{ $message->embed(public_path($item->variante->producto->imagenes->first()->url_imagen)) }}" style="width: 100%; height: 100%; object-fit: cover;" alt="Prod">
                        @else
                            <div style="width: 100%; height: 100%; background-color: #cbd5e1;"></div>
                        @endif
                    </div>
                    <div class="item-info">
                        <span class="item-name">{{ $item->variante->producto->nombre }}</span>
                        <span class="item-price">${{ number_format($item->variante->precio, 0) }}</span>
                    </div>
                </div>
                @endforeach
                @if($carrito->items->count() > 3)
                    <div style="text-align: center; padding-top: 10px; font-size: 12px; color: #64748b;">
                        y {{ $carrito->items->count() - 3 }} productos más...
                    </div>
                @endif
            </div>

            <a href="{{ url('/') }}/cart" class="btn">Continuar Compra</a>
        </div>

        <div class="footer">
            <p>&copy; {{ date('Y') }} Venezia Pizzas & Style.</p>
            <p>¿No fuiste tú? Puedes ignorar este correo.</p>
        </div>
    </div>
</body>
</html>
