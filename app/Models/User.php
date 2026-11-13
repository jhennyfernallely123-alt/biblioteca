<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'nickname', 'email', 'password', 'default_view_mode', 'locale', 'theme'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $attributes = [
        'default_view_mode' => 'covers',
        'locale' => 'es',
        'theme' => 'light',
    ];

public function shelves(): HasMany
{
    return $this->hasMany(Shelf::class)->orderBy('position');
}

public function literaryUniverses(): HasMany
{
    return $this->hasMany(LiteraryUniverse::class)->orderBy('name');
}

    public function books(): HasMany
    {
        return $this->hasMany(Book::class);
    }

    public function catalogBooks(): HasMany
    {
        return $this->hasMany(CatalogBook::class, 'created_by');
    }

    public function decorations(): HasMany
    {
        return $this->hasMany(Decoration::class);
    }

    public function readingSessions(): HasMany
    {
        return $this->hasMany(ReadingSession::class);
    }

    public function socialAccounts(): HasMany
    {
        return $this->hasMany(SocialAccount::class);
    }

    public function cardDraws(): HasMany
    {
        return $this->hasMany(CardDraw::class);
    }

    public function userCards(): HasMany
    {
        return $this->hasMany(UserCard::class);
    }

    public function wallet(): HasOne
    {
        return $this->hasOne(UserWallet::class);
    }

    public function collectibles(): HasMany
    {
        return $this->hasMany(UserCollectible::class);
    }

    public function isMaster(): bool
    {
        return (bool) $this->is_master;
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'is_master' => 'boolean',
            'password' => 'hashed',
        ];
    }
}
