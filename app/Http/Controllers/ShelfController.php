<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreShelfRequest;
use App\Http\Requests\UpdateShelfRequest;
use App\Models\Shelf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ShelfController extends Controller
{
    public function store(StoreShelfRequest $request): RedirectResponse
    {
        $position = $request->user()->shelves()->max('position');
        $position = $position === null ? 0 : $position + 1;

        $request->user()->shelves()->create([
            'name' => $request->validated('name'),
            'position' => $position,
            'view_mode' => $request->user()->default_view_mode ?? 'covers',
        ]);

        return back();
    }

    public function update(UpdateShelfRequest $request, Shelf $shelf): RedirectResponse
    {
        $this->authorize('update', $shelf);

        $shelf->update($request->validated());

        return back();
    }

    public function reorder(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer', 'distinct'],
        ]);

        DB::transaction(function () use ($request, $validated) {
            $shelves = $request->user()->shelves()->whereIn('id', $validated['ids'])->get()->keyBy('id');

            foreach ($validated['ids'] as $position => $id) {
                if ($shelves->has($id)) {
                    $shelves[$id]->update(['position' => $position]);
                }
            }
        });

        return back();
    }

    public function destroy(Request $request, Shelf $shelf): RedirectResponse
    {
        $this->authorize('delete', $shelf);

        DB::transaction(function () use ($request, $shelf) {
            $fallback = $request->user()->shelves()->where('name', 'Pendientes')->first()
                ?? $request->user()->shelves()->where('id', '!=', $shelf->id)->orderBy('position')->first();

            if (! $fallback) {
                $fallback = $request->user()->shelves()->create([
                    'name' => 'Pendientes',
                    'position' => 0,
                    'view_mode' => 'covers',
                ]);
            }

            $shelf->books()->update(['shelf_id' => $fallback->id]);
            $shelf->delete();

            $request->user()->shelves()->orderBy('position')->get()
                ->each(fn (Shelf $remaining, int $position) => $remaining->update(['position' => $position]));
        });

        return back();
    }
}
