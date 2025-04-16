<?php

namespace App\Models\Pedidos;

use Illuminate\Database\Eloquent\Model;

class Pedidos extends Model
{
    protected $table = 'Pedidos';
    protected $primaryKey = 'ped_id';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    public function detalles()
    {
        return $this->hasMany(Pedidos_Detalles::class, 'ped_id', 'ped_id');
    }

    public function cliente()
    {
        return $this->belongsTo(Clientes::class, 'cli_id', 'cli_id');
    }

    public function usuario()
    {
        return $this->belongsTo(Usuarios::class, 'usr_id', 'usr_id');
    }
}
