<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Alertas_Stock extends Model
{
    protected $table = 'Alertas_Stock';
    protected $primaryKey = 'alerta_id';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    public function producto()
    {
        return $this->belongsTo(Productos::class, 'prod_id', 'pro_id');
    }

    public function usuarioCreador()
    {
        return $this->belongsTo(Usuarios::class, 'usr_id_creador', 'usr_id');
    }

    public function usuarioResolucion()
    {
        return $this->belongsTo(Usuarios::class, 'usr_id_resolucion', 'usr_id');
    }

    public function notificaciones()
    {
        return $this->hasMany(Notificaciones_Alertas::class, 'alerta_id', 'alerta_id');
    }
}