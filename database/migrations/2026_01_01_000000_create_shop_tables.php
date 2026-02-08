<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Roles
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('nombre')->unique();
            $table->timestamps();
        });

        // 2. Usuarios
        Schema::create('usuarios', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->string('estado')->default('activo');
            $table->foreignId('rol_id')->constrained('roles');
            $table->timestamps();
        });

        // 3. Standard Laravel Tables
        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });

        Schema::create('cache', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->mediumText('value');
            $table->integer('expiration');
        });

        Schema::create('cache_locks', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->string('owner');
            $table->integer('expiration');
        });

        Schema::create('jobs', function (Blueprint $table) {
            $table->id();
            $table->string('queue')->index();
            $table->longText('payload');
            $table->unsignedTinyInteger('attempts');
            $table->unsignedInteger('reserved_at')->nullable();
            $table->unsignedInteger('available_at');
            $table->unsignedInteger('created_at');
        });

        Schema::create('job_batches', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('name');
            $table->integer('total_jobs');
            $table->integer('pending_jobs');
            $table->integer('failed_jobs');
            $table->longText('failed_job_ids');
            $table->mediumText('options')->nullable();
            $table->integer('cancelled_at')->nullable();
            $table->integer('created_at');
            $table->integer('finished_at')->nullable();
        });

        Schema::create('failed_jobs', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->text('connection');
            $table->text('queue');
            $table->longText('payload');
            $table->longText('exception');
            $table->timestamp('failed_at')->useCurrent();
        });

        Schema::create('personal_access_tokens', function (Blueprint $table) {
            $table->id();
            $table->morphs('tokenable');
            $table->string('name');
            $table->string('token', 64)->unique();
            $table->text('abilities')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });

        // 4. Categorias
        Schema::create('categorias', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->foreignId('parent_id')->nullable()->constrained('categorias')->nullOnDelete();
            $table->boolean('activa')->default(true);
            $table->timestamps();
        });

        // 5. Productos
        Schema::create('productos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('categoria_id')->constrained('categorias')->cascadeOnDelete();
            $table->string('nombre');
            $table->text('descripcion')->nullable();
            $table->string('marca')->nullable();
            $table->boolean('activo')->default(true);
            $table->integer('descuento')->default(0);
            $table->timestamps();
        });

        // 6. Producto Imagenes
        Schema::create('producto_imagenes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('producto_id')->constrained('productos')->cascadeOnDelete();
            $table->string('url_imagen');
            $table->timestamps();
        });

        // 7. Atributos
        Schema::create('atributos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->timestamps();
        });

        // 8. Atributo Valores
        Schema::create('atributo_valores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('atributo_id')->constrained('atributos')->cascadeOnDelete();
            $table->string('valor');
            // From SQL dump, no timestamps.
        });

        // 9. Producto Variantes
        Schema::create('producto_variantes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('producto_id')->constrained('productos')->cascadeOnDelete();
            $table->string('sku')->unique();
            $table->decimal('precio', 10, 2);
            $table->integer('stock')->default(0);
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        // 10. Variante Atributos
        Schema::create('variante_atributos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('producto_variante_id')->constrained('producto_variantes')->cascadeOnDelete();
            $table->foreignId('atributo_valor_id')->constrained('atributo_valores')->cascadeOnDelete();
        });

        // 11. Carritos
        Schema::create('carritos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_id')->constrained('usuarios')->cascadeOnDelete();
            $table->timestamps();
        });

        // 12. Carrito Items
        Schema::create('carrito_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('carrito_id')->constrained('carritos')->cascadeOnDelete();
            $table->foreignId('producto_variante_id')->constrained('producto_variantes')->cascadeOnDelete();
            $table->integer('cantidad');
        });

        // 13. Pedidos
        Schema::create('pedidos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_id')->constrained('usuarios')->cascadeOnDelete();
            $table->decimal('total', 10, 2);
            $table->string('direccion_envio')->nullable();
            $table->string('ciudad')->nullable();
            $table->string('codigo_postal')->nullable();
            $table->string('telefono')->nullable();
            $table->string('metodo_pago')->nullable();
            $table->text('notas_cliente')->nullable();
            $table->string('estado');
            $table->timestamp('pagado_en')->nullable();
            $table->timestamp('cancelado_en')->nullable();
            $table->timestamps();
        });

        // 14. Pedido Items
        Schema::create('pedido_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pedido_id')->constrained('pedidos')->cascadeOnDelete();
            $table->foreignId('producto_variante_id')->nullable()->constrained('producto_variantes')->nullOnDelete();
            $table->integer('cantidad');
            $table->decimal('precio_unitario', 10, 2);
        });

        // 15. Pagos
        Schema::create('pagos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pedido_id')->constrained('pedidos')->cascadeOnDelete();
            $table->string('metodo_pago');
            $table->string('estado');
            $table->decimal('monto', 10, 2);
            $table->string('moneda', 3)->default('COP');
            $table->string('pasarela_transaccion_id')->nullable()->index();
            $table->string('pasarela_nombre')->nullable();
            $table->text('pasarela_respuesta')->nullable();
            $table->timestamp('fecha_pago')->nullable();
            $table->text('notas')->nullable();
            $table->timestamps();
            
            $table->index(['pedido_id', 'estado']);
        });

        // 16. Reservas Stock
        Schema::create('reservas_stock', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pedido_id')->constrained('pedidos')->cascadeOnDelete();
            $table->foreignId('producto_variante_id')->constrained('producto_variantes')->cascadeOnDelete();
            $table->integer('cantidad');
            $table->timestamp('expira_en');
            $table->timestamps();

            $table->index(['producto_variante_id', 'expira_en']);
        });

        // 17. Movimientos Stock
        Schema::create('movimientos_stock', function (Blueprint $table) {
            $table->id();
            $table->foreignId('producto_variante_id')->constrained('producto_variantes')->cascadeOnDelete();
            $table->string('tipo');
            $table->integer('cantidad');
            $table->string('motivo')->nullable();
            $table->timestamps();
        });

        // 18. Product Likes
        Schema::create('product_likes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('producto_id')->constrained('productos')->cascadeOnDelete();
            $table->foreignId('usuario_id')->constrained('usuarios')->cascadeOnDelete();
            $table->timestamps();
            
            $table->unique(['producto_id', 'usuario_id']);
        });

        // 19. Product Ratings
        Schema::create('product_ratings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('producto_id')->constrained('productos')->cascadeOnDelete();
            $table->foreignId('usuario_id')->constrained('usuarios')->cascadeOnDelete();
            $table->integer('rating');
            $table->text('comment')->nullable();
            $table->timestamps();
        });

        // 20. Perfil Clientes
        Schema::create('perfil_clientes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_id')->constrained('usuarios')->cascadeOnDelete();
            $table->string('codigo_postal')->nullable();
            $table->string('direccion')->nullable();
            $table->string('departamento')->nullable();
            $table->string('ciudad')->nullable();
            $table->string('numero_telefono')->nullable();
            $table->timestamps();
        });

        // 21. Logs
        Schema::create('logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_id')->nullable()->constrained('usuarios')->nullOnDelete();
            $table->string('accion');
            $table->string('tabla')->nullable();
            $table->unsignedBigInteger('registro_id')->nullable();
            $table->string('ip')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('logs');
        Schema::dropIfExists('perfil_clientes');
        Schema::dropIfExists('product_ratings');
        Schema::dropIfExists('product_likes');
        Schema::dropIfExists('movimientos_stock');
        Schema::dropIfExists('reservas_stock');
        Schema::dropIfExists('pagos');
        Schema::dropIfExists('pedido_items');
        Schema::dropIfExists('pedidos');
        Schema::dropIfExists('carrito_items');
        Schema::dropIfExists('carritos');
        Schema::dropIfExists('variante_atributos');
        Schema::dropIfExists('producto_variantes');
        Schema::dropIfExists('atributo_valores');
        Schema::dropIfExists('atributos');
        Schema::dropIfExists('producto_imagenes');
        Schema::dropIfExists('productos');
        Schema::dropIfExists('categorias');
        Schema::dropIfExists('personal_access_tokens');
        Schema::dropIfExists('failed_jobs');
        Schema::dropIfExists('job_batches');
        Schema::dropIfExists('jobs');
        Schema::dropIfExists('cache_locks');
        Schema::dropIfExists('cache');
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('usuarios');
        Schema::dropIfExists('roles');
    }
};
