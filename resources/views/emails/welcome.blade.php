<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bienvenido a Shopping Cúcuta</title>
    <style>
        body { margin: 0; padding: 0; font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; background-color: #f4f4f7; color: #51545E; }
        .wrapper { width: 100%; background-color: #f4f4f7; padding: 20px 0; }
        .email-container { max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); overflow: hidden; }
        .header { background-color: #1a202c; padding: 30px; text-align: center; }
        .header h1 { color: #ffffff; margin: 0; font-size: 24px; font-weight: bold; }
        .content { padding: 40px; }
        .content h2 { color: #333333; font-size: 20px; margin-top: 0; }
        .content p { line-height: 1.6; color: #51545E; font-size: 16px; margin-bottom: 20px; }
        .btn-container { text-align: center; margin: 30px 0; }
        .btn { display: inline-block; background-color: #D9258B; color: #ffffff; padding: 12px 25px; text-decoration: none; border-radius: 5px; font-weight: bold; font-size: 16px; }
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
                <h2>¡Hola, {{ $user->nombre }}!</h2>
                <p>Nos alegra mucho tenerte en nuestra comunidad.</p>
                <p>En Shopping Cúcuta encontrarás los mejores productos y ofertas exclusivas pensadas para ti. Tu cuenta ha sido creada exitosamente y ya puedes empezar a explorar todo lo que tenemos.</p>
                
                <div class="btn-container">
                    <a href="{{ config('app.url') }}" class="btn">Ir a la Tienda</a>
                </div>

                <div class="divider"></div>
                
                <p style="font-size: 14px;">Si tienes alguna pregunta o necesitas ayuda, nuestro equipo de soporte está listo para asistirte.</p>
            </div>

            <!-- Footer -->
            <div class="footer">
                <p>&copy; {{ date('Y') }} Shopping Cúcuta. Todos los derechos reservados.</p>
                <p>Este es un correo automático, por favor no respondas a este mensaje.</p>
            </div>
        </div>
    </div>
</body>
</html>
