<?php

namespace App\Models\ventas_Pagos;

use Illuminate\Database\Eloquent\Model;

class FacturasMetodos extends Model
{
    protected $table = 'FacturasMetodos';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'factura_id',
        'met_id',
        'monto',
        'fecha_pago',
        'nota_pago'
    ];
}

