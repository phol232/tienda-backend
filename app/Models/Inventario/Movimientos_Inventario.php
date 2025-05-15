<?php


namespace App\Models\Inventario;

use Illuminate\Database\Eloquent\Model;
use App\Models\Inventario\Tipos_Movimiento;
use App\Models\Productos_Proveedores\Productos;
use App\Models\Seguridad\Usuarios;
use App\Models\Productos_Proveedores\Proveedores;

class Movimientos_Inventario extends Model
{
    protected $table = 'Movimientos_Inventario';
    protected $primaryKey = 'mov_id';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'mov_id',
        'tipmov_id',
        'mov_fecha',
        'mov_referencia',
        'mov_notas',
        'usr_id',
        'prov_id',
    ];

    /** ← Esto hace que Eloquent cargue automáticamente estas relaciones al serializar */
    protected $with = [
        'tipoMovimiento',
        'usuario',
        'proveedor',
        'productos',
    ];

    /**
     * Relación al tipo de movimiento
     */
    public function tipoMovimiento()
    {
        return $this->belongsTo(Tipos_Movimiento::class, 'tipmov_id', 'tipmov_id');
    }

    /**
     * Relación al usuario que hizo el movimiento
     */
    public function usuario()
    {
        return $this->belongsTo(Usuarios::class, 'usr_id', 'usr_id');
    }

    /**
     * Relación al proveedor asociado
     */
    public function proveedor()
    {
        return $this->belongsTo(Proveedores::class, 'prov_id', 'prov_id');
    }

    /**
     * Relación many-to-many con productos y datos pivot
     */
    public function productos()
    {
        return $this->belongsToMany(
            Productos::class,
            'Movimiento_Producto', // tu tabla pivote
            'mov_id',               // FK local
            'prod_id'               // FK remoto
        )->withPivot('movprod_cantidad', 'movprod_costo_unitario');
    }
}
