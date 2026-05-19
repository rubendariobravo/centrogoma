<?php

namespace Database\Seeders;

use App\Models\Articulo;
use App\Models\CcProveedor;
use App\Models\Compra;
use App\Models\DetCompra;
use App\Models\MovimientoArticulo;
use App\Models\Proveedor;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class ComprasSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::first();
        if (! $user) {
            $this->command->warn('No hay usuarios. Ejecutar FakeDataSeeder primero.');
            return;
        }

        $proveedores = Proveedor::where('estado', 'A')->take(3)->get();
        if ($proveedores->isEmpty()) {
            $this->command->warn('No hay proveedores activos. Ejecutar FakeDataSeeder primero.');
            return;
        }

        $articulos = Articulo::where('estado', 'A')->take(6)->get();
        if ($articulos->isEmpty()) {
            $this->command->warn('No hay artículos activos. Ejecutar FakeDataSeeder primero.');
            return;
        }

        $comprasData = [
            // Proveedor 0 — 3 facturas pendientes + 1 cerrada
            [
                'proveedor_idx' => 0,
                'fecha'         => now()->subDays(45),
                'nro_factura'   => '00001-00000101',
                'letra'         => 'A',
                'estado'        => 'C',   // ya pagada
                'items'         => [
                    ['art_idx' => 0, 'cantidad' => 100, 'precio' => 850.00,  'iva' => 10.5],
                    ['art_idx' => 1, 'cantidad' => 50,  'precio' => 1200.00, 'iva' => 10.5],
                ],
            ],
            [
                'proveedor_idx' => 0,
                'fecha'         => now()->subDays(30),
                'nro_factura'   => '00001-00000115',
                'letra'         => 'A',
                'estado'        => 'P',
                'items'         => [
                    ['art_idx' => 0, 'cantidad' => 200, 'precio' => 870.00,  'iva' => 10.5],
                    ['art_idx' => 2, 'cantidad' => 30,  'precio' => 450.00,  'iva' => 21],
                ],
            ],
            [
                'proveedor_idx' => 0,
                'fecha'         => now()->subDays(15),
                'nro_factura'   => '00001-00000128',
                'letra'         => 'A',
                'estado'        => 'P',
                'items'         => [
                    ['art_idx' => 1, 'cantidad' => 80,  'precio' => 1250.00, 'iva' => 10.5],
                    ['art_idx' => 3, 'cantidad' => 60,  'precio' => 320.00,  'iva' => 10.5],
                ],
            ],
            [
                'proveedor_idx' => 0,
                'fecha'         => now()->subDays(5),
                'nro_factura'   => '00001-00000140',
                'letra'         => 'A',
                'estado'        => 'P',
                'items'         => [
                    ['art_idx' => 0, 'cantidad' => 150, 'precio' => 880.00,  'iva' => 10.5],
                ],
            ],

            // Proveedor 1 — 2 facturas pendientes
            [
                'proveedor_idx' => 1,
                'fecha'         => now()->subDays(20),
                'nro_factura'   => '00002-00000056',
                'letra'         => 'B',
                'estado'        => 'P',
                'items'         => [
                    ['art_idx' => 2, 'cantidad' => 100, 'precio' => 480.00,  'iva' => 21],
                    ['art_idx' => 4, 'cantidad' => 200, 'precio' => 95.00,   'iva' => 21],
                ],
            ],
            [
                'proveedor_idx' => 1,
                'fecha'         => now()->subDays(8),
                'nro_factura'   => '00002-00000071',
                'letra'         => 'B',
                'estado'        => 'P',
                'items'         => [
                    ['art_idx' => 3, 'cantidad' => 500, 'precio' => 330.00,  'iva' => 10.5],
                ],
            ],

            // Proveedor 2 — 3 facturas pendientes (monto parcial en una)
            [
                'proveedor_idx' => 2,
                'fecha'         => now()->subDays(60),
                'nro_factura'   => '00003-00000009',
                'letra'         => 'B',
                'estado'        => 'P',
                'saldo_override' => 35000.00,  // pago parcial previo
                'items'         => [
                    ['art_idx' => 5, 'cantidad' => 50,  'precio' => 1800.00, 'iva' => 21],
                    ['art_idx' => 0, 'cantidad' => 100, 'precio' => 840.00,  'iva' => 10.5],
                ],
            ],
            [
                'proveedor_idx' => 2,
                'fecha'         => now()->subDays(25),
                'nro_factura'   => '00003-00000018',
                'letra'         => 'B',
                'estado'        => 'P',
                'items'         => [
                    ['art_idx' => 4, 'cantidad' => 300, 'precio' => 98.00,   'iva' => 21],
                    ['art_idx' => 1, 'cantidad' => 40,  'precio' => 1230.00, 'iva' => 10.5],
                ],
            ],
            [
                'proveedor_idx' => 2,
                'fecha'         => now()->subDays(3),
                'nro_factura'   => '00003-00000027',
                'letra'         => 'B',
                'estado'        => 'P',
                'items'         => [
                    ['art_idx' => 2, 'cantidad' => 80,  'precio' => 490.00,  'iva' => 21],
                ],
            ],
        ];

        foreach ($comprasData as $data) {
            $pIdx      = $data['proveedor_idx'];
            $proveedor = $proveedores->get($pIdx);
            if (! $proveedor) {
                continue;
            }

            // Calcular totales
            $neto21    = 0;
            $neto105   = 0;
            $iva21     = 0;
            $iva105    = 0;

            foreach ($data['items'] as $item) {
                $art = $articulos->get($item['art_idx']);
                if (! $art) {
                    continue;
                }
                $subtotal = round($item['cantidad'] * $item['precio'], 2);
                $ivaItem  = round($subtotal * ($item['iva'] / 100), 2);

                if ($item['iva'] == 21) {
                    $neto21  += $subtotal;
                    $iva21   += $ivaItem;
                } else {
                    $neto105 += $subtotal;
                    $iva105  += $ivaItem;
                }
            }

            $neto21  = round($neto21, 2);
            $neto105 = round($neto105, 2);
            $iva21   = round($iva21, 2);
            $iva105  = round($iva105, 2);
            $neto    = round($neto21 + $neto105, 2);
            $total   = round($neto + $iva21 + $iva105, 2);
            $saldo   = $data['saldo_override'] ?? ($data['estado'] === 'P' ? $total : 0);

            $compra = Compra::create([
                'proveedor_id'  => $proveedor->id,
                'user_id'       => $user->id,
                'caja_id'       => null,
                'fecha'         => $data['fecha']->toDateString(),
                'nro_factura'   => $data['nro_factura'],
                'letra'         => $data['letra'],
                'neto'          => $neto,
                'neto_21'       => $neto21,
                'neto_10_5'     => $neto105,
                'monto_iva21'   => $iva21,
                'monto_iva10_5' => $iva105,
                'total'         => $total,
                'saldo'         => $saldo,
                'estado'        => $data['estado'],
            ]);

            foreach ($data['items'] as $item) {
                $art = $articulos->get($item['art_idx']);
                if (! $art) {
                    continue;
                }

                $subtotal    = round($item['cantidad'] * $item['precio'], 2);
                $subtotalIva = round($subtotal * ($item['iva'] / 100), 2);

                DetCompra::create([
                    'compra_id'       => $compra->id,
                    'articulo_id'     => $art->id,
                    'cantidad'        => $item['cantidad'],
                    'precio_unitario' => $item['precio'],
                    'alicuota_iva'    => $item['iva'],
                    'subtotal'        => $subtotal,
                    'subtotal_iva'    => $subtotalIva,
                ]);

                if ($art->modifica_stock) {
                    $art->increment('stock', $item['cantidad']);

                    MovimientoArticulo::create([
                        'articulo_id'     => $art->id,
                        'tipo'            => 'entrada',
                        'cantidad'        => $item['cantidad'],
                        'fecha'           => $data['fecha']->toDateString(),
                        'referencia_type' => Compra::class,
                        'referencia_id'   => $compra->id,
                        'observacion'     => "Compra #{$compra->id} - Fac. {$data['letra']}-{$data['nro_factura']}",
                    ]);

                    $art->update(['precio_costo' => $item['precio']]);
                }
            }

            // Actualizar saldo del proveedor y crear movimiento CC
            if ($data['estado'] === 'P') {
                $nuevoSaldo = round((float) $proveedor->fresh()->saldo + $total, 2);
                $proveedor->update(['saldo' => $nuevoSaldo]);

                CcProveedor::create([
                    'proveedor_id'    => $proveedor->id,
                    'fecha'           => $data['fecha']->toDateString(),
                    'concepto'        => "Compra #{$compra->id} - Fac. {$data['letra']}-{$data['nro_factura']}",
                    'debe'            => $total,
                    'haber'           => 0,
                    'saldo'           => $nuevoSaldo,
                    'referencia_type' => Compra::class,
                    'referencia_id'   => $compra->id,
                ]);
            }
        }

        $this->command->info('ComprasSeeder: ' . count($comprasData) . ' compras creadas.');
    }
}
