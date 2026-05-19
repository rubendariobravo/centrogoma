<?php

namespace App\Http\Controllers;

use App\Exports\ListaPreciosExport;
use App\Models\ListaPrecio;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ListasController extends Controller
{
    public function exportarPrecios(int $lista): BinaryFileResponse
    {
        $listaPrecio = ListaPrecio::findOrFail($lista);

        $verNetos    = (bool)   request('verNetos', false);
        $filterRubro = (string) request('rubro', '');
        $search      = (string) request('search', '');

        $filename = 'precios-' . str($listaPrecio->codigo)->slug() . '.xlsx';

        return Excel::download(
            new ListaPreciosExport($lista, $verNetos, $filterRubro, $search),
            $filename,
        );
    }
}
