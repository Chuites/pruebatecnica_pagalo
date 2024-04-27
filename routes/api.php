<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\MovieController;

// Rutas para la autenticación de usuarios
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Rutas para las funcionalidades relacionadas con películas
Route::middleware('jwt.auth')->group(function () {
    Route::get('/movies', [MovieController::class, 'index']);  // Listar películas
    Route::get('/movies/search', [MovieController::class, 'search']);  // Buscar películas por nombre
    Route::post('/movies/add_favorites', [MovieController::class, 'addFavorite']);  // Añadir película a favoritos
    Route::get('/movies/list_favorites', [MovieController::class, 'listFavorites']);  // Listar películas favoritas
    Route::get('/movies/login_log', [MovieController::class, 'getLoginLogs']);  // Listar películas favoritas
});
