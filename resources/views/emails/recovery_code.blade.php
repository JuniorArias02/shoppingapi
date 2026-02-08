<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Código de Recuperación</title>
    <style>
        body { margin: 0; padding: 0; font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; background-color: #f4f4f7; color: #51545E; }
        .wrapper { width: 100%; background-color: #f4f4f7; padding: 20px 0; }
        .email-container { max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); overflow: hidden; }
        .header { background-color: #1a202c; padding: 30px; text-align: center; }
        .header h1 { color: #ffffff; margin: 0; font-size: 24px; font-weight: bold; }
        .content { padding: 40px; text-align: center; }
        .content h2 { color: #333333; font-size: 20px; margin-top: 0; }
        .content p { line-height: 1.6; color: #51545E; font-size: 16px; margin-bottom: 20px; }
        .code-box { background-color: #f0f0f5; border: 1px dashed #D9258B; border-radius: 8px; padding: 20px; margin: 30px 0; display: inline-block; width: 80%; }
        .code-display { font-size: 32px; font-weight: bold; color: #D9258B; letter-spacing: 5px; font-family: monospace; }
        .footer { background-color: #f4f4f7; padding: 20px; text-align: center; font-size: 12px; color: #6b6e76; }
        .divider { border-top: 1px solid #eaeaec; margin: 20px 0; }
        .note { font-size: 13px; color: #888; margin-top: 15px; }
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
                <h2>Recuperación de Contraseña</h2>
                <p>Hola, {{ $user->nombre }}.</p>
                <p>Recibimos una solicitud para restablecer tu contraseña. Usa el siguiente código para continuar:</p>
                
                <div class="code-box">
                    <span class="code-display">{{ $code }}</span>
                </div>

                <p class="note">Este código expirará en 15 minutos.</p>
                <p class="note">Si no solicitaste este cambio, puedes ignorar este correo.</p>

                <div class="divider"></div>
            </div>

            <!-- Footer -->
            <div class="footer">
                <p>&copy; {{ date('Y') }} Shopping Cúcuta. Todos los derechos reservados.</p>
            </div>
        </div>
    </div>
</body>
</html>
