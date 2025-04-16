<?php

namespace App\Models\Productos_Proveedores;

use Illuminate\Database\Eloquent\Model;

class Producto_Proveedor extends Model
{
    protected $table = 'Producto_Proveedor';
    protected $primaryKey = 'prod_prov_id';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    public function producto()
    {
        return $this->belongsTo(Productos::class, 'prod_id', 'pro_id');
    }

    public function proveedor()
    {
        return $this->belongsTo(Proveedores::class, 'prov_id', 'prov_id');
    }
}
