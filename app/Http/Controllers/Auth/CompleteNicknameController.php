<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CompleteNicknameController extends Controller
{
    public function edit(Request $request): Response|RedirectResponse
    {
        if ($request->user()->nickname !== null) {
            return redirect()->route('dashboard');
        }

        return Inertia::render('Auth/CompleteNickname');
    }

    public function update(Request $request): RedirectResponse
    {
        if ($request->user()->nickname !== null) {
            return redirect()->route('dashboard');
        }

        $validated = $request->validate([
            'nickname' => ['required', 'string', 'max:50'],
        ]);

        $request->user()->update([
            'nickname' => $validated['nickname'],
        ]);

        $request->session()->regenerate();

        return redirect()->route('dashboard');
    }
}
