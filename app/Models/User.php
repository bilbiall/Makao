<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Helpers\EmailHelper;
use App\Helpers\EmailTemplateHelper;
use App\Helpers\SmsHelper;

//for alerts
use TomatoPHP\FilamentAlerts\Traits\InteractsWithNotifications;




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
        'email',
        'phone_number',
        'password',
        'role',
        'location_id',
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
            'password' => 'hashed',
        ];
    }
    
    protected static function booted()
    {
        static::created(function ($user) {
            try {
                $actor = auth()->id() ?? null;
                \App\Helpers\ActivityLogger::log('create_user', $actor, "New user created: {$user->name} ({$user->email}) - Role: {$user->role}");
            } catch (\Throwable $e) {
                // ignore
            }
        });
        
        static::updated(function ($user) {
            try {
                $actor = auth()->id() ?? null;
                $changes = [];
                
                if ($user->wasChanged('name')) {
                    $changes[] = "name from '{$user->getOriginal('name')}' to '{$user->name}'";
                }
                if ($user->wasChanged('email')) {
                    $changes[] = "email from '{$user->getOriginal('email')}' to '{$user->email}'";
                }
                if ($user->wasChanged('role')) {
                    $changes[] = "role from '{$user->getOriginal('role')}' to '{$user->role}'";
                }
                if ($user->wasChanged('phone_number')) {
                    $changes[] = "phone number";
                }
                
                if (!empty($changes)) {
                    $details = "Updated user {$user->name}: " . implode(', ', $changes);
                    \App\Helpers\ActivityLogger::log('update_user', $actor, $details);
                }
            } catch (\Throwable $e) {
                // ignore
            }
        });
        
        static::deleted(function ($user) {
            try {
                $actor = auth()->id() ?? null;
                \App\Helpers\ActivityLogger::log('delete_user', $actor, "User deleted: {$user->name} ({$user->email}) - Role: {$user->role}");
            } catch (\Throwable $e) {
                // ignore
            }
        });
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isTenant(): bool
    {
        return $this->role === 'tenant';
    }

    public function isCaretaker(): bool
    {
        return $this->role === 'caretaker';
    }

    //relationship with tenants
    public function tenant()
    {
        return $this->hasOne(Tenant::class);
    }

    // relationship with location (for caretakers)
    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    // messages sent by this user
    public function messagesSent()
    {
        return $this->hasMany(Message::class, 'sender_id');
    }

    // messages received by this user
    public function messagesReceived()
    {
        return $this->hasMany(Message::class, 'receiver_id');
    }

    //full label attribute for the users search
    // In User.php model
    public function getFullLabelAttribute()
    {
        return "{$this->name} ({$this->email})";
    }

    /**
     * Override default reset notification to send via SMTP and optional SMS.
     */
    public function sendPasswordResetNotification($token)
    {
        // Email
        if ($this->email) {
            $resetUrl = url(route('password.reset', [
                'token' => $token,
                'email' => $this->email,
            ], false));
            $body = EmailTemplateHelper::render('password_reset', [
                'tenant_name' => $this->name ?? $this->email,
                'reset_url' => $resetUrl,
                'reset_code' => $token,
            ]);

            try {
                EmailHelper::send(
                    $this->email,
                    'Password Reset Instructions',
                    $body
                );
            } catch (\Throwable $e) {
                // ignore email failures (e.g. SMTP not configured) - SMS below is a fallback
            }
        }

        // Optional SMS only for tenants with phone numbers
        if ($this->tenant && $this->tenant->phone_number) {
            $settings = \App\Models\Setting::singleton();
            $payload = $settings->payload ?? [];
            $template = $payload['template_password_reset_sms'] ?? 'Hi {tenant_name}, use this code to reset your password: {reset_code}. - {app_name}';

            $sms = str_replace(
                ['{tenant_name}', '{reset_code}', '{app_name}'],
                [$this->tenant->tenant_name ?? $this->name, $token, \App\Helpers\AppHelper::getAppName()],
                $template
            );

            try {
                SmsHelper::sendSms($this->tenant->phone_number, $sms);
            } catch (\Throwable $e) {
                // ignore SMS errors
            }
        }
    }




}