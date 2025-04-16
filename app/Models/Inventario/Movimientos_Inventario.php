<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Movimientos_Inventario extends Model
{
    protected $table = 'Movimientos_Inventario';
    protected $primaryKey = 'mov_id';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    public function tipoMovimiento()
    {
        return $this->belongsTo(Tipos_Movimiento::class, 'tipmov_id', 'tipmov_id');
    }

    public function producto()
    {
        return $this->belongsTo(Productos::class, 'prod_id', 'pro_id');
    }

    public function usuario()
    {
        return $this->belongsTo(Usuarios::class, 'usr_id', 'usr_id');
    }

    public function proveedor()
    {
        return $this->belongsTo(Proveedores::class, 'prov_id', 'prov_id');
    }
}