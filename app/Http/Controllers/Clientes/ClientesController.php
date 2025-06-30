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

    public function index()
    {
        $clientes = Clientes::with('categorias')->get();
        return response()->json($clientes);
    }

    public function search(Request $request)
    {
        $query = $request->get('q', '');
        $limit = $request->get('limit', 10);
        if (empty(trim($query))) {
            return response()->json([]);
        }

        $clientes = Clientes::with('categorias')
            ->where(function ($q) use ($query) {
                $q->where('cli_nombre', 'LIKE', "%{$query}%")
                    ->orWhere('cli_apellido', 'LIKE', "%{$query}%")
                    ->orWhere(DB::raw("CONCAT(cli_nombre, ' ', cli_apellido)"), 'LIKE', "%{$query}%");
            })
            ->limit($limit)
            ->orderBy('cli_nombre', 'asc')
            ->orderBy('cli_apellido', 'asc')
            ->get();

        return response()->json($clientes);
    }

    public function show($id)
    {
        $cliente = Clientes::with('categorias')->findOrFail($id);
        return response()->json($cliente);
    }

    public function store(Request $request)
    {
        $request->validate([
            'cli_nombre'       => 'required|string|max:50',
            'cli_apellido'     => 'required|string|max:50',
            'cli_email'        => 'required|email|unique:Clientes,cli_email',
            'cli_telefono'     => 'nullable|string|max:20',
            'cli_cat_id'       => 'required|exists:Categorias_Clientes,cli_cat_id',
        ]);

        $lastCliente = Clientes::orderBy('cli_id', 'desc')->first();
        if ($lastCliente && preg_match('/CLI-(\d+)/', $lastCliente->cli_id, $m)) {
            $lastNumber = intval($m[1]);
            $newNumber  = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }
        $newCliId = 'CLI-' . str_pad($newNumber, 3, '0', STR_PAD_LEFT);

        $cliente = new Clientes();
        $cliente->cli_id               = $newCliId;
        $cliente->cli_nombre           = $request->cli_nombre;
        $cliente->cli_apellido         = $request->cli_apellido;
        $cliente->cli_email            = $request->cli_email;
        $cliente->cli_telefono         = $request->cli_telefono;
        $cliente->save();

        $lastAsoc = Cliente_Categoria::orderBy('cli_cat_asoc_id', 'desc')->first();
        if ($lastAsoc && preg_match('/CLICASOC-(\d+)/', $lastAsoc->cli_cat_asoc_id, $m2)) {
            $lastNumAsoc = intval($m2[1]);
            $newNumAsoc  = $lastNumAsoc + 1;
        } else {
            $newNumAsoc = 1;
        }
        $newAsocId = 'CLICASOC-' . str_pad($newNumAsoc, 3, '0', STR_PAD_LEFT);

        $pivote = new Cliente_Categoria();
        $pivote->cli_cat_asoc_id   = $newAsocId;
        $pivote->cli_id            = $newCliId;
        $pivote->cli_cat_id        = $request->cli_cat_id;
        $pivote->fecha_asociacion  = Carbon::now();
        $pivote->save();

        $clienteConCategorias = Clientes::with('categorias')->find($newCliId);
        return response()->json($clienteConCategorias, 201);
    }

    public function update(Request $request, $id)
    {
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

        $cliente = Clientes::findOrFail($id);
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

        $pivoteExistente = Cliente_Categoria::where('cli_id', $id)->first();
        if ($pivoteExistente) {
            if ($pivoteExistente->cli_cat_id !== $request->cli_cat_id) {
                $pivoteExistente->cli_cat_id = $request->cli_cat_id;
                $pivoteExistente->fecha_asociacion = Carbon::now();
                $pivoteExistente->save();
            }
        } else {
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

        $clienteConCategorias = Clientes::with('categorias')->find($id);
        return response()->json($clienteConCategorias);
    }

    public function destroy($id)
    {
        $cliente = Clientes::findOrFail($id);

        Cliente_Categoria::where('cli_id', $id)->delete();

        $cliente->delete();

        return response()->json([
            'message' => 'Cliente eliminado correctamente'
        ]);
    }
}
