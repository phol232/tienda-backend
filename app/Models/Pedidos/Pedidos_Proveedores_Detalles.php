<?php

namespace App\Models\Pedidos;

use Illuminate\Database\Eloquent\Model;

class Pedidos_Proveedores_Detalles extends Model
{
    protected $table = 'Pedidos_Proveedores_Detalles';
    protected $primaryKey = 'ppdet_id';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    public function pedidoProveedor()
    {
        return $this->belongsTo(Pedidos_Proveedores::class, 'pedprov_id', 'pedprov_id');
    }

    public function producto()
    {
        return $this->belongsTo(Productos::class, 'pro_id', 'pro_id');
    }
}
