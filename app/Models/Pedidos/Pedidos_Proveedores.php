<?php

namespace App\Models\Pedidos;

use Illuminate\Database\Eloquent\Model;

class Pedidos_Proveedores extends Model
{
    protected $table = 'Pedidos_Proveedores';
    protected $primaryKey = 'pedprov_id';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    public function detalles()
    {
        return $this->hasMany(Pedidos_Proveedores_Detalles::class, 'pedprov_id', 'pedprov_id');
    }

    public function proveedor()
    {
        return $this->belongsTo(Proveedores::class, 'prov_id', 'prov_id');
    }

    public function usuario()
    {
        return $this->belongsTo(Usuarios::class, 'usr_id', 'usr_id');
    }
}
