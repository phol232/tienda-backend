<?php

namespace App\Models\Seguridad;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use App\Models\Pedidos;
use App\Models\Seguridad\Roles;
use App\Models\Seguridad\Usuarios_Perfil;

class Usuarios extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $table = 'Usuarios';
    protected $primaryKey = 'usr_id';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'usr_id',
        'usr_user',
        'usr_email',
        'usr_password',
        'usr_estado'
    ];

    protected $hidden = [
        'usr_password',
    ];

    public function getAuthIdentifierName()
    {
        return 'usr_email';
    }

    public function getAuthPassword()
    {
        return $this->usr_password;
    }

    public function perfil()
    {
        return $this->hasOne(Usuarios_Perfil::class, 'usrp_id', 'usr_id');
    }

    public function roles()
    {
        return $this->belongsToMany(Roles::class, 'Usuario_Rol', 'usr_id', 'rol_id')
            ->withPivot('usr_rol_id', 'fecha_asignacion', 'asignado_por');
    }

    public function pedidos()
    {
        return $this->hasMany(Pedidos::class, 'usr_id', 'usr_id');
    }
}
