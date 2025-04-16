<?php

namespace App\Models\Clientes;

use Illuminate\Database\Eloquent\Model;

class Clientes extends Model
{
    protected $table = 'Clientes';
    protected $primaryKey = 'cli_id';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    public function pedidos()
    {
        return $this->hasMany(Pedidos::class, 'cli_id', 'cli_id');
    }

    public function categorias()
    {
        return $this->belongsToMany(Categorias_Clientes::class, 'Cliente_Categoria', 'cli_id', 'cli_cat_id')
            ->withPivot('cli_cat_asoc_id', 'fecha_asociacion');
    }
}
