<?php
namespace App\Models\Productos_Proveedores;

use Illuminate\Database\Eloquent\Relations\Pivot;

class Proveedor_Categoria extends Pivot
{
    protected $table = 'Proveedor_Categoria';
    protected $primaryKey = 'prov_cat_map_id';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'prov_cat_map_id',
        'prov_id',
        'prov_cat_id',
        'fecha_asociacion',
    ];

    public function proveedor()
    {
        return $this->belongsTo(Proveedores::class, 'prov_id', 'prov_id');
    }

    public function categoriaProveedor()
    {
        return $this->belongsTo(Categorias_Proveedores::class, 'prov_cat_id', 'prov_cat_id');
    }
}
