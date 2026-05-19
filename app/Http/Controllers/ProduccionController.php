<?php

namespace App\Http\Controllers;

use App\Models\Produccion;
use Barryvdh\DomPDF\Facade\Pdf;

class ProduccionController extends Controller
{
    public function pdf(Produccion $produccion)
    {
        $produccion->load([
            'insumos.articulo',
            'productos.articulo',
            'empleado',
            'user',
        ]);

        $pdf = Pdf::loadView('produccion.pdf', compact('produccion'));
        $pdf->setPaper('A4');

        $nombre = "produccion-{$produccion->nro_produccion}.pdf";
        return $pdf->stream($nombre);
    }
}
