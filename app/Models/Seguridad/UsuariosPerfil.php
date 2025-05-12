<?php


namespace App\Models\Seguridad;

use Illuminate\Database\Eloquent\Model;

class UsuariosPerfil extends Model
{
    protected $table        = 'Usuarios_Perfil';
    protected $primaryKey   = 'usrp_id';
    public    $incrementing = false;
    protected $keyType      = 'string';
    public    $timestamps   = false;

    protected $fillable = [
        'usrp_id',
        'usrp_nombre',
        'usrp_apellido',
        'usrp_telefono',
        'usrp_direccion',
        'usrp_genero',
        'usrp_fecha_nacimiento',
        'usrp_imagen',
    ];

    public function usuario()
    {
        return $this->belongsTo(
            Usuarios::class,
            'usrp_id',
            'usr_id'
        );
    }
}
