<?php

namespace App\Imports;

use App\Models\ArticuloPrecio;
use App\Support\Pricing;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;

/**
 * Importa precios desde el Excel generado por ListaPreciosExport.
 *
 * Formato esperado (por posición de columna):
 *   Col A (0): ID_TRAMO  — identifica el registro en articulo_precios
 *   Col G (6): Precio editable (final c/IVA o neto s/IVA según $modo)
 *
 * La fila 0 es el encabezado y se omite.
 * Solo se actualizan tramos que pertenecen a $listaId (seguridad).
 */
class ListaPreciosImport implements ToCollection, SkipsEmptyRows
{
    public int   $actualizados = 0;
    public int   $omitidos     = 0;
    public array $errores      = [];

    public function __construct(
        private int    $listaId,
        private string $modo = 'final', // final | neto
    ) {}

    public function collection(Collection $rows): void
    {
        foreach ($rows as $i => $row) {
            if ($i === 0) {
                continue; // saltar encabezado
            }

            $tramoId  = (int) ($row[0] ?? 0);
            $precioRaw = $row[6] ?? null; // columna G

            if (! $tramoId || $precioRaw === null || $precioRaw === '') {
                $this->omitidos++;
                continue;
            }

            $tramo = ArticuloPrecio::with('articulo')
                ->where('id', $tramoId)
                ->where('lista_precio_id', $this->listaId)
                ->first();

            if (! $tramo) {
                $this->errores[] = "Fila " . ($i + 1) . ": ID {$tramoId} no pertenece a esta lista.";
                $this->omitidos++;
                continue;
            }

            $precio = (float) str_replace(',', '.', (string) $precioRaw);

            if ($precio <= 0) {
                $this->errores[] = "Fila " . ($i + 1) . ": precio inválido ({$precioRaw}).";
                $this->omitidos++;
                continue;
            }

            $iva = (float) ($tramo->articulo->alicuota_iva ?? 21);
            $precioNeto = $this->modo === 'neto'
                ? round($precio, 4)
                : Pricing::neto($precio, $iva);

            $tramo->update(['precio' => $precioNeto]);
            $this->actualizados++;
        }
    }
}
