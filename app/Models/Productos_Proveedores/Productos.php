<?php

namespace App\Models\Productos_Proveedores;

use Illuminate\Database\Eloquent\Model;

class Productos extends Model
{
    protected $table = 'Productos';
    protected $primaryKey = 'pro_id';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    public function categoria()
    {
        return $this->belongsTo(Categorias::class, 'cat_id', 'cat_id');
    }

    public function detalles()
    {
        return $this->hasOne(Producto_Detalles::class, 'prod_id', 'pro_id');
    }

    public function proveedores()
    {
        return $this->belongsToMany(Proveedores::class, 'Producto_Proveedor', 'prod_id', 'prov_id')
            ->withPivot('prod_prov_id', 'precio_proveedor', 'fecha_inicio_suministro', 'fecha_fin_suministro', 'notas', 'estado');
    }
}
