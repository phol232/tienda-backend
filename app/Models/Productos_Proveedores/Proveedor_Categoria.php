<?php

namespace App\Models\Productos_Proveedores;

use Illuminate\Database\Eloquent\Model;

class Proveedor_Categoria extends Model
{
    protected $table = 'Proveedor_Categoria';
    protected $primaryKey = 'prov_cat_map_id';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    public function proveedor()
    {
        return $this->belongsTo(Proveedores::class, 'prov_id', 'prov_id');
    }

    public function categoriaProveedor()
    {
        return $this->belongsTo(Categorias_Proveedores::class, 'prov_cat_id', 'prov_cat_id');
    }
}
