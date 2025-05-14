<?php

namespace App\Models\Inventario;

use Illuminate\Database\Eloquent\Model;
use App\Models\Seguridad\Usuarios;

class ConfiguracionAlerta extends Model
{
    protected $table = 'Configuracion_Alertas';
    protected $primaryKey = 'config_id';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'config_id',
        'config_nombre',
        'config_tipo',
        'config_umbral',
        'config_porcentaje',
        'config_periodo',
        'config_estado',
        'config_destinatarios',
        'config_ultima_revision',
        'usr_id',
    ];

    public function usuario()
    {
        return $this->belongsTo(Usuarios::class, 'usr_id', 'usr_id');
    }
}
