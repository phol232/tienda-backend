<?php

namespace App\Models\ventas_Pagos;

use Illuminate\Database\Eloquent\Model;
use App\Models\ventas_Pagos\Pagos;

class MetodosPago extends Model
{
    protected $table = 'Metodos_Pago'; 

    protected $primaryKey = 'met_id';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'met_id',
        'met_nombre',
        'met_descripcion',
        'met_estado',
        'met_tipo',
        'met_banco',
    ];

    public function pagos()
    {
        return $this->hasMany(Pagos::class, 'met_id', 'met_id');
    }
}
