<?php

namespace App\Models\Seguridad;

use Illuminate\Database\Eloquent\Model;

class Usuario_Rol extends Model
{
    protected $table = 'Usuario_Rol';
    protected $primaryKey = 'usr_rol_id';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    public function usuario()
    {
        return $this->belongsTo(Usuarios::class, 'usr_id', 'usr_id');
    }

    public function rol()
    {
        return $this->belongsTo(Roles::class, 'rol_id', 'rol_id');
    }
}
