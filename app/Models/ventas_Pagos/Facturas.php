<?php

namespace App\Models\ventas_Pagos;

use App\Models\Pedidos\Pedidos;
use Illuminate\Database\Eloquent\Model;

class Facturas extends Model
{
    protected $table = 'Facturas';
    protected $primaryKey = 'factura_id';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'factura_id',
        'factura_numero',
        'factura_fecha',
        'factura_subtotal',
        'factura_impuestos',
        'factura_descuento',
        'factura_total',
        'factura_estado',
        'factura_notas',
        'ped_id'
    ];

    // Relación con Metodos de Pago (muchos a muchos)
    public function metodosPago()
    {
        return $this->belongsToMany(
            MetodosPago::class,
            'FacturasMetodos', // tabla pivote
            'factura_id',
            'met_id'
        )->withPivot('monto', 'fecha_pago', 'nota_pago'); // ajusta según tus campos extra
    }
    public function pedido()
    {
        return $this->belongsTo(Pedidos::class, 'ped_id', 'ped_id');
    }

}
