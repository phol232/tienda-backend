<?php
namespace App\Models\Productos_Proveedores;

use Illuminate\Database\Eloquent\Model;

class Producto_Detalles extends Model
{
    protected $table = 'Producto_Detalles';
    protected $primaryKey = 'prod_id';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'prod_id',
        'prod_descripcion',
        'prod_precio_compra',
        'prod_stock_minimo',
        'prod_stock_maximo',
        'prod_fecha_caducidad',
        'prod_imagen',
    ];

    public function producto()
    {
        return $this->belongsTo(Productos::class, 'prod_id', 'pro_id');
    }
}
