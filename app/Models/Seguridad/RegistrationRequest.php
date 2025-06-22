<?php

namespace App\Models\Seguridad;

use Illuminate\Database\Eloquent\Model;

class RegistrationRequest extends Model
{
    protected $table = 'registration_requests';

    protected $fillable = [
        'email',
        'username',
        'message',
        'password',
        'approved',
    ];

    protected $casts = [
        'approved' => 'boolean',
    ];
}
