<?php

namespace Modules\Member\App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Member extends Authenticatable
{
    use SoftDeletes;

    protected $table = 'members';

    protected $fillable = [
        'username',
        'nickname',
        'password',
        'avatar',
        'email',
        'mobile',
        'status',
        'remark',
        'last_login_ip',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'last_login_at' => 'datetime',
            'status' => 'integer',
        ];
    }

    public function isEnabled(): bool
    {
        return (int) $this->status === 1;
    }

    public static function findByLoginIdentifier(string $identifier): ?self
    {
        $identifier = trim($identifier);
        if ($identifier === '') {
            return null;
        }

        return static::query()
            ->where('username', $identifier)
            ->orWhere('email', $identifier)
            ->orWhere('mobile', $identifier)
            ->first();
    }
}
