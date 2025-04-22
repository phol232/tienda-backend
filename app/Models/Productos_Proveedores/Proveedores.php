<?php
namespace App\Models\Productos_Proveedores;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Models\Productos_Proveedores\Productos;
use App\Models\Productos_Proveedores\Categorias_Proveedores;
use App\Models\Productos_Proveedores\Proveedor_Categoria as ProvCatPivot;

class Proveedores extends Model
{
    protected $table = 'Proveedores';
    protected $primaryKey = 'prov_id';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'prov_id',
        'prov_nombre',
        // …otros campos que tú uses
    ];

    public function productos(): BelongsToMany
    {
        return $this->belongsToMany(
            Productos::class,
            'Producto_Proveedor',
            'prov_id',
            'prod_id'
        )
            ->using(Producto_Proveedor::class)
            ->withPivot(
                'prod_prov_id',
                'precio_proveedor',
                'fecha_inicio_suministro',
                'fecha_fin_suministro',
                'notas',
                'estado'
            );
    }

    public function categorias(): BelongsToMany
    {
        return $this->belongsToMany(
            Categorias_Proveedores::class,
            'Proveedor_Categoria',
            'prov_id',
            'prov_cat_id'
        )
            ->using(ProvCatPivot::class)
            ->withPivot('prov_cat_map_id', 'fecha_asociacion');
    }
}
