<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\ReadingSession;
use App\Services\EventService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReadingController extends Controller
{
    public function show(Book $book): JsonResponse
    {
        $this->authorize('update', $book);

        return response()->json([
            'book' => $this->payload($book),
            'sessions' => $book->readingSessions()
                ->orderByDesc('read_on')
                ->orderByDesc('id')
                ->get(['id', 'read_on', 'minutes', 'page']),
        ]);
    }

    public function update(Request $request, Book $book): JsonResponse
    {
        $this->authorize('update', $book);

        $data = $request->validate([
            'total_pages' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'current_page' => ['nullable', 'integer', 'min:0', 'max:100000'],
        ]);

        $total = $data['total_pages'] ?? null;
        $current = $data['current_page'] ?? null;

        if ($total !== null && $current !== null) {
            $current = min($current, $total);
        }

        $book->update([
            'total_pages' => $total,
            'current_page' => $current,
        ]);

        return response()->json(['book' => $this->payload($book->fresh())]);
    }

    public function storeSession(Request $request, Book $book, EventService $events): JsonResponse
    {
        $this->authorize('update', $book);

        $data = $request->validate([
            'minutes' => ['required', 'integer', 'min:1', 'max:1440'],
            'page' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'read_on' => ['nullable', 'date'],
        ]);

        $page = $data['page'] ?? null;

        if ($page !== null && $book->total_pages) {
            $page = min($page, $book->total_pages);
        }

        $session = $book->readingSessions()->create([
            'user_id' => $request->user()->id,
            'read_on' => $data['read_on'] ?? now()->toDateString(),
            'minutes' => $data['minutes'],
            'page' => $page,
        ]);

        if ($page !== null) {
            $book->update([
                'current_page' => max((int) $book->current_page, $page),
            ]);
        }

        $events->completeTask($request->user(), 'read');

        return response()->json([
            'book' => $this->payload($book->fresh()),
            'session' => $session,
        ]);
    }

    public function destroySession(Request $request, ReadingSession $session): JsonResponse
    {
        abort_unless($session->user_id === $request->user()->id, 403);

        $book = $session->book;
        $session->delete();

        return response()->json(['book' => $this->payload($book->fresh())]);
    }

    private function payload(Book $book): array
    {
        $book->loadSum('readingSessions', 'minutes')->loadCount('readingSessions');

        return [
            'id' => $book->id,
            'title' => $book->title,
            'author' => $book->author,
            'cover_path' => $book->cover_path,
            'total_pages' => $book->total_pages,
            'current_page' => $book->current_page,
            'progress' => $book->readingProgress(),
            'minutes' => (int) ($book->reading_sessions_sum_minutes ?? 0),
            'status' => $book->readingStatus(),
        ];
    }
}
