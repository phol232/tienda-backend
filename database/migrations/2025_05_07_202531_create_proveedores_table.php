<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProveedoresTable extends Migration
{
    public function up(): void
    {
        Schema::create('proveedores', function (Blueprint $table) {
            $table->string('prov_id', 12)->primary();
            $table->string('prov_nombre', 100);
            $table->string('prov_contacto', 100)->nullable();
            $table->string('prov_telefono', 20)->nullable();
            $table->string('prov_email', 100)->nullable();
            $table->text('prov_direccion')->nullable();
            $table->string('prov_rfc', 20)->nullable();
            $table->text('prov_notas')->nullable();
            $table->string('prov_estado', 20)->default('Activo');
            $table->timestamp('prov_fecha_registro')->useCurrent();
            $table->string('prov_sitio_web', 255)->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('proveedores');
    }
}
