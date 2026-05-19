<?php

namespace App\Livewire\Ayuda;

use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Centro de Ayuda')]
class Index extends Component
{
    public string $search    = '';
    public string $tabActiva = 'modulos';

    public function getResultadosProperty(): array
    {
        if (strlen($this->search) < 2) {
            return [];
        }

        $term    = mb_strtolower($this->search);
        $results = [];

        foreach (config('help.pages', []) as $key => $page) {
            $score = 0;

            if (str_contains(mb_strtolower($page['titulo'] ?? ''), $term)) {
                $score += 10;
            }
            if (str_contains(mb_strtolower($page['descripcion'] ?? ''), $term)) {
                $score += 5;
            }

            foreach ($page['tips'] ?? [] as $tip) {
                if (str_contains(mb_strtolower($tip), $term)) {
                    $score += 3;
                }
            }
            foreach ($page['faq'] ?? [] as $item) {
                if (str_contains(mb_strtolower($item['q']), $term) ||
                    str_contains(mb_strtolower($item['a']), $term)) {
                    $score += 4;
                }
            }

            if ($score > 0) {
                $results[] = [
                    'key'    => $key,
                    'titulo' => $page['titulo'],
                    'desc'   => $page['descripcion'] ?? '',
                    'score'  => $score,
                ];
            }
        }

        foreach (config('help.guides', []) as $guide) {
            $score = 0;
            if (str_contains(mb_strtolower($guide['titulo']), $term)) {
                $score += 10;
            }
            foreach ($guide['pasos'] ?? [] as $paso) {
                if (str_contains(mb_strtolower($paso), $term)) {
                    $score += 2;
                }
            }
            if ($score > 0) {
                $results[] = [
                    'key'    => 'guide',
                    'titulo' => $guide['titulo'],
                    'desc'   => 'Guía paso a paso',
                    'score'  => $score,
                ];
            }
        }

        usort($results, fn($a, $b) => $b['score'] - $a['score']);

        return $results;
    }

    public function render()
    {
        $moduloGroups = [
            'Ventas'          => ['ventas', 'ventas.create', 'clientes', 'recibos'],
            'Compras'         => ['compras', 'compras.create', 'proveedores', 'ordenes-pago'],
            'Artículos'       => ['articulos', 'articulos.create', 'rubros', 'marcas'],
            'Caja y Finanzas' => ['cajas', 'cc-clientes', 'cc-proveedores'],
            'Producción'      => ['produccion', 'remitos', 'pesadas', 'descartes', 'empleados'],
            'Administración'  => ['usuarios', 'roles', 'config', 'auditorias'],
        ];

        return view('livewire.ayuda.index', [
            'moduloGroups' => $moduloGroups,
            'pages'        => config('help.pages', []),
            'guides'       => config('help.guides', []),
            'resultados'   => $this->resultados,
        ]);
    }
}
