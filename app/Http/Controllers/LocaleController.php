<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateLocaleRequest;
use App\Services\CatalogService;
use App\Services\CatalogTranslationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class LocaleController extends Controller
{
    public function __construct(
        private CatalogService $catalog,
        private CatalogTranslationService $translator,
    ) {}

    public function prepare(UpdateLocaleRequest $request): JsonResponse
    {
        $locale = $request->validated('locale');
        $missing = $this->catalog->missing($locale);

        return response()->json([
            'ready' => $missing === [],
            'missing' => count($missing),
            'keys' => array_slice($missing, 0, 20),
        ]);
    }

    public function translate(UpdateLocaleRequest $request): JsonResponse
    {
        $locale = $request->validated('locale');
        $keys = $request->validate([
            'keys' => ['required', 'array', 'max:20'],
            'keys.*' => ['string'],
        ])['keys'];

        $source = $this->catalog->source();
        $keys = array_values(array_filter($keys, fn ($key) => isset($source[$key])));

        $result = $this->translator->translateKeys($locale, $keys);
        $remaining = $this->catalog->missing($locale);

        return response()->json([
            'remaining' => count($remaining),
            'keys' => array_slice($remaining, 0, 20),
            'failed' => $result['failed'],
        ]);
    }

    public function update(UpdateLocaleRequest $request): RedirectResponse
    {
        $locale = $request->validated('locale');

        if (! $this->catalog->isComplete($locale)) {
            return back()->withErrors(['locale' => __('ui.language_prepare_failed')]);
        }

        if ($locale !== 'es' && ! in_array($locale, config('languages.curated'), true)) {
            $this->translator->finalize($locale);
        }

        $request->user()->update(['locale' => $locale]);

        return back()->with('success', __('ui.language_updated'));
    }
}
