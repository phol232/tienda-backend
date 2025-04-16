<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pagos extends Model
{
    protected $table = 'Pagos';
    protected $primaryKey = 'pag_id';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    public function boleta()
    {
        return $this->belongsTo(Boletas::class, 'boleta_id', 'boleta_id');
    }

    public function factura()
    {
        return $this->belongsTo(Facturas::class, 'factura_id', 'factura_id');
    }

    public function metodoPago()
    {
        return $this->belongsTo(Metodos_Pago::class, 'met_id', 'met_id');
    }

    public function usuario()
    {
        return $this->belongsTo(Usuarios::class, 'usr_id', 'usr_id');
    }
}