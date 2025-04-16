<?php

namespace App\Models\Clientes;

use Illuminate\Database\Eloquent\Model;

class Categorias_Clientes extends Model
{
    protected $table = 'Categorias_Clientes';
    protected $primaryKey = 'cli_cat_id';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    public function clientesCategorias()
    {
        return $this->hasMany(Cliente_Categoria::class, 'cli_cat_id', 'cli_cat_id');
    }
}
