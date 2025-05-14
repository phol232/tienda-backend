<?php

namespace App\Models\Inventario;

use Illuminate\Database\Eloquent\Model;

class Tipos_Movimiento extends Model
{
    protected $table = 'Tipos_Movimiento';
    protected $primaryKey = 'tipmov_id';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'tipmov_id',
        'tipmov_nombre',
        'tipmov_descripcion',
        'tipmov_estado',
    ];

    public function movimientosInventario()
    {
        return $this->hasMany(Movimientos_Inventario::class, 'tipmov_id', 'tipmov_id');
    }
}
