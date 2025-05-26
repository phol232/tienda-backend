<?php

namespace App\Models\Inventario;

use Illuminate\Database\Eloquent\Model;
use App\Models\Productos_Proveedores\Productos;
use App\Models\Seguridad\Usuarios;

class AlertaStock extends Model
{
    protected $table = 'Alertas_Stock';
    protected $primaryKey = 'alerta_stock_id';

    const CREATED_AT = 'fecha_generacion';
    const UPDATED_AT = null;
    public $timestamps = false;

    protected $fillable = [
        'prod_id',
        'config_alerta_id_origen',
        'stock_capturado',
        'umbral_evaluado',
        'alerta_tipo_generada',
        'alerta_nivel_generado',
        'mensaje_automatico',
        'estado_alerta',
        'fecha_resolucion',
        'comentario_resolucion',
        'resuelta_por_usr_id',
        'creada_por_proceso',
    ];

    protected $casts = [
        'fecha_generacion' => 'datetime',
        'fecha_resolucion' => 'datetime',
        'stock_capturado' => 'integer',
        'umbral_evaluado' => 'integer',
    ];

    public function producto()
    {
        return $this->belongsTo(Productos::class, 'prod_id', 'pro_id');
    }

    public function configuracionOrigen()
    {
        return $this->belongsTo(ConfiguracionAlerta::class, 'config_alerta_id_origen', 'config_alerta_id');
    }

    public function resueltaPor()
    {
        return $this->belongsTo(Usuarios::class, 'resuelta_por_usr_id', 'usr_id');
    }

    public function notificaciones()
    {
        return $this->hasMany(NotificacionAlerta::class, 'alerta_stock_id', 'alerta_stock_id');
    }
}
