<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notificaciones_Alertas extends Model
{
    protected $table = 'Notificaciones_Alertas';
    protected $primaryKey = 'notif_id';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    public function alerta()
    {
        return $this->belongsTo(Alertas_Stock::class, 'alerta_id', 'alerta_id');
    }

    public function usuario()
    {
        return $this->belongsTo(Usuarios::class, 'usr_id', 'usr_id');
    }
}