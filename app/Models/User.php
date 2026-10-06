<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;
use Spatie\Permission\Traits\HasRoles;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string|null $phone
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'email', 'phone', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements JWTSubject
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    /**
     * Spatie Permission default guard name.
     */
    protected string $guard_name = 'web';

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     */
    public function getJWTIdentifier(): mixed
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     */
    public function getJWTCustomClaims(): array
    {
        return [
            'name' => $this->name,
            'email' => $this->email,
            'roles' => $this->getRoleNames()->values()->all(),
            'permissions' => $this->getAllPermissions()->pluck('name')->values()->all(),
        ];
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
            'password' => 'hashed',
        ];
    }

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        $initials = Str::initials($this->name, true);

        return Str::length($initials) > 1
            ? Str::substr($initials, 0, 1).Str::substr($initials, -1)
            : $initials;
    }

    /**
     * Normalize Indonesian phone number to international 62 format (digits only).
     */
    public static function normalizePhone(?string $phone): ?string
    {
        if (! $phone) {
            return null;
        }

        // Remove any non-numeric characters
        $digits = preg_replace('/\D+/', '', $phone);

        if (empty($digits)) {
            return null;
        }

        // If starts with 0 (e.g. 08123456789), replace leading 0 with 62
        if (str_starts_with($digits, '0')) {
            $digits = '62'.substr($digits, 1);
        } elseif (str_starts_with($digits, '8')) {
            $digits = '62'.$digits;
        }

        return $digits;
    }

    /**
     * Get clean formatted phone number (+62 812-xxxx-xxxx)
     */
    public function formattedPhone(): ?string
    {
        if (! $this->phone) {
            return null;
        }

        $digits = self::normalizePhone($this->phone);
        if (! $digits) {
            return $this->phone;
        }

        // Format as +62 8xx-xxxx-xxxx
        if (str_starts_with($digits, '62') && strlen($digits) >= 10) {
            $prefix = '+62';
            $rest = substr($digits, 2);
            $part1 = substr($rest, 0, 3);
            $part2 = substr($rest, 3, 4);
            $part3 = substr($rest, 7);

            return "{$prefix} {$part1}-{$part2}".($part3 ? "-{$part3}" : '');
        }

        return '+'.$digits;
    }

    /**
     * Get WhatsApp direct chat link (https://wa.me/628123456789)
     */
    public function whatsappUrl(): ?string
    {
        $digits = self::normalizePhone($this->phone);

        return $digits ? "https://wa.me/{$digits}" : null;
    }
}
