<?php

// app/Models/Inventario/Movimientos_Inventario.php
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

    public function tipoMovimiento()
    {
        return $this->belongsTo(Tipos_Movimiento::class, 'tipmov_id', 'tipmov_id');
    }

    public function usuario()
    {
        return $this->belongsTo(Usuarios::class, 'usr_id', 'usr_id');
    }

    public function proveedor()
    {
        return $this->belongsTo(Proveedores::class, 'prov_id', 'prov_id');
    }

    public function productos()
    {
        return $this->belongsToMany(
            Productos::class,
            'Movimiento_Producto',
            'mov_id',
            'prod_id'
        )
            ->withPivot('movprod_cantidad', 'movprod_costo_unitario');
    }
}
