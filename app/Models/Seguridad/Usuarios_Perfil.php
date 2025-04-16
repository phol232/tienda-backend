<?php

namespace App\Models\Seguridad;

use Illuminate\Database\Eloquent\Model;

class Usuarios_Perfil extends Model
{
    protected $table = 'Usuarios_Perfil';
    protected $primaryKey = 'usrp_id';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    public function usuario()
    {
        return $this->belongsTo(Usuarios::class, 'usrp_id', 'usr_id');
    }
}
