<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Configuracion_Alertas extends Model
{
    protected $table = 'Configuracion_Alertas';
    protected $primaryKey = 'config_id';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    public function usuario()
    {
        return $this->belongsTo(Usuarios::class, 'usr_id', 'usr_id');
    }
}