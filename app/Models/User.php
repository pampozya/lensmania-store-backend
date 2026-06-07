<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;

class User extends Authenticatable implements FilamentUser
{
    use HasFactory;
    use Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'email_verified_at',
        'is_admin',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_admin' => 'boolean',
    ];

    public function canAccessPanel(Panel $panel): bool
    {
        return (bool) $this->is_admin;
    }

    public function entitlements()
    {
        return $this->hasMany(Entitlement::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function licenses()
    {
        return $this->hasMany(License::class);
    }

    public function supportNotes()
    {
        return $this->hasMany(SupportNote::class);
    }

    public function emailDeliveryLogs()
    {
        return $this->hasMany(EmailDeliveryLog::class);
    }

    public function timelineAuditLogs()
    {
        return $this->hasMany(AuditLog::class, 'subject_id')
            ->where('subject_type', self::class);
    }

    public function setPasswordAttribute(string $value): void
    {
        $this->attributes['password'] = $value ? Hash::make($value) : $value;
    }
}
