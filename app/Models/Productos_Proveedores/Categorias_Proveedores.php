<?php

namespace App\Models\Productos_Proveedores;

use Illuminate\Database\Eloquent\Model;

class Categorias_Proveedores extends Model
{
    protected $table = 'Categorias_Proveedores';
    protected $primaryKey = 'prov_cat_id';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    public function proveedoresCategorias()
    {
        return $this->hasMany(Proveedor_Categoria::class, 'prov_cat_id', 'prov_cat_id');
    }
}
