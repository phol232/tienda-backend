<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tipos_Movimiento extends Model
{
    protected $table = 'Tipos_Movimiento';
    protected $primaryKey = 'tipmov_id';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    public function movimientosInventario()
    {
        return $this->hasMany(Movimientos_Inventario::class, 'tipmov_id', 'tipmov_id');
    }
}