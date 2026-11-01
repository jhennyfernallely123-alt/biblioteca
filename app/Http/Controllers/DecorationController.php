<?php

namespace App\Http\Controllers;

use App\Models\Decoration;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class DecorationController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('Decorations/Index', [
            'decorations' => $request->user()->decorations()->with('shelf:id,name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'kind' => ['required', Rule::in(Decoration::KINDS)],
            'color' => ['required', Rule::in(Decoration::COLORS)],
        ]);

        $shelf = $request->user()->shelves()
            ->orderBy('position')
            ->orderBy('id')
            ->firstOrFail();

        $lastBook = $shelf->books()
            ->reorder()
            ->orderByDesc('position')
            ->orderByDesc('title')
            ->first();

        $request->user()->decorations()->create([
            'shelf_id' => $shelf->id,
            'kind' => $data['kind'],
            'color' => $data['color'],
            'after_book_id' => $lastBook?->id,
            'sort' => $shelf->decorations()->where('after_book_id', $lastBook?->id)->count(),
        ]);

        return back()->with('success', __('ui.decoration_added', [
            'name' => __('ui.decoration_'.$data['kind']),
            'shelf' => $shelf->name,
        ]));
    }

    public function update(Request $request, Decoration $decoration): RedirectResponse
    {
        $this->authorize('update', $decoration);

        $data = $request->validate([
            'shelf_id' => ['required', 'integer'],
            'after_book_id' => ['nullable', 'integer'],
            'sort' => ['nullable', 'integer', 'min:0'],
        ]);

        $shelf = $request->user()->shelves()->findOrFail($data['shelf_id']);
        $afterBookId = $data['after_book_id'] ?? null;

        if ($afterBookId !== null) {
            abort_unless(
                $shelf->books()->whereKey($afterBookId)->exists(),
                422,
                'El libro ancla no pertenece a esa estantería.'
            );
        }

        DB::transaction(function () use ($decoration, $shelf, $afterBookId, $data) {
            $oldShelfId = (int) $decoration->shelf_id;
            $oldAnchor = $decoration->after_book_id;

            $this->place($decoration, (int) $shelf->id, $afterBookId, (int) ($data['sort'] ?? 0));

            $this->resequence($oldShelfId, $oldAnchor);
        });

        return back()->with('success', __('ui.decoration_moved', [
            'name' => __('ui.decoration_'.$decoration->kind),
            'shelf' => $shelf->name,
        ]));
    }

    public function destroy(Decoration $decoration): RedirectResponse
    {
        $this->authorize('delete', $decoration);

        $name = __('ui.decoration_'.$decoration->kind);

        $decoration->delete();

        return back()->with('success', __('ui.decoration_removed', ['name' => $name]));
    }

    public function clear(Request $request): RedirectResponse
    {
        $request->user()->decorations()->delete();

        return back()->with('success', __('ui.decorations_cleared'));
    }

    private function place(Decoration $decoration, int $shelfId, ?int $afterBookId, int $index): void
    {
        $siblings = Decoration::query()
            ->where('shelf_id', $shelfId)
            ->where('after_book_id', $afterBookId)
            ->whereKeyNot($decoration->id)
            ->orderBy('sort')
            ->orderBy('id')
            ->get()
            ->all();

        $index = max(0, min($index, count($siblings)));

        array_splice($siblings, $index, 0, [$decoration]);

        $decoration->update([
            'shelf_id' => $shelfId,
            'after_book_id' => $afterBookId,
        ]);

        foreach ($siblings as $position => $item) {
            $item->update(['sort' => $position]);
        }
    }

    private function resequence(int $shelfId, ?int $afterBookId): void
    {
        Decoration::query()
            ->where('shelf_id', $shelfId)
            ->where('after_book_id', $afterBookId)
            ->orderBy('sort')
            ->orderBy('id')
            ->get()
            ->each(fn (Decoration $item, int $position) => $item->update(['sort' => $position]));
    }
}
