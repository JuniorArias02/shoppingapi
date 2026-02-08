<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nuevo Inicio de Sesión</title>
    <style>
        body { margin: 0; padding: 0; font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; background-color: #f4f4f7; color: #51545E; }
        .wrapper { width: 100%; background-color: #f4f4f7; padding: 20px 0; }
        .email-container { max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); overflow: hidden; }
        .header { background-color: #1a202c; padding: 30px; text-align: center; }
        .header h1 { color: #ffffff; margin: 0; font-size: 24px; font-weight: bold; }
        .content { padding: 40px; }
        .content h2 { color: #D9258B; font-size: 20px; margin-top: 0; }
        .content p { line-height: 1.6; color: #51545E; font-size: 16px; margin-bottom: 20px; }
        .details-box { background-color: #f9f9f9; border: 1px solid #eaeaec; border-radius: 5px; padding: 15px; margin-bottom: 20px; }
        .details-title { font-weight: bold; color: #333; font-size: 14px; margin-bottom: 5px; display: block; }
        .details-value { color: #555; font-family: monospace; font-size: 14px; word-break: break-all; }
        .footer { background-color: #f4f4f7; padding: 20px; text-align: center; font-size: 12px; color: #6b6e76; }
        .divider { border-top: 1px solid #eaeaec; margin: 20px 0; }
        .warning { color: #e53e3e; font-size: 14px; margin-top: 20px; font-weight: bold; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="email-container">
            <!-- Header -->
            <div class="header">
                 <img src="{{ $message->embed(public_path('logo.jpg')) }}" alt="Shopping Cúcuta" style="max-width: 150px; margin-bottom: 15px; border-radius: 10px;">
                 <h1>Shopping Cúcuta</h1>
            </div>

            <!-- Content -->
            <div class="content">
                <h2>Nuevo inicio de sesión detectado</h2>
                <p>Hola, {{ $user->nombre }}.</p>
                <p>Hemos detectado un nuevo inicio de sesión en tu cuenta de Shopping Cúcuta.</p>
                
                <div class="details-box">
                    <span class="details-title">Fecha y Hora:</span>
                    <span class="details-value">{{ $time }}</span>
                    <br><br>
                    <span class="details-title">Dirección IP:</span>
                    <span class="details-value">{{ $ip }}</span>
                    <br><br>
                    <span class="details-title">Dispositivo / Navegador:</span>
                    <span class="details-value">{{ $userAgent }}</span>
                </div>

                <p class="warning">Si no fuiste tú, por favor cambia tu contraseña inmediatamente y contacta a soporte.</p>

                <div class="divider"></div>
                
                <p style="font-size: 14px;">Este mensaje es por tu seguridad. Si fuiste tú, puedes ignorar este correo.</p>
            </div>

            <!-- Footer -->
            <div class="footer">
                <p>&copy; {{ date('Y') }} Shopping Cúcuta. Todos los derechos reservados.</p>
                <p>Este es un correo automático.</p>
            </div>
        </div>
    </div>
</body>
</html>
