<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name', 'email', 'password', 'role', 'is_active',
        'last_login_at', 'last_login_ip', 'login_count',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'last_login_at'     => 'datetime',
        'is_active'         => 'boolean',
        'password'          => 'hashed',
    ];

    // ─── Roles ───────────────────────────────────────────────
    public function isAdmin(): bool   { return $this->role === 'admin'; }
    public function isManager(): bool { return in_array($this->role, ['admin', 'manager']); }
    public function isViewer(): bool  { return $this->role === 'viewer'; }

    public function can($ability, $arguments = []): bool
    {
        return match($ability) {
            'admin'          => $this->isAdmin(),
            'addKeyword'     => $this->isManager(),
            'editKeyword'    => $this->isManager(),
            'deleteKeyword'  => $this->isAdmin(),
            'viewAuditLog'   => $this->isAdmin(),
            'manageUsers'    => $this->isAdmin(),
            'apiSettings'    => $this->isAdmin(),
            default          => false,
        };
    }

    // ─── Relationships ────────────────────────────────────────
    public function keywords()    { return $this->hasMany(Keyword::class, 'created_by'); }
    public function activityLogs(){ return $this->hasMany(ActivityLog::class); }
    public function loginLogs()   { return $this->hasMany(LoginLog::class); }

    // ─── Helpers ─────────────────────────────────────────────
    public function getInitialsAttribute(): string
    {
        return collect(explode(' ', $this->name))
            ->map(fn($w) => strtoupper(substr($w, 0, 1)))
            ->take(2)
            ->implode('');
    }

    public function getAvatarColorAttribute(): string
    {
        $colors = ['blue', 'green', 'amber', 'purple'];
        return $colors[crc32($this->name) % count($colors)];
    }

    public function recordLogin(string $ip, string $ua): void
    {
        $this->update([
            'last_login_at'  => now(),
            'last_login_ip'  => $ip,
            'login_count'    => $this->login_count + 1,
        ]);
    }
}
