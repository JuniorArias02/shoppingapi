<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $titulo }}</title>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; background-color: #0f172a; margin: 0; padding: 0; color: #e2e8f0; }
        .container { max-width: 600px; margin: 40px auto; background-color: #151E32; border-radius: 16px; overflow: hidden; box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.5), 0 0 10px rgba(0, 194, 203, 0.1); border: 1px solid rgba(255, 255, 255, 0.1); }
        
        /* Dynamic Header Colors */
        @php
            $accentColor = '#00C2CB'; // Cyan Default
            $icon = '👀';
            $statusStep = 1;
            
            if($status == 'visto') { $accentColor = '#3b82f6'; $icon = '👀'; $statusStep = 2; }
            if($status == 'empacado') { $accentColor = '#8b5cf6'; $icon = '📦'; $statusStep = 3; }
            if($status == 'enviado') { $accentColor = '#D9258B'; $icon = '🚚'; $statusStep = 4; }
        @endphp

        .header { background: linear-gradient(135deg, rgba(21, 30, 50, 0.95), rgba(21, 30, 50, 0.8)); padding: 40px 30px; text-align: center; border-bottom: 1px solid rgba(255,255,255,0.05); position: relative; }
        .header::after { content: ''; position: absolute; bottom: 0; left: 0; right: 0; height: 1px; background: linear-gradient(90deg, transparent, {{ $accentColor }}, transparent); opacity: 0.5; }
        
        .logo { width: 80px; height: 80px; border-radius: 12px; margin-bottom: 20px; box-shadow: 0 0 20px {{ $accentColor }}40; border: 2px solid rgba(255,255,255,0.1); object-fit: cover; }
        
        .header h1 { margin: 0; font-size: 28px; font-weight: 800; color: #ffffff; letter-spacing: -0.5px; text-transform: uppercase; text-shadow: 0 2px 10px rgba(0,0,0,0.5); }
        .header h2 { margin: 10px 0 0; font-size: 16px; font-weight: 500; color: {{ $accentColor }}; text-transform: uppercase; letter-spacing: 2px; }

        .content { padding: 40px 30px; text-align: center; background-color: #151E32; }

        /* Timeline Visualization */
        .timeline { display: flex; justify-content: space-between; margin-bottom: 40px; position: relative; padding: 0 10px; }
        .timeline::before { content: ''; position: absolute; top: 15px; left: 0; right: 0; height: 2px; background: #334155; z-index: 1; border-radius: 99px; }
        
        .timeline-step { position: relative; z-index: 2; display: flex; flex-direction: column; align-items: center; width: 25%; }
        .dot { width: 30px; height: 30px; border-radius: 50%; background-color: #1e293b; border: 2px solid #475569; display: flex; align-items: center; justify-content: center; font-size: 12px; margin-bottom: 8px; transition: all 0.3s; color: #94a3b8; }
        
        /* Active State logic handled inline styles due to PHP limitations in CSS blocks */
        .dot.active { border-color: {{ $accentColor }}; background-color: {{ $accentColor }}; color: #fff; box-shadow: 0 0 15px {{ $accentColor }}60; }
        .dot.completed { border-color: {{ $accentColor }}; background-color: #151E32; color: {{ $accentColor }}; }

        .step-label { font-size: 10px; text-transform: uppercase; color: #64748b; font-weight: 700; letter-spacing: 0.5px; }
        .step-label.active { color: {{ $accentColor }}; }

        .message-text { font-size: 16px; line-height: 1.6; color: #cbd5e1; margin-bottom: 30px; background: rgba(255,255,255,0.03); padding: 20px; border-radius: 12px; border: 1px solid rgba(255,255,255,0.05); }

        .order-details { background-color: #0f172a; border: 1px solid #334155; border-radius: 12px; padding: 25px; margin-bottom: 30px; text-align: left; position: relative; overflow: hidden; }
        .order-details::before { content: ''; position: absolute; top: 0; left: 0; width: 4px; height: 100%; background: {{ $accentColor }}; }
        
        .order-details h3 { margin-top: 0; color: #ffffff; margin-bottom: 20px; font-size: 14px; text-transform: uppercase; letter-spacing: 1px; border-bottom: 1px solid #334155; padding-bottom: 15px; display: flex; justify-content: space-between; align-items: center; }
        .order-id { color: {{ $accentColor }}; font-family: monospace; font-size: 16px; }

        .detail-row { display: flex; justify-content: space-between; margin-bottom: 12px; font-size: 14px; }
        .detail-label { color: #94a3b8; }
        .detail-value { font-weight: 600; color: #f1f5f9; }

        .btn { display: inline-block; background-color: {{ $accentColor }}; color: #ffffff; padding: 14px 40px; text-decoration: none; border-radius: 12px; font-weight: 700; font-size: 16px; transition: all 0.3s; text-transform: uppercase; letter-spacing: 1px; box-shadow: 0 4px 15px {{ $accentColor }}40; border: 1px solid rgba(255,255,255,0.1); }
        .btn:hover { transform: translateY(-2px); box-shadow: 0 6px 20px {{ $accentColor }}60; }

        .footer { background-color: #0f172a; padding: 30px; text-align: center; font-size: 12px; color: #64748b; border-top: 1px solid rgba(255,255,255,0.05); }
        .footer p { margin: 5px 0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <img src="{{ $message->embed(public_path('logo.jpg')) }}" alt="Logo" class="logo">
            <h1>{{ $titulo }}</h1>
            <h2> Shopping Cúcuta </h2>
        </div>
        
        <div class="content">
            
            <!-- Timeline (Simplified for Email) -->
            <div class="timeline">
                <div class="timeline-step">
                    <div class="dot {{ $statusStep >= 1 ? ($statusStep > 1 ? 'completed' : 'active') : '' }}">✓</div>
                    <div class="step-label {{ $statusStep >= 1 ? 'active' : '' }}">Pagado</div>
                </div>
                <div class="timeline-step">
                    <div class="dot {{ $statusStep >= 2 ? ($statusStep > 2 ? 'completed' : 'active') : '' }}">👀</div>
                    <div class="step-label {{ $statusStep >= 2 ? 'active' : '' }}">Visto</div>
                </div>
                <div class="timeline-step">
                    <div class="dot {{ $statusStep >= 3 ? ($statusStep > 3 ? 'completed' : 'active') : '' }}">📦</div>
                    <div class="step-label {{ $statusStep >= 3 ? 'active' : '' }}">Empacado</div>
                </div>
                <div class="timeline-step">
                    <div class="dot {{ $statusStep >= 4 ? 'active' : '' }}">🚚</div>
                    <div class="step-label {{ $statusStep >= 4 ? 'active' : '' }}">Enviado</div>
                </div>
            </div>

            <p class="message-text">
                Hola <strong style="color: #fff">{{ $user->nombre }}</strong>,<br><br>
                {{ $mensaje }}
            </p>

            <div class="order-details">
                <h3>Resumen <span class="order-id">#{{ $pedido->id }}</span></h3>
                <div class="detail-row">
                    <span class="detail-label">Fecha del Pedido</span>
                    <span class="detail-value">{{ $pedido->created_at->format('d/m/Y') }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Cantidad Items</span>
                    <span class="detail-value">{{ $pedido->items->count() }}</span>
                </div>
                <div class="detail-row" style="margin-top: 15px; padding-top: 15px; border-top: 1px dashed #334155;">
                    <span class="detail-label">Total a Pagar</span>
                    <span class="detail-value" style="color: {{ $accentColor }}; font-size: 18px;">${{ number_format($pedido->total, 0) }}</span>
                </div>
            </div>

            <a href="{{ url('/') }}/client/orders" class="btn">Ver Pedido</a>
        </div>

        <div class="footer">
            <p>&copy; {{ date('Y') }} Shopping Cúcuta. Todos los derechos reservados.</p>
            <p>Este es un mensaje automático, por favor no respondas a este correo.</p>
        </div>
    </div>
</body>
</html>
