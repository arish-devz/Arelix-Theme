<?php
namespace Pterodactyl\Models;
use Pterodactyl\Rules\Username;
use Pterodactyl\Facades\Activity;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rules\In;
use Illuminate\Auth\Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Builder;
use Pterodactyl\Models\Traits\HasAccessTokens;
use Illuminate\Auth\Passwords\CanResetPassword;
use Pterodactyl\Traits\Helpers\AvailableLanguages;
use Pterodactyl\Traits\Helpers\ThemeLanguages;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\Access\Authorizable;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Pterodactyl\Notifications\SendPasswordReset as ResetPasswordNotification;
use Laragear\WebAuthn\Contracts\WebAuthnAuthenticatable;

class User extends Model implements
    AuthenticatableContract,
    AuthorizableContract,
    CanResetPasswordContract,
    WebAuthnAuthenticatable
{
    use Authenticatable;
    use Authorizable;
    use AvailableLanguages;
    use ThemeLanguages;
    use CanResetPassword;
    use HasAccessTokens;
    use Notifiable;
    use \Laragear\WebAuthn\WebAuthnAuthentication;
    public const USER_LEVEL_USER = 0;
    public const USER_LEVEL_ADMIN = 1;
    public const RESOURCE_NAME = 'user';
    protected string $accessLevel = 'all';
    protected $table = 'users';
    protected $fillable = [
        'external_id',
        'username',
        'email',
        'name_first',
        'name_last',
        'password',
        'language',
        'use_totp',
        'totp_secret',
        'totp_authenticated_at',
        'gravatar',
        'root_admin',
        'country',
        'address',
        'zip_code',
        'credit',
    ];
    protected $casts = [
        'root_admin' => 'boolean',
        'use_totp' => 'boolean',
        'gravatar' => 'boolean',
        'totp_authenticated_at' => 'datetime',
        'credit' => 'decimal:2',
    ];
    protected $hidden = ['password', 'remember_token', 'totp_secret', 'totp_authenticated_at'];
    protected $attributes = [
        'external_id' => null,
        'root_admin' => false,
        'language' => 'en',
        'use_totp' => false,
        'totp_secret' => null,
    ];
    public static array $validationRules = [
        'uuid' => 'required|string|size:36|unique:users,uuid',
        'email' => 'required|email|between:1,191|unique:users,email',
        'external_id' => 'sometimes|nullable|string|max:191|unique:users,external_id',
        'username' => 'required|between:1,191|unique:users,username',
        'name_first' => 'required|string|between:1,191',
        'name_last' => 'required|string|between:1,191',
        'password' => 'sometimes|nullable|string',
        'root_admin' => 'boolean',
        'language' => 'string',
        'use_totp' => 'boolean',
        'totp_secret' => 'nullable|string',
        'country' => 'nullable|string|max:191',
        'address' => 'nullable|string|max:191',
        'zip_code' => 'nullable|string|max:191',
        'credit' => 'numeric|min:0',
    ];
    public static function getRules(): array
    {
        $rules = parent::getRules();
        $rules['language'][] = new In(array_keys((new self())->getThemeLanguages()));
        $rules['username'][] = new Username();
        return $rules;
    }
    public function toVueObject(): array
    {
        $data = Collection::make($this->toArray())->except(['id', 'external_id'])->toArray();
        $data['country'] = $this->country;
        $data['address'] = $this->address;
        $data['zip_code'] = $this->zip_code;
        $data['has_admin_permissions'] = $this->root_admin || !is_null($this->permission_role_id);
        $data['has_global_view'] = $this->hasAdminPermission('arelix.global.view_all_servers');
        return $data;
    }
    public function sendPasswordResetNotification($token)
    {
        Activity::event('auth:reset-password')
            ->withRequestMetadata()
            ->subject($this)
            ->log('sending password reset email');
        $this->notify(new ResetPasswordNotification($token));
    }
    public function setUsernameAttribute(string $value)
    {
        $this->attributes['username'] = $value;
    }
    public function getNameAttribute(): string
    {
        return trim($this->name_first . ' ' . $this->name_last);
    }
    public function servers(): HasMany
    {
        return $this->hasMany(Server::class, 'owner_id');
    }
    public function apiKeys(): HasMany
    {
        return $this->hasMany(ApiKey::class)
            ->where('key_type', ApiKey::TYPE_ACCOUNT);
    }
    public function recoveryTokens(): HasMany
    {
        return $this->hasMany(RecoveryToken::class);
    }
    public function sshKeys(): HasMany
    {
        return $this->hasMany(UserSSHKey::class);
    }
    public function activity(): MorphToMany
    {
        return $this->morphToMany(ActivityLog::class, 'subject', 'activity_log_subjects');
    }
    public function accessibleServers(): Builder
    {
        return Server::query()
            ->select('servers.*')
            ->leftJoin('subusers', 'subusers.server_id', '=', 'servers.id')
            ->where(function (Builder $builder) {
                $builder->where('servers.owner_id', $this->id)->orWhere('subusers.user_id', $this->id);
            })
            ->groupBy('servers.id');
    }
    public function loginHistory(): HasMany
    {
        return $this->hasMany(UserLoginHistory::class);
    }
    public function permissionRole()
    {
        return $this->belongsTo(\Pterodactyl\Models\Arelix\PermissionRole::class, 'permission_role_id');
    }
    public function hasAdminPermission(string $permission): bool
    {
        if ($this->root_admin) {
            return true;
        }
        if (!$this->permissionRole) {
            return false;
        }
        return $this->permissionRole->hasPermission($permission);
    }

    public function integrations(): HasMany
    {
        return $this->hasMany(UserIntegration::class);
    }
}
