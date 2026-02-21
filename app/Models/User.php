<?php

namespace App\Models;

use Carbon\Carbon;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasAvatar;
use Filament\Models\Contracts\HasTenants;
use Filament\Panel;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Jeffgreco13\FilamentBreezy\Traits\TwoFactorAuthenticatable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Yebor974\Filament\RenewPassword\RenewPasswordPlugin;
use Yebor974\Filament\RenewPassword\Traits\RenewPassword;

class User extends Authenticatable implements FilamentUser, HasTenants, MustVerifyEmail, HasAvatar
{
    use HasApiTokens;
    use HasRoles, TwoFactorAuthenticatable, RenewPassword, SoftDeletes;

    /** @use HasFactory<UserFactory> */
    use HasFactory;

    use Notifiable;

    protected $fillable = [
        'name',
        'first_name',
        'last_name',
        'email',
        'password',
        'avatar_url',
        'phone',
        'bio',
        'job_title',
        'department',
        'status',
        'last_login_at',
        'last_login_ip',
        'invitation_sent_at',
        'invitation_accepted_at',
        'invitation_token',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'invitation_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    protected $casts = [
        'email_verified_at'        => 'datetime',
        'last_login_at'            => 'datetime',
        'invitation_sent_at'       => 'datetime',
        'invitation_accepted_at'   => 'datetime',
        'password'                 => 'hashed',
    ];

    public function canAccessPanel(Panel $panel): bool
    {
        return true;
    }

    public function canAccessTenant(Model $tenant): bool
    {
        return true;
    }

    public function getFilamentAvatarUrl(): ?string
    {
        if (! $this->avatar_url) {
            // Fallback to a UI-Avatar if no image is uploaded
            return 'https://ui-avatars.com/api/?name=' . urlencode($this->name) . '&color=7C3AED&background=EDE9FE';
        }

        // Explicitly use the 'public' disk you defined in your form
        return Storage::url($this->avatar_url);
    }

    /** @return Collection<int,Team> */
    public function getTenants(Panel $panel): Collection
    {
        return Team::all();
    }

    public function isAdmin(): bool
    {
        return $this->hasRole('super_admin') || $this->can('access_admin_panel');
    }

    public function getFullNameAttribute(): string
    {
        if ($this->first_name || $this->last_name) {
            return trim("{$this->first_name} {$this->last_name}");
        }

        return $this->name;
    }

    public function isInvitationPending(): bool
    {
        return $this->invitation_token !== null
            && $this->invitation_accepted_at === null;
    }

    public function hasAcceptedInvitation(): bool
    {
        return $this->invitation_accepted_at !== null;
    }

    public function needRenewPassword(): bool
    {
        $plugin = RenewPasswordPlugin::get();

        return (
            !is_null($plugin->getPasswordExpiresIn())
            && Carbon::parse($this->{$plugin->getTimestampColumn()})->addDays($plugin->getPasswordExpiresIn()) < now()
        ) || (
            $plugin->getForceRenewPassword()
            && $this->{$plugin->getForceRenewColumn()}
        );
    }
}
