<?php
namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'tg_id',
        'username',
        'first_name',
        'last_name',
        'email_verified_at',
        'email',
        'password',
        'phone',
        'meta',
    ];

    protected $casts = [
        'email' => 'string',
        'meta'  => 'array',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
        ];
    }

    /**
     * Find a user by Telegram ID (tg_id).
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int|string $tgId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFindByTgId($query, $tgId)
    {
        return $query->where('tg_id', $tgId);
    }

    /**
     * Get the user's meta data.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $username
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function getXuiDataAttribute()
    {
        return data_get($this->meta, 'xui_data');
    }

    /**
     * Get the user's meta data.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $username
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function getTelegramDataAttribute()
    {
        return data_get($this->meta, 'telegram_data');
    }

    /**
     * Get the user's Telegram Username.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $username
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function getTelegramUsernameAttribute()
    {
        $tgData = data_get($this->meta, 'telegram_data');
        return $tgData['username'] ?? $tgData['id'];
    }
}
