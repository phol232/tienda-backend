<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Metodos_Pago extends Model
{
    protected $table = 'Metodos_Pago';
    protected $primaryKey = 'met_id';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    public function pagos()
    {
        return $this->hasMany(Pagos::class, 'met_id', 'met_id');
    }
}