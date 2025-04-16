<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Boletas extends Model
{
    protected $table = 'Boletas';
    protected $primaryKey = 'boleta_id';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    public function pedido()
    {
        return $this->belongsTo(Pedidos::class, 'ped_id', 'ped_id');
    }

    public function pagos()
    {
        return $this->hasMany(Pagos::class, 'boleta_id', 'boleta_id');
    }
}