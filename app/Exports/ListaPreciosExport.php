<?php

namespace App\Exports;

use App\Models\Articulo;
use App\Support\Pricing;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ListaPreciosExport implements FromCollection, WithHeadings, WithStyles, WithColumnWidths
{
    public function __construct(
        private int    $listaId,
        private bool   $verNetos    = false,
        private string $filterRubro = '',
        private string $search      = '',
    ) {}

    public function headings(): array
    {
        $labelEditable = $this->verNetos
            ? 'Precio Neto s/IVA [EDITAR]'
            : 'Precio Final c/IVA [EDITAR]';

        $labelRef = $this->verNetos
            ? 'Final c/IVA (referencia)'
            : 'Neto s/IVA (referencia)';

        return [
            'ID_TRAMO',
            'Código',
            'Descripción',
            'Rubro',
            'Cant. Desde',
            'Cant. Hasta',
            $labelEditable,
            'IVA %',
            $labelRef,
        ];
    }

    public function collection(): Collection
    {
        $articulos = Articulo::query()
            ->where('estado', 'A')
            ->with(['rubro', 'precios' => fn ($q) => $q
                ->where('lista_precio_id', $this->listaId)
                ->orderBy('cantidad_desde')])
            ->when($this->filterRubro, fn ($q) => $q->where('rubro_id', $this->filterRubro))
            ->when($this->search, fn ($q) => $q->where(fn ($q) =>
                $q->where('codigo', 'like', "%{$this->search}%")
                  ->orWhere('descripcion', 'like', "%{$this->search}%")))
            ->orderBy('descripcion')
            ->get();

        $rows = collect();

        foreach ($articulos as $art) {
            $iva = (float) $art->alicuota_iva;
            foreach ($art->precios as $tramo) {
                $neto  = (float) $tramo->precio;
                $final = Pricing::final($neto, $iva);
                $rows->push([
                    $tramo->id,
                    $art->codigo,
                    $art->descripcion,
                    $art->rubro?->descripcion ?? '',
                    (float) $tramo->cantidad_desde,
                    $tramo->cantidad_hasta !== null ? (float) $tramo->cantidad_hasta : '',
                    $this->verNetos ? $neto : $final,
                    $iva,
                    $this->verNetos ? $final : $neto,
                ]);
            }
        }

        return $rows;
    }

    public function columnWidths(): array
    {
        return [
            'A' => 12, 'B' => 14, 'C' => 38, 'D' => 20,
            'E' => 12, 'F' => 12, 'G' => 26, 'H' => 7, 'I' => 22,
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        // Encabezado en negrita
        $sheet->getStyle('1')->getFont()->setBold(true);
        // Columna ID: gris claro (no editar)
        $sheet->getStyle('A')->getFont()->getColor()->setRGB('AAAAAA');
        // Columna precio editable: fondo amarillo
        $lastRow = $sheet->getHighestRow();
        if ($lastRow > 1) {
            $sheet->getStyle("G2:G{$lastRow}")
                ->getFill()
                ->setFillType('solid')
                ->getStartColor()->setRGB('FFF3CD');
        }
        // Columnas no editables (B-F, H-I): fondo gris muy suave
        if ($lastRow > 1) {
            foreach (['B', 'C', 'D', 'E', 'F', 'H', 'I'] as $col) {
                $sheet->getStyle("{$col}2:{$col}{$lastRow}")
                    ->getFill()
                    ->setFillType('solid')
                    ->getStartColor()->setRGB('F8F9FA');
            }
        }
        return [];
    }
}
