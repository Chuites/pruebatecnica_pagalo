<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    // Método para registrar un nuevo usuario
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6',
            'phone' => 'nullable|string'
        ], [
            'name.required' => 'El nombre es obligatorio.',
            'name.string' => 'El nombre debe ser una cadena de caracteres.',
            'name.max' => 'El nombre no puede tener más de 255 caracteres.',
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.string' => 'El correo electrónico debe ser una cadena de caracteres.',
            'email.email' => 'El correo electrónico debe ser una dirección de correo electrónico válida.',
            'email.max' => 'El correo electrónico no puede tener más de 255 caracteres.',
            'email.unique' => 'El correo electrónico ya ha sido registrado.',
            'password.required' => 'La contraseña es obligatoria.',
            'password.string' => 'La contraseña debe ser una cadena de caracteres.',
            'password.min' => 'La contraseña debe tener al menos 6 caracteres.',
            'phone.string' => 'El teléfono debe ser una cadena de caracteres.'
        ]);


        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        // Crear el usuario
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'phone' => $request->phone
        ]);

        // Crear el token JWT
        $token = JWTAuth::fromUser($user);

        Log::channel('auth')->info('Register successful for user: ' . $request->name . ' email: ' . $request->email);

        // Respuesta exitosa
        return response()->json(compact('user', 'token'), 201);
    }

    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        if (! $token = auth()->attempt($credentials)) {
            // Log de intento fallido
            Log::channel('auth')->info('Login unauthorized for user: ' . $request->email);
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Log de intento exitoso
        Log::channel('auth')->info('Login successful for user: ' . $request->email);

        return $this->respondWithToken($token);
    }

    // Método para responder con el token JWT
    protected function respondWithToken($token)
    {
        // Obtenemos el tiempo de vida del token JWT en segundos
        $expiresIn = JWTAuth::factory()->getTTL() * 60;

        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => $expiresIn
        ]);
    }
}
