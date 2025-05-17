<?php

namespace App\Models\Inventario;

use Illuminate\Database\Eloquent\Model;
use App\Models\Seguridad\Usuarios;

class NotificacionAlerta extends Model
{
    protected $table = 'Notificaciones_Alertas';
    protected $primaryKey = 'notificacion_id';

    const CREATED_AT = 'fecha_intento_envio';
    const UPDATED_AT = 'fecha_actualizacion_estado';

    protected $fillable = [
        'alerta_stock_id',
        'usr_id_destinatario',
        'canal_notificacion',
        'estado_notificacion',
        'asunto_mensaje',
        'cuerpo_mensaje',
        'datos_respuesta_canal',
    ];

    protected $casts = [
        'fecha_intento_envio' => 'datetime',
        'fecha_actualizacion_estado' => 'datetime',
    ];

    public function alertaStock()
    {
        return $this->belongsTo(AlertaStock::class, 'alerta_stock_id', 'alerta_stock_id');
    }

    public function destinatario()
    {
        return $this->belongsTo(Usuarios::class, 'usr_id_destinatario', 'usr_id');
    }
}
