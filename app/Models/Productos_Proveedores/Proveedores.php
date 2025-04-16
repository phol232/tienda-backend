<?php

namespace App\Models\Productos_Proveedores;

use Illuminate\Database\Eloquent\Model;

class Proveedores extends Model
{
    protected $table = 'Proveedores';
    protected $primaryKey = 'prov_id';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    public function productos()
    {
        return $this->belongsToMany(Productos::class, 'Producto_Proveedor', 'prov_id', 'prod_id')
            ->withPivot('prod_prov_id', 'precio_proveedor', 'fecha_inicio_suministro', 'fecha_fin_suministro', 'notas', 'estado');
    }
}
