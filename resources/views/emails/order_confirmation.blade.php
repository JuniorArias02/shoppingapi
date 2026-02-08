<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Confirmación de Pedido</title>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; background-color: #f4f4f4; margin: 0; padding: 0; color: #333; }
        .container { max-width: 600px; margin: 30px auto; background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .header { background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); padding: 30px; text-align: center; color: #ffffff; }
        .header h1 { margin: 0; font-size: 24px; font-weight: 700; letter-spacing: 0.5px; }
        .header p { margin: 10px 0 0; opacity: 0.8; font-size: 14px; }
        .content { padding: 30px; }
        .order-info { margin-bottom: 30px; border-bottom: 1px solid #eee; padding-bottom: 20px; }
        .order-info h2 { font-size: 18px; color: #0f172a; margin-top: 0; }
        .order-info p { margin: 5px 0; font-size: 14px; color: #64748b; }
        
        table { width: 100%; border-collapse: collapse; margin-bottom: 25px; }
        th { text-align: left; padding: 12px; border-bottom: 2px solid #f1f5f9; color: #64748b; font-size: 12px; text-transform: uppercase; letter-spacing: 1px; }
        td { padding: 12px; border-bottom: 1px solid #f1f5f9; font-size: 14px; vertical-align: middle; }
        .product-name { font-weight: 600; color: #0f172a; }
        .product-variant { font-size: 12px; color: #94a3b8; display: block; }
        .total-row td { border-top: 2px solid #0f172a; border-bottom: none; font-weight: 700; font-size: 16px; color: #0f172a; padding-top: 15px; }
        
        .shipping-info { background-color: #f8fafc; padding: 20px; border-radius: 8px; font-size: 14px; margin-bottom: 30px; }
        .shipping-info h3 { margin-top: 0; font-size: 16px; color: #0f172a; margin-bottom: 10px; }
        .shipping-info p { margin: 5px 0; color: #475569; }

        .footer { background-color: #f8fafc; padding: 20px; text-align: center; font-size: 12px; color: #94a3b8; border-top: 1px solid #e2e8f0; }
        .btn { display: inline-block; background-color: #D9258B; color: #ffffff; padding: 12px 25px; text-decoration: none; border-radius: 6px; font-weight: 600; font-size: 14px; transition: background 0.3s; margin-top: 10px; }
        .btn:hover { background-color: #be1875; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <img src="{{ $message->embed(public_path('logo.jpg')) }}" alt="Venezia Pizzas" style="max-width: 150px; margin-bottom: 15px; border-radius: 10px;">
            <h1>¡Gracias por tu compra!</h1>
            <p>Hola {{ $user->nombre }}, estamos preparando tu pedido.</p>
        </div>
        
        <div class="content">
            <div class="order-info">
                <h2>Pedido #{{ $pedido->id }}</h2>
                <p>Fecha: {{ $pedido->created_at->format('d/m/Y h:i A') }}</p>
                <p>Estado: <strong style="color: #10b981; text-transform: uppercase;">{{ $pedido->estado }}</strong></p>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>Producto</th>
                        <th style="text-align: center;">Cant.</th>
                        <th style="text-align: right;">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($pedido->items as $item)
                    <tr>
                        <td>
                            <span class="product-name">{{ $item->variante->producto->nombre }}</span>
                            <span class="product-variant">
                                {{ $item->variante->color }} / {{ $item->variante->talla }}
                            </span>
                        </td>
                        <td style="text-align: center;">{{ $item->cantidad }}</td>
                        <td style="text-align: right;">${{ number_format($item->precio_unitario * $item->cantidad, 0) }}</td>
                    </tr>
                    @endforeach
                    <tr class="total-row">
                        <td colspan="2" style="text-align: right;">TOTAL A PAGAR:</td>
                        <td style="text-align: right;">${{ number_format($pedido->total, 0) }}</td>
                    </tr>
                </tbody>
            </table>

            <div class="shipping-info">
                <h3>Detalles de Envío</h3>
                <p><strong>Dirección:</strong> {{ $pedido->direccion_envio }}</p>
                <p><strong>Ciudad:</strong> {{ $pedido->ciudad }}</p>
                @if($pedido->codigo_postal)
                <p><strong>Código Postal:</strong> {{ $pedido->codigo_postal }}</p>
                @endif
                <p><strong>Teléfono:</strong> {{ $pedido->telefono }}</p>
                @if($pedido->notas_cliente)
                <p style="margin-top: 10px; font-style: italic;">"{{ $pedido->notas_cliente }}"</p>
                @endif
            </div>

            <div style="text-align: center;">
                <p>Puedes ver el estado de tu pedido en tu cuenta.</p>
                <a href="{{ url('/') }}/client/orders" class="btn">Ver Mis Pedidos</a>
            </div>
        </div>

        <div class="footer">
            <p>&copy; {{ date('Y') }} Venezia Pizzas & Style. Todos los derechos reservados.</p>
            <p>Si tienes alguna pregunta, contáctanos en soporte@venezia.com</p>
        </div>
    </div>
</body>
</html>
