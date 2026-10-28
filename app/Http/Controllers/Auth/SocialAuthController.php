<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\SocialAccount;
use App\Models\User;
use App\Services\DefaultShelvesService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirectResponse;

class SocialAuthController extends Controller
{
    private const PROVIDERS = ['google', 'facebook'];

    public function __construct(private DefaultShelvesService $defaultShelves) {}

    public function redirect(string $provider): SymfonyRedirectResponse
    {
        $this->ensureConfigured($provider);

        return Socialite::driver($provider)->redirect();
    }

    public function callback(Request $request, string $provider): RedirectResponse
    {
        $this->ensureConfigured($provider);

        try {
            $socialUser = Socialite::driver($provider)->user();
        } catch (\Throwable $exception) {
            report($exception);

            return redirect()->route('login')->with('error', __('auth.social_failed'));
        }

        $email = $socialUser->getEmail();

        if (! $email) {
            return redirect()->route('login')->with('error', __('auth.social_email_missing'));
        }

        $user = DB::transaction(function () use ($provider, $socialUser, $email) {
            $account = SocialAccount::query()
                ->where('provider', $provider)
                ->where('provider_id', $socialUser->getId())
                ->first();

            if ($account) {
                return $account->user;
            }

            $user = User::where('email', $email)->first();

            if (! $user) {
                $user = User::create([
                    'name' => $socialUser->getName() ?: Str::before($email, '@'),
                    'nickname' => null,
                    'email' => $email,
                    'password' => Hash::make(Str::random(40)),
                ]);

                $user->forceFill(['email_verified_at' => now()])->save();
                $this->defaultShelves->createFor($user);
            }

            $user->socialAccounts()->firstOrCreate(
                ['provider' => $provider],
                ['provider_id' => $socialUser->getId()],
            );

            return $user;
        });

        Auth::login($user);
        $request->session()->regenerate();

        if ($user->nickname === null) {
            return redirect()->route('profile.complete');
        }

        return redirect()->intended(route('dashboard', absolute: false));
    }

    private function ensureConfigured(string $provider): void
    {
        abort_unless(in_array($provider, self::PROVIDERS, true), 404);

        abort_unless(
            filled(config("services.{$provider}.client_id"))
            && filled(config("services.{$provider}.client_secret")),
            404,
        );
    }
}
