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
        'city',
        'password',
        'role',
    ];

    public function isAdmin(): bool
    {
        if ($this->role) {
            return $this->role === 'admin';
        }
        return in_array($this->name, ['Matheus', 'Dabiane']);
    }

    public function isSupervisor(): bool
    {
        if ($this->role) {
            return $this->role === 'supervisor';
        }
        return $this->name === 'LUCAS';
    }

    public function canSwitchCity(): bool
    {
        if ($this->role) {
            return in_array($this->role, ['admin', 'supervisor']);
        }
        return in_array($this->name, ['Matheus', 'Dabiane', 'LUCAS']);
    }

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

    public function city() {
        return $this->belongsTo(City::class);
    }

    public function getAuthPassword()
    {
        return $this->senha; // Substitui 'password' por 'senha'
    }
    
};
