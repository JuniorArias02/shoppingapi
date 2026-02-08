<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    protected $emailService;

    public function __construct(\App\Services\EmailService $emailService)
    {
        $this->emailService = $emailService;
    }

    // Registro de usuario
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nombre' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:usuarios',
            'password' => 'required|string|min:8',
            'rol_id' => 'nullable|exists:roles,id',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $user = User::create([
            'nombre' => $request->nombre,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'rol_id' => $request->rol_id ?? 2, // Default to Client (2)
            'estado' => 'activo',
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'data' => $user,
            'access_token' => $token,
            'token_type' => 'Bearer',
        ], 201);
    }

    // Login de usuario
    public function login(Request $request)
    {
        if (!Auth::attempt($request->only('email', 'password'))) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $user = User::where('email', $request->email)->firstOrFail();

        // Verificar estado
        if ($user->estado !== 'activo') {
            return response()->json(['message' => 'User account is inactive'], 403);
        }

        // Check for first login
        if (is_null($user->last_login_at)) {
            $this->emailService->sendWelcomeEmail($user);
        }

        // Send Login Alert
        $this->emailService->sendLoginAlert($user, $request->ip(), $request->userAgent());

        // Update last login time
        $user->update(['last_login_at' => now()]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Hi ' . $user->nombre . ', welcome to home',
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user->load('perfil')
        ]);
    }

    // Logout
    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();

        return response()->json(['message' => 'You have successfully logged out and the token was successfully deleted']);
    }
    // Actualizar perfil
    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'nombre' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:usuarios,email,' . $user->id,
            'codigo_postal' => 'nullable|string|max:20',
            'direccion' => 'nullable|string|max:255',
            'departamento' => 'nullable|string|max:100',
            'ciudad' => 'nullable|string|max:100',
            'numero_telefono' => 'nullable|string|max:20',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $user->update([
            'nombre' => $request->nombre,
            'email' => $request->email,
        ]);

        // Update or Create Perfil
        $user->perfil()->updateOrCreate(
            ['usuario_id' => $user->id],
            [
                'codigo_postal' => $request->codigo_postal,
                'direccion' => $request->direccion,
                'departamento' => $request->departamento,
                'ciudad' => $request->ciudad,
                'numero_telefono' => $request->numero_telefono,
            ]
        );

        return response()->json([
            'message' => 'Profile updated successfully',
            'user' => $user->load('perfil')
        ]);
    }

    // Cambiar contraseña
    public function changePassword(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'current_password' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json(['message' => 'Contraseña actual incorrecta'], 400);
        }

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        $this->emailService->sendPasswordChangedAlert($user);

        return response()->json(['message' => 'Password changed successfully']);
    }

    // Enviar código de recuperación
    public function sendResetCode(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:usuarios,email',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Email not found'], 404);
        }

        $user = User::where('email', $request->email)->first();
        
        // Generate 6-digit code
        $code = rand(100000, 999999);
        
        // Store in Cache for 15 minutes
        // Key format: password_reset_EMAIL
        \Illuminate\Support\Facades\Cache::put('password_reset_' . $user->email, $code, now()->addMinutes(15));

        $this->emailService->sendRecoveryCode($user, $code);

        return response()->json(['message' => 'Recovery code sent']);
    }

    // Verificar código y cambiar contraseña
    public function verifyAndResetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:usuarios,email',
            'code' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $cachedCode = \Illuminate\Support\Facades\Cache::get('password_reset_' . $request->email);

        if (!$cachedCode || $cachedCode != $request->code) {
            return response()->json(['message' => 'Invalid or expired code'], 400);
        }

        $user = User::where('email', $request->email)->first();
        $user->password = Hash::make($request->password);
        $user->save();

        // Forget the code
        \Illuminate\Support\Facades\Cache::forget('password_reset_' . $request->email);

        // Send confirmation email
        $this->emailService->sendPasswordChangedAlert($user);

        // Auto login
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Password reset successfully',
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user->load('perfil')
        ]);
    }
}
