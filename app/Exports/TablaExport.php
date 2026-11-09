<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Hoja simple a partir de filas ya armadas (encabezados incluidos). Los números quedan como números
 * con formato de moneda; las filas indicadas (1 = primera) van en negrita.
 */
class TablaExport implements FromArray, WithStyles, WithTitle, ShouldAutoSize
{
    /** @param  int[]  $negritas */
    public function __construct(private array $filas, private string $titulo = 'Hoja1', private array $negritas = [1]) {}

    public function array(): array
    {
        return $this->filas;
    }

    public function title(): string
    {
        return mb_substr($this->titulo, 0, 31);
    }

    public function styles(Worksheet $sheet): array
    {
        foreach ($this->filas as $i => $fila) {
            foreach (array_values($fila) as $j => $valor) {
                if (is_float($valor)) {
                    $sheet->getStyle([$j + 1, $i + 1])->getNumberFormat()->setFormatCode('#,##0.00');
                }
            }
        }

        return collect($this->negritas)->mapWithKeys(fn ($n) => [$n => ['font' => ['bold' => true]]])->all();
    }
}
