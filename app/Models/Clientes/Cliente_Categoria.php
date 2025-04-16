<?php

namespace App\Models\Clientes;

use Illuminate\Database\Eloquent\Model;

class Cliente_Categoria extends Model
{
    protected $table = 'Cliente_Categoria';
    protected $primaryKey = 'cli_cat_asoc_id';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    public function cliente()
    {
        return $this->belongsTo(Clientes::class, 'cli_id', 'cli_id');
    }

    public function categoriaCliente()
    {
        return $this->belongsTo(Categorias_Clientes::class, 'cli_cat_id', 'cli_cat_id');
    }
}
