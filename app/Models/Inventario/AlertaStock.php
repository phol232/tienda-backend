<?php

namespace App\Models\Inventario;

use Illuminate\Database\Eloquent\Model;
use App\Models\Productos_Proveedores\Productos;
use App\Models\Seguridad\Usuarios;

class AlertaStock extends Model
{
    protected $table = 'Alertas_Stock';
    protected $primaryKey = 'alerta_id';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'alerta_id',
        'alerta_tipo',
        'alerta_nivel',
        'alerta_mensaje',
        'alerta_fecha',
        'alerta_estado',
        'alerta_fecha_resolucion',
        'alerta_comentario',
        'prod_id',
        'usr_id_creador',
        'usr_id_resolucion',
    ];

    public function producto()
    {
        return $this->belongsTo(Productos::class, 'prod_id', 'pro_id');
    }

    public function creador()
    {
        return $this->belongsTo(Usuarios::class, 'usr_id_creador', 'usr_id');
    }

    public function resolucion()
    {
        return $this->belongsTo(Usuarios::class, 'usr_id_resolucion', 'usr_id');
    }
}
