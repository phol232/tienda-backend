<?php

namespace App\Models\Inventario;

use Illuminate\Database\Eloquent\Model;
use App\Models\Inventario\Movimientos_Inventario;

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

    /**
     * Relación con Movimientos_Inventario
     */
    public function movimientosInventario()
    {
        return $this->hasMany(
            Movimientos_Inventario::class, // modelo relacionado
            'tipmov_id',                   // FK en Movimientos_Inventario
            'tipmov_id'                    // PK aquí
        );
    }
}
