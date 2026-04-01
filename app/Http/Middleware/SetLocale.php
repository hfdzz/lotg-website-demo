<?php

namespace App\Http\Middleware;

use App\Support\LotgLanguage;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $language = LotgLanguage::normalize($request->query('lang'));

        App::setLocale($language);

        return $next($request);
    }
}
