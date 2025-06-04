<?php

namespace App\Http\Controllers\Clientes;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

use App\Models\Clientes\Clientes;
use App\Models\Clientes\Cliente_Categoria;
use App\Models\Clientes\Categorias_Clientes;

class ClientesController extends Controller
{
    /**
     * Mostrar todos los clientes con su(s) categoría(s) asociada(s).
     */
    public function index()
    {
        $clientes = Clientes::with('categorias')->get();
        return response()->json($clientes);
    }

    public function show($id)
    {
        $cliente = Clientes::with('categorias')->findOrFail($id);
        return response()->json($cliente);
    }

    public function store(Request $request)
    {
        // Validación de campos del cliente y de la categoría
        $request->validate([
            'cli_nombre'       => 'required|string|max:50',
            'cli_apellido'     => 'required|string|max:50',
            'cli_email'        => 'required|email|unique:Clientes,cli_email',
            'cli_telefono'     => 'nullable|string|max:20',
            'cli_cat_id'       => 'required|exists:Categorias_Clientes,cli_cat_id',
        ]);

        // Generar un nuevo cli_id con formato CLI-###
        $lastCliente = Clientes::orderBy('cli_id', 'desc')->first();
        if ($lastCliente && preg_match('/CLI-(\d+)/', $lastCliente->cli_id, $m)) {
            $lastNumber = intval($m[1]);
            $newNumber  = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }
        $newCliId = 'CLI-' . str_pad($newNumber, 3, '0', STR_PAD_LEFT);

        // Crear el registro del cliente
        $cliente = new Clientes();
        $cliente->cli_id               = $newCliId;
        $cliente->cli_nombre           = $request->cli_nombre;
        $cliente->cli_apellido         = $request->cli_apellido;
        $cliente->cli_email            = $request->cli_email;
        $cliente->cli_telefono         = $request->cli_telefono;
        $cliente->save();

        // Generar un nuevo cli_cat_asoc_id para la tabla pivote Cliente_Categoria
        $lastAsoc = Cliente_Categoria::orderBy('cli_cat_asoc_id', 'desc')->first();
        if ($lastAsoc && preg_match('/CLICASOC-(\d+)/', $lastAsoc->cli_cat_asoc_id, $m2)) {
            $lastNumAsoc = intval($m2[1]);
            $newNumAsoc  = $lastNumAsoc + 1;
        } else {
            $newNumAsoc = 1;
        }
        $newAsocId = 'CLICASOC-' . str_pad($newNumAsoc, 3, '0', STR_PAD_LEFT);

        // Insertar manualmente en la tabla pivote (Cliente_Categoria)
        $pivote = new Cliente_Categoria();
        $pivote->cli_cat_asoc_id   = $newAsocId;
        $pivote->cli_id            = $newCliId;
        $pivote->cli_cat_id        = $request->cli_cat_id;
        $pivote->fecha_asociacion  = Carbon::now();
        $pivote->save();

        // Devolver el cliente recién creado (con la categoría ya asociada)
        $clienteConCategorias = Clientes::with('categorias')->find($newCliId);
        return response()->json($clienteConCategorias, 201);
    }

    /**
     * Actualizar un cliente existente y, de ser necesario, su asociación de categoría.
     */
    public function update(Request $request, $id)
    {
        // Validación, ignorando el email actual del cliente
        $request->validate([
            'cli_nombre'       => 'required|string|max:50',
            'cli_apellido'     => 'required|string|max:50',
            'cli_email'        => 'required|email|unique:Clientes,cli_email,' . $id . ',cli_id',
            'cli_telefono'     => 'nullable|string|max:20',
            'cli_direccion'    => 'nullable|string',
            'cli_genero'       => 'nullable|string|max:10',
            'cli_fecha_nacimiento' => 'nullable|date',
            'cli_tipo'         => 'nullable|string|max:20',
            'cli_estado'       => 'nullable|string|max:20',
            'cli_rfc'          => 'nullable|string|max:20',
            'cli_notas'        => 'nullable|string',
            'cli_cat_id'       => 'required|exists:Categorias_Clientes,cli_cat_id',
        ]);

        // Buscar el cliente
        $cliente = Clientes::findOrFail($id);
        // Actualizar campos
        $cliente->cli_nombre           = $request->cli_nombre;
        $cliente->cli_apellido         = $request->cli_apellido;
        $cliente->cli_email            = $request->cli_email;
        $cliente->cli_telefono         = $request->cli_telefono;
        $cliente->cli_direccion        = $request->cli_direccion;
        $cliente->cli_genero           = $request->cli_genero;
        $cliente->cli_fecha_nacimiento = $request->cli_fecha_nacimiento;
        $cliente->cli_tipo             = $request->cli_tipo      ?? $cliente->cli_tipo;
        $cliente->cli_estado           = $request->cli_estado    ?? $cliente->cli_estado;
        $cliente->cli_rfc              = $request->cli_rfc;
        $cliente->cli_notas            = $request->cli_notas;
        $cliente->save();

        // Actualizar la asociación de categoría (solo hay una por cliente según tu modelo)
        // 1) Buscar registro en la tabla pivote
        $pivoteExistente = Cliente_Categoria::where('cli_id', $id)->first();
        if ($pivoteExistente) {
            // Si cambió la categoría, actualizar cli_cat_id y fecha_asociacion
            if ($pivoteExistente->cli_cat_id !== $request->cli_cat_id) {
                $pivoteExistente->cli_cat_id = $request->cli_cat_id;
                $pivoteExistente->fecha_asociacion = Carbon::now();
                $pivoteExistente->save();
            }
        } else {
            // Si no existía asociación, crear una nueva
            $lastAsoc = Cliente_Categoria::orderBy('cli_cat_asoc_id', 'desc')->first();
            if ($lastAsoc && preg_match('/CLICASOC-(\d+)/', $lastAsoc->cli_cat_asoc_id, $m2)) {
                $lastNumAsoc = intval($m2[1]);
                $newNumAsoc  = $lastNumAsoc + 1;
            } else {
                $newNumAsoc = 1;
            }
            $newAsocId = 'CLICASOC-' . str_pad($newNumAsoc, 3, '0', STR_PAD_LEFT);

            $nuevoPivote = new Cliente_Categoria();
            $nuevoPivote->cli_cat_asoc_id  = $newAsocId;
            $nuevoPivote->cli_id           = $id;
            $nuevoPivote->cli_cat_id       = $request->cli_cat_id;
            $nuevoPivote->fecha_asociacion = Carbon::now();
            $nuevoPivote->save();
        }

        // Devolver el cliente actualizado (con la categoría actualizada)
        $clienteConCategorias = Clientes::with('categorias')->find($id);
        return response()->json($clienteConCategorias);
    }

    public function destroy($id)
    {
        $cliente = Clientes::findOrFail($id);

        // Eliminar la(s) fila(s) de la tabla pivote asociadas a este cliente
        Cliente_Categoria::where('cli_id', $id)->delete();

        // Eliminar el cliente
        $cliente->delete();

        return response()->json([
            'message' => 'Cliente eliminado correctamente'
        ]);
    }
}
