<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Support\Facades\Crypt;

class CorretorSeguradora extends Pivot
{
    protected $table = 'corretor_seguradora';

    public $incrementing = true;

    protected $fillable = [
        'corretor_id',
        'seguradora_id',
        'login_username',
        'login_password',
        'extra_credentials',
        'is_enabled',
    ];

    protected $casts = [
        'extra_credentials' => 'array',
        'is_enabled' => 'boolean',
    ];

    public function setLoginUsernameAttribute(?string $value): void
    {
        $this->attributes['login_username'] = $value ? Crypt::encryptString($value) : null;
    }

    public function getLoginUsernameAttribute(?string $value): ?string
    {
        return $value ? Crypt::decryptString($value) : null;
    }

    public function setLoginPasswordAttribute(?string $value): void
    {
        $this->attributes['login_password'] = $value ? Crypt::encryptString($value) : null;
    }

    public function getLoginPasswordAttribute(?string $value): ?string
    {
        return $value ? Crypt::decryptString($value) : null;
    }
}
