<?php
namespace App\Models\Pedidos;

use Illuminate\Database\Eloquent\Model;

class PedidosDetalle extends Model
{
    protected $table = 'Pedidos_Detalles';
    protected $primaryKey = 'det_id';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'det_id',
        'ped_id',
        'prod_id',
        'det_cantidad',
        'det_precio_unitario',
        'det_descuento',
        'det_impuesto',
        'det_subtotal',
        'det_nota'
    ];

    public function pedido()
    {
        return $this->belongsTo(Pedidos::class, 'ped_id', 'ped_id');
    }

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($detalle) {
            $tasaImpuesto  = 0.18;
            $tasaDescuento = 0.00;

            $detalle->det_subtotal  = $detalle->det_cantidad * $detalle->det_precio_unitario;
            $detalle->det_impuesto  = $detalle->det_subtotal * $tasaImpuesto;
            $detalle->det_descuento = $detalle->det_subtotal * $tasaDescuento;
        });

        static::saved(function ($detalle) {
            if ($detalle->pedido) {
                $detalle->pedido->recalcularTotales();
            }
        });

        static::deleted(function ($detalle) {
            if ($detalle->pedido) {
                $detalle->pedido->recalcularTotales();
            }
        });
    }
}
