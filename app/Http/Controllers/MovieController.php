<?php

namespace App\Http\Controllers;

use App\Models\Favorite;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\File;

class MovieController extends Controller
{
    public function index()
    {
        $apiKey = '1f4538eba70f05d92bc718ad8c93e6e1';
        $url = 'https://api.themoviedb.org/3/movie/popular';

        // Realizar la solicitud HTTP a la API externa
        $response = Http::get($url, [
            'api_key' => $apiKey,
        ]);

        // Verificar si la solicitud fue exitosa
        if ($response->successful()) {
            $responseData = $response->json();
            $movies = $responseData['results'];

            // Paginar los resultados manualmente
            $currentPage = request()->query('page', 1);
            $perPage = 15; // Número de elementos por página
            $total = count($movies);

            // Crear un objeto Paginator para la paginación
            $paginator = new LengthAwarePaginator(
                $movies,
                $total,
                $perPage,
                $currentPage,
                ['path' => Paginator::resolveCurrentPath()]
            );

            return response()->json($paginator);
        } else {
            // En caso de error en la solicitud, retornar un mensaje de error
            return response()->json(['error' => 'Error al obtener las películas de la API externa'], $response->status());
        }
    }

    public function search(Request $request)
    {
        // Separar los términos de búsqueda en un array si están separados por coma
        $queries = explode(',', $request->input('query'));

        // Validar la entrada del usuario
        $validator = Validator::make(['query' => $queries], [
            'query' => 'required|array', // Asegura que el parámetro 'query' esté presente y sea un array
            'query.*' => 'required|string', // Asegura que cada elemento del array sea una cadena de texto
        ], [
            'query.required' => 'El parámetro de búsqueda es requerido.',
            'query.array' => 'El parámetro de búsqueda debe ser un array.',
            'query.*.required' => 'Cada término de búsqueda es requerido.',
            'query.*.string' => 'Cada término de búsqueda debe ser una cadena de texto.',
        ]);

        // Si la validación falla, devolver los mensajes de error
        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        // Array para almacenar los resultados de búsqueda de todas las consultas
        $allResults = [];

        // Iterar sobre cada término de búsqueda y buscar películas que coincidan con ellos
        foreach ($queries as $query) {
            // Construir la URL de la API externa para la búsqueda de películas por nombre
            $apiKey = '1f4538eba70f05d92bc718ad8c93e6e1';
            $url = 'https://api.themoviedb.org/3/search/movie';

            // Realizar la solicitud HTTP a la API externa
            $response = Http::get($url, [
                'api_key' => $apiKey,
                'query' => $query,
            ]);

            // Verificar si la solicitud fue exitosa
            if ($response->successful()) {
                $responseData = $response->json();
                $movies = $responseData['results'];

                // Agregar los resultados de esta consulta al array de todos los resultados
                $allResults[] = $movies;
            } else {
                // En caso de error en la solicitud, retornar un mensaje de error
                return response()->json(['error' => 'Error al buscar películas en la API externa'], $response->status());
            }
        }

        // Devolver todos los resultados de búsqueda combinados en un solo array
        return response()->json($allResults);
    }

    public function addFavorite(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'movieId' => 'required|integer',
        ], [
            'movieId.required' => 'El ID de la película es requerido.',
            'movieId.integer' => 'El ID de la película debe ser un número entero.',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        // Obtener el usuario autenticado
        $user = auth()->user();

        // Verificar si el usuario ya ha marcado esta película como favorita
        if ($user->favorites()->where('movie_id', $request->movieId)->exists()) {
            return response()->json(['error' => 'La película ya está marcada como favorita'], 400);
        }

        // Crear una nueva entrada en la tabla de favoritos para marcar la película como favorita
        $favorite = new Favorite();
        $favorite->user_id = $user->id;
        $favorite->movie_id = $request->movieId;
        $favorite->save();

        // Devolver una respuesta exitosa
        return response()->json(['message' => 'Película marcada como favorita correctamente'], 200);
    }

    public function listFavorites(Request $request)
    {
        // Obtener el usuario autenticado
        $user = auth()->user();

        // Obtener los IDs de las películas marcadas como favoritas por el usuario
        $favoriteMovieIds = $user->favorites()->pluck('movie_id');

        // Realizar la solicitud HTTP a la API externa para obtener la información de las películas
        $apiKey = '1f4538eba70f05d92bc718ad8c93e6e1';
        $url = 'https://api.themoviedb.org/3/movie/';

        $movies = [];
        foreach ($favoriteMovieIds as $movieId) {
            $response = Http::get($url . $movieId, [
                'api_key' => $apiKey,
            ]);

            // Verificar si la solicitud fue exitosa
            if ($response->successful()) {
                $movies[] = $response->json();
            } else {
                // Manejar el error en caso de que la solicitud falle
                // Puedes registrar el error, omitir la película o manejarlo de otra manera según tus necesidades
                \Log::error('Error al obtener información de la película con ID: ' . $movieId);
            }
        }

        // Devolver la información de las películas marcadas como favoritas
        return response()->json($movies);
    }

    public function getLoginLogs()
    {
        // Ruta al archivo de log de autenticación
        $logFilePath = storage_path('logs/auth.log');

        // Verificar si el archivo de log existe
        if (File::exists($logFilePath)) {
            // Leer el contenido del archivo de log
            $logContent = File::get($logFilePath);

            // Devolver el contenido del log en formato JSON
            return response()->json($logContent);
        } else {
            // Si el archivo de log no existe, devolver un mensaje de error
            return response()->json(['error' => 'El archivo de log no existe'], 404);
        }
    }

}
