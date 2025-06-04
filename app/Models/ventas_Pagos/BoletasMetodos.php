<?php

namespace App\Models\ventas_Pagos;

use Illuminate\Database\Eloquent\Model;

class BoletasMetodos extends Model
{
    protected $table = 'Boleta_Metodo_Pago';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'boleta_id',
        'met_id',
        'monto',
        'fecha_pago',
        'nota_pago'
    ];
}
