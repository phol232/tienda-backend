<?php
namespace App\Models\Productos_Proveedores;

use Illuminate\Database\Eloquent\Relations\Pivot;

class Producto_Proveedor extends Pivot
{
    protected $table = 'Producto_Proveedor';
    protected $primaryKey = 'prod_prov_id';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'prod_prov_id',
        'prod_id',
        'prov_id',
        'precio_proveedor',
        'fecha_inicio_suministro',
        'fecha_fin_suministro',
        'notas',
        'estado',
    ];
}
