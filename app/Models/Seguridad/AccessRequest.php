<?php

namespace App\Models\Seguridad;

use Illuminate\Database\Eloquent\Model;

class AccessRequest extends Model
{
    protected $table = 'access_requests';

    protected $fillable = [
        'email',
        'username',
        'message',
        'approved',
    ];

    protected $casts = [
        'approved' => 'boolean',
    ];
}
