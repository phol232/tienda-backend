<?php

namespace App\Models\ventas_Pagos;

use Illuminate\Database\Eloquent\Model;

class MetodosPago extends Model
{
    protected $table = 'Metodos_Pago';
    protected $primaryKey = 'met_id';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'met_id',
        'met_nombre',
        'met_descripcion',
        'met_estado',
        'met_tipo',
        'met_banco'
    ];
    public function boletas()
    {
        return $this->belongsToMany(
            Boletas::class,
            'BoletasMetodos',
            'met_id',
            'boleta_id'
        )->withPivot('monto', 'fecha_pago', 'nota_pago');
    }

    // Métodos de pago usados en facturas
    public function facturas()
    {
        return $this->belongsToMany(
            Facturas::class,
            'FacturasMetodos',
            'met_id',
            'factura_id'
        )->withPivot('monto', 'fecha_pago', 'nota_pago');
    }
}

