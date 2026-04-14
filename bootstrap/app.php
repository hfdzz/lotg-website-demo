<?php

use App\Http\Middleware\SetLocale;
use App\Models\Permission;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            SetLocale::class,
        ]);
        $middleware->redirectGuestsTo(fn () => route('login'));
        $middleware->redirectUsersTo(function (Request $request) {
            return $request->user()?->hasPermissionTo(Permission::ADMIN_ACCESS)
                ? route('admin.home')
                : route('laws.index');
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
