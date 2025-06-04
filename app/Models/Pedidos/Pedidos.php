<?php
namespace App\Models\Pedidos;

use App\Models\ventas_Pagos\Boletas;
use App\Models\ventas_Pagos\Facturas;
use Illuminate\Database\Eloquent\Model;

class Pedidos extends Model
{
    protected $table = 'Pedidos';
    protected $primaryKey = 'ped_id';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'ped_id', 'ped_fecha', 'cli_id', 'usr_id',
        'ped_subtotal', 'ped_impuestos', 'ped_descuento',
        'ped_total', 'ped_estado', 'ped_tipo',
        'ped_forma_entrega', 'ped_notas'
    ];

    // Relación con detalles de pedido
    public function detalles()
    {
        return $this->hasMany(PedidosDetalle::class, 'ped_id', 'ped_id');
    }

    // Relación con boletas
    public function boletas()
    {
        return $this->hasMany(Boletas::class, 'ped_id', 'ped_id');
    }

    // Relación con facturas
    public function facturas()
    {
        return $this->hasMany(Facturas::class, 'ped_id', 'ped_id');
    }

    // Recalcular totales del pedido
    public function recalcularTotales()
    {
        $subtotal   = 0;
        $impuestos  = 0;
        $descuento  = 0;

        foreach ($this->detalles as $d) {
            $subtotal  += $d->det_subtotal;
            $impuestos += $d->det_impuesto;
            $descuento += $d->det_descuento;
        }

        $this->ped_subtotal  = $subtotal;
        $this->ped_impuestos = $impuestos;
        $this->ped_descuento = $descuento;
        $this->ped_total     = $subtotal + $impuestos - $descuento;
        $this->saveQuietly();
    }
}
