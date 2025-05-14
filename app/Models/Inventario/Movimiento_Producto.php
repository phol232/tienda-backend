<?php

namespace App\Models\Inventario;

use Illuminate\Database\Eloquent\Model;
use App\Models\Inventario\Movimientos_Inventario;
use App\Models\Productos_Proveedores\Productos;

class Movimiento_Producto extends Model
{
    protected $table = 'Movimiento_Producto';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'mov_id',
        'prod_id',
        'movprod_cantidad',
        'movprod_costo_unitario',
    ];

    public function movimiento()
    {
        return $this->belongsTo(Movimientos_Inventario::class, 'mov_id', 'mov_id');
    }

    public function producto()
    {
        return $this->belongsTo(Productos::class, 'prod_id', 'prod_id');
    }

}
