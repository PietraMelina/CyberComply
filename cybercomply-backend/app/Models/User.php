<?php

namespace App\Models;

use App\Models\DB1\Role;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use HasFactory;
    use Notifiable;

    protected $connection = 'db1';

    protected $table = 'users';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'client_id',
        'nif',
        'display_name',
        'email',
        'password_hash',
        'role_id',
        'is_active',
        'email_verified_at',
        'mfa_enabled',
        'mfa_secret',
        'mfa_backup_codes',
        'avatar_asset_id',
        'accepted_terms_at',
        'accepted_terms_version',
        'last_login_at',
    ];

    protected $hidden = [
        'password_hash',
        'mfa_secret',
        'mfa_backup_codes',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'mfa_enabled' => 'boolean',
        'mfa_backup_codes' => 'array',
        'email_verified_at' => 'datetime',
        'accepted_terms_at' => 'datetime',
        'last_login_at' => 'datetime',
    ];

    public function getAuthPassword(): string
    {
        return (string) $this->password_hash;
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_id', 'id');
    }

    public function getJWTIdentifier(): mixed
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims(): array
    {
        return [
            'client_id' => $this->client_id,
            'role' => $this->role?->name,
        ];
    }

    public function getNameAttribute(): string
    {
        if (!empty($this->display_name)) {
            return (string) $this->display_name;
        }

        return (string) Str::before((string) $this->email, '@');
    }

    public function setNameAttribute(string $value): void
    {
        $this->attributes['display_name'] = $value;
    }
}
