<?php

namespace App\Models\Productos_Proveedores;

use Illuminate\Database\Eloquent\Model;

class Producto_Detalles extends Model
{
    protected $table = 'Producto_Detalles';
    protected $primaryKey = 'prod_id';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    public function producto()
    {
        return $this->belongsTo(Productos::class, 'prod_id', 'pro_id');
    }

    public function usuarioCreador()
    {
        return $this->belongsTo(Usuarios::class, 'prod_creado_por', 'usr_id');
    }

    public function usuarioActualizador()
    {
        return $this->belongsTo(Usuarios::class, 'prod_actualizado_por', 'usr_id');
    }
}
