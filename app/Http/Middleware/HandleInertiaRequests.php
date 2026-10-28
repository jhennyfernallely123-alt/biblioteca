<?php

namespace App\Http\Middleware;

use App\Services\CatalogService;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $locale = app()->getLocale();

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user(),
            ],
            'locale' => $locale,
            'direction' => in_array($locale, config('languages.rtl'), true) ? 'rtl' : 'ltr',
            'translations' => app(CatalogService::class)->ui($locale),
            'socialProviders' => [
                'google' => filled(config('services.google.client_id')) && filled(config('services.google.client_secret')),
                'facebook' => filled(config('services.facebook.client_id')) && filled(config('services.facebook.client_secret')),
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
        ];
    }
}
