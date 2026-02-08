<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contraseña Actualizada</title>
    <style>
        body { margin: 0; padding: 0; font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; background-color: #f4f4f7; color: #51545E; }
        .wrapper { width: 100%; background-color: #f4f4f7; padding: 20px 0; }
        .email-container { max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); overflow: hidden; }
        .header { background-color: #1a202c; padding: 30px; text-align: center; }
        .header h1 { color: #ffffff; margin: 0; font-size: 24px; font-weight: bold; }
        .content { padding: 40px; }
        .content h2 { color: #333333; font-size: 20px; margin-top: 0; }
        .content p { line-height: 1.6; color: #51545E; font-size: 16px; margin-bottom: 20px; }
        .alert-box { background-color: #fff8e1; border-left: 5px solid #ffc107; padding: 15px; margin-bottom: 20px; color: #856404; font-size: 14px; }
        .footer { background-color: #f4f4f7; padding: 20px; text-align: center; font-size: 12px; color: #6b6e76; }
        .divider { border-top: 1px solid #eaeaec; margin: 20px 0; }
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
                <h2>Tu contraseña ha sido actualizada</h2>
                <p>Hola, {{ $user->nombre }}.</p>
                <p>Te informamos que la contraseña de tu cuenta ha sido cambiada exitosamente el <strong>{{ $time }}</strong>.</p>
                
                <div class="alert-box">
                    <strong>¿No fuiste tú?</strong><br>
                    Si no realizaste este cambio, por favor contacta a soporte inmediatamente y recupera tu cuenta.
                </div>

                <div class="divider"></div>
                
                <p style="font-size: 14px;">Gracias por confiar en nosotros.</p>
            </div>

            <!-- Footer -->
            <div class="footer">
                <p>&copy; {{ date('Y') }} Shopping Cúcuta. Todos los derechos reservados.</p>
            </div>
        </div>
    </div>
</body>
</html>
