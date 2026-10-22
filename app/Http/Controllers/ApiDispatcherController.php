<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ApiDispatcherController extends Controller
{
    public function handle(Request $request, string $module, string $action)
    {
        $controllerName = Str::studly($module) . 'Controller';

        $controllerClass = 'App\\Http\\Controllers\\' . $controllerName;

        if (!class_exists($controllerClass) || !method_exists($controllerClass, 'handle')) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid API module'
            ], 404);
        }

        $controller = app($controllerClass);

        return $controller->handle($request, $action);
    }
}
