<?php

namespace App\Http\Middleware;

use App\Support\Locale\LocaleResolver;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function __construct(
        private readonly LocaleResolver $localeResolver,
    ) {}

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $this->localeResolver->resolve($request->user());

        app()->setLocale($locale);

        return $next($request);
    }
}
