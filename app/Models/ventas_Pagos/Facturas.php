<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Facturas extends Model
{
    protected $table = 'Facturas';
    protected $primaryKey = 'factura_id';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    public function pedido()
    {
        return $this->belongsTo(Pedidos::class, 'ped_id', 'ped_id');
    }

    public function pagos()
    {
        return $this->hasMany(Pagos::class, 'factura_id', 'factura_id');
    }
}