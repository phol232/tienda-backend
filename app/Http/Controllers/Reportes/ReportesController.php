<?php

namespace App\Http\Controllers\Reportes;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportesController extends Controller
{
    public function topProductos()
    {
        $result = DB::select('CALL sp_top_5_productos_mas_vendidos()');
        return response()->json($result);
    }

    public function ventasPorCategoria()
    {
        $result = DB::select('CALL sp_ventas_por_categoria()');
        return response()->json($result);
    }
}
