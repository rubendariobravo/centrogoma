<?php

namespace App\Http\Controllers;

use App\Models\Presupuesto;
use App\Models\VariableEntorno;
use Barryvdh\DomPDF\Facade\Pdf;

class PresupuestoController extends Controller
{
    public function pdf(Presupuesto $presupuesto)
    {
        $presupuesto->load(['cliente', 'detalles.articulo']);

        $empresa = [
            'nombre'    => VariableEntorno::get('empresa_nombre', config('app.name')),
            'cuit'      => VariableEntorno::get('empresa_cuit', ''),
            'direccion' => VariableEntorno::get('empresa_direccion', ''),
            'telefono'  => VariableEntorno::get('empresa_telefono', ''),
            'email'     => VariableEntorno::get('empresa_email', ''),
        ];

        $pdf = Pdf::loadView('presupuestos.pdf', compact('presupuesto', 'empresa'));
        $pdf->setPaper('A4');

        $nombre = 'Presupuesto-' . str_pad($presupuesto->nro_presupuesto, 6, '0', STR_PAD_LEFT) . '.pdf';

        return $pdf->stream($nombre);
    }
}
