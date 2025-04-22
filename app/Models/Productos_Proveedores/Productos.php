<?php
namespace App\Models\Productos_Proveedores;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Models\Productos_Proveedores\Proveedores;
use App\Models\Productos_Proveedores\Producto_Detalles;
use App\Models\Productos_Proveedores\Categorias;
use App\Models\Productos_Proveedores\Producto_Proveedor as Pivot;

class Productos extends Model
{
    protected $table = 'Productos';
    protected $primaryKey = 'pro_id';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'pro_id',
        'pro_nombre',
        'pro_precio_venta',
        'pro_stock',
        'pro_estado',
        'cat_id',
    ];

    public function categoria()
    {
        return $this->belongsTo(Categorias::class, 'cat_id', 'cat_id');
    }

    public function detalles()
    {
        return $this->hasOne(Producto_Detalles::class, 'prod_id', 'pro_id');
    }

    public function proveedores(): BelongsToMany
    {
        return $this->belongsToMany(
            Proveedores::class,
            'Producto_Proveedor', // tabla pivote
            'prod_id',            // FK de Productos en pivote
            'prov_id'             // FK de Proveedores en pivote
        )
            ->using(Pivot::class)     // Usa tu modelo de pivote
            ->withPivot(
                'prod_prov_id',
                'precio_proveedor',
                'fecha_inicio_suministro',
                'fecha_fin_suministro',
                'notas',
                'estado'
            );
    }
}
