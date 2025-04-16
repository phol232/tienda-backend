<?php

namespace App\Models\Pedidos;

use Illuminate\Database\Eloquent\Model;

class Pedidos_Detalles extends Model
{
    protected $table = 'Pedidos_Detalles';
    protected $primaryKey = 'det_id';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    public function pedido()
    {
        return $this->belongsTo(Pedidos::class, 'ped_id', 'ped_id');
    }

    public function producto()
    {
        return $this->belongsTo(Productos::class, 'prod_id', 'pro_id');
    }
}
