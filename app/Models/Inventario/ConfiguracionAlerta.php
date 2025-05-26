<?php

namespace App\Models\Inventario;

use Illuminate\Database\Eloquent\Model;
use App\Models\Seguridad\Usuarios;

class ConfiguracionAlerta extends Model
{
    protected $table = 'Configuracion_Alertas';
    protected $primaryKey = 'config_alerta_id';
    const CREATED_AT = 'config_creado_en';
    const UPDATED_AT = 'config_actualizado_en';

    protected $fillable = [
        'config_nombre',
        'config_tipo',
        'config_umbral_valor',
        'config_aplicabilidad',
        'id_referencia_aplicabilidad',
        'config_descripcion',
        'config_nivel_alerta_default',
        'config_estado',
        'config_creado_por_usr_id',
    ];

    protected $casts = [
        'config_creado_en' => 'datetime',
        'config_actualizado_en' => 'datetime',
        'config_umbral_valor' => 'integer',
        // ENUMs se manejan como strings
    ];

    public function creadoPor()
    {
        return $this->belongsTo(Usuarios::class, 'config_creado_por_usr_id', 'usr_id');
    }

    public function alertasStockOriginadas()
    {
        return $this->hasMany(AlertaStock::class, 'config_alerta_id_origen', 'config_alerta_id');
    }

}
