<?php
namespace App\Models\ventas_Pagos;

use App\Models\Pedidos\Pedidos;
use Illuminate\Database\Eloquent\Model;

class Boletas extends Model
{
    protected $table = 'Boletas';
    protected $primaryKey = 'boleta_id';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'boleta_id',
        'boleta_numero',
        'boleta_fecha',
        'boleta_subtotal',
        'boleta_impuestos',
        'boleta_descuento',
        'boleta_total',
        'boleta_estado',
        'boleta_notas',
        'payment_id',
        'ped_id'
    ];

    // Relación con Metodos de Pago (muchos a muchos)
    public function metodosPago()
    {
        return $this->belongsToMany(
            MetodosPago::class,
            'Boleta_Metodo_Pago',
            'boleta_id',
            'met_id'
        )->withPivot('monto', 'referencia', 'fecha_registro');
    }

    public function pedido()
    {
        return $this->belongsTo(Pedidos::class, 'ped_id', 'ped_id');
    }

}

