<?php

namespace App\Models\Productos_Proveedores;

use Illuminate\Database\Eloquent\Model;

class Categorias extends Model
{
    protected $table = 'Categorias';
    protected $primaryKey = 'cat_id';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    public function productos()
    {
        return $this->hasMany(Productos::class, 'cat_id', 'cat_id');
    }
}
