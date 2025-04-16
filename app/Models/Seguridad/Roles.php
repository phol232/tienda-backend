<?php

namespace App\Models\Seguridad;

use Illuminate\Database\Eloquent\Model;

class Roles extends Model
{
    protected $table = 'Roles';
    protected $primaryKey = 'rol_id';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    public function usuarios()
    {
        return $this->belongsToMany(Usuarios::class, 'Usuario_Rol', 'rol_id', 'usr_id')
            ->withPivot('usr_rol_id', 'fecha_asignacion', 'asignado_por');
    }
}
