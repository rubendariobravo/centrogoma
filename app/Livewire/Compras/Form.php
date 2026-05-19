<?php

namespace App\Livewire\Compras;

use App\Models\Articulo;
use App\Models\Caja;
use App\Models\CcProveedor;
use App\Models\Compra;
use App\Models\DetCompra;
use App\Models\MovimientoArticulo;
use App\Models\Proveedor;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Nueva compra')]
class Form extends Component
{
    public ?int $compraId = null;

    // Encabezado
    public ?int    $proveedor_id   = null;
    public string  $fecha          = '';
    public string  $nro_factura    = '';
    public string  $letra          = 'B';
    public string  $forma_pago     = 'cuenta_corriente';
    public string  $observacion    = '';

    // Ítems
    public array $items = [];

    // Caja abierta (opcional en compras)
    public ?int $cajaId = null;

    // Modo lectura (ya guardada)
    public bool $readOnly = false;

    public function mount(?int $compra = null): void
    {
        $this->fecha = now()->toDateString();

        $caja = Caja::where('estado', 'abierta')
            ->where('user_id', Auth::id())
            ->latest()->first();

        $this->cajaId = $caja?->id;

        if ($compra) {
            $this->cargarCompra($compra);
        } else {
            $this->addItem();
        }
    }

    private function cargarCompra(int $id): void
    {
        $c = Compra::with(['detalles.articulo', 'proveedor'])->findOrFail($id);
        $this->compraId    = $c->id;
        $this->proveedor_id = $c->proveedor_id;
        $this->fecha        = $c->fecha->toDateString();
        $this->nro_factura  = $c->nro_factura ?? '';
        $this->letra        = $c->letra ?? 'B';
        $this->observacion  = $c->observacion ?? '';
        $this->readOnly     = true;

        $this->items = $c->detalles->map(fn ($d) => [
            'articulo_id'    => $d->articulo_id,
            'descripcion'    => $d->articulo?->descripcion ?? '',
            'unidad_medida'  => $d->articulo?->unidad_medida ?? 'unidad',
            'cantidad'       => (float) $d->cantidad,
            'precio'         => (float) $d->precio_unitario,
            'alicuota_iva'   => (float) $d->alicuota_iva,
            'subtotal'       => (float) $d->subtotal,
        ])->toArray();
    }

    public function addItem(): void
    {
        $this->items[] = [
            'articulo_id'  => null,
            'descripcion'  => '',
            'unidad_medida'=> 'unidad',
            'cantidad'     => 1,
            'precio'       => 0,
            'alicuota_iva' => 21,
            'subtotal'     => 0,
        ];
    }

    public function removeItem(int $index): void
    {
        if (count($this->items) > 1) {
            array_splice($this->items, $index, 1);
            $this->items = array_values($this->items);
        }
    }

    public function save(array $totales): void
    {
        if ($this->readOnly) {
            return;
        }

        // Cargar ítems desde el payload de Alpine ANTES de validar
        if (! empty($totales['items'])) {
            $this->items = $totales['items'];
        }

        $this->validate([
            'proveedor_id'       => 'required|exists:proveedores,id',
            'fecha'              => 'required|date',
            'items'              => 'required|array|min:1',
            'items.*.articulo_id'=> 'required|exists:articulos,id',
            'items.*.cantidad'   => 'required|numeric|min:0.001',
            'items.*.precio'     => 'required|numeric|min:0',
        ], [
            'proveedor_id.required'        => 'Seleccione un proveedor.',
            'items.*.articulo_id.required' => 'Seleccione un artículo en cada línea.',
            'items.*.cantidad.min'         => 'La cantidad debe ser mayor a 0.',
        ]);

        $neto_21   = (float) ($totales['neto_21'] ?? 0);
        $neto_10_5 = (float) ($totales['neto_10_5'] ?? 0);
        $neto_exento = (float) ($totales['neto_exento'] ?? 0);
        $iva_21    = (float) ($totales['iva_21'] ?? 0);
        $iva_10_5  = (float) ($totales['iva_10_5'] ?? 0);
        $total     = (float) ($totales['total'] ?? 0);
        $neto      = $neto_21 + $neto_10_5 + $neto_exento;

        $compra = Compra::create([
            'proveedor_id'   => $this->proveedor_id,
            'user_id'        => Auth::id(),
            'caja_id'        => $this->cajaId,
            'fecha'          => $this->fecha,
            'nro_factura'    => $this->nro_factura ?: null,
            'letra'          => $this->letra,
            'neto'           => $neto,
            'neto_21'        => $neto_21,
            'neto_10_5'      => $neto_10_5,
            'monto_iva21'    => $iva_21,
            'monto_iva10_5'  => $iva_10_5,
            'total'          => $total,
            'saldo'          => $this->forma_pago === 'cuenta_corriente' ? $total : 0,
            'estado'         => $this->forma_pago === 'cuenta_corriente' ? 'P' : 'C',
            'observacion'    => $this->observacion ?: null,
        ]);

        foreach ($this->items as $item) {
            $art = Articulo::find($item['articulo_id']);
            if (! $art) continue;

            $subtotalNeto = round((float)$item['cantidad'] * (float)$item['precio'], 2);
            $subtotalIva  = round($subtotalNeto * ((float)$item['alicuota_iva'] / 100), 2);

            DetCompra::create([
                'compra_id'      => $compra->id,
                'articulo_id'    => $art->id,
                'cantidad'       => $item['cantidad'],
                'precio_unitario'=> $item['precio'],
                'alicuota_iva'   => $item['alicuota_iva'],
                'subtotal'       => $subtotalNeto,
                'subtotal_iva'   => $subtotalIva,
            ]);

            if ($art->modifica_stock) {
                $art->increment('stock', $item['cantidad']);

                MovimientoArticulo::create([
                    'articulo_id'     => $art->id,
                    'tipo'            => 'entrada',
                    'cantidad'        => $item['cantidad'],
                    'fecha'           => $this->fecha,
                    'referencia_type' => Compra::class,
                    'referencia_id'   => $compra->id,
                    'observacion'     => "Compra #{$compra->id}" . ($this->nro_factura ? " - Fac. {$this->letra}-{$this->nro_factura}" : ''),
                ]);
            }

            // Actualizar precio_costo del artículo
            $art->update(['precio_costo' => $item['precio']]);
        }

        // Actualizar saldo del proveedor y crear movimiento CC
        if ($this->forma_pago === 'cuenta_corriente') {
            $proveedor = Proveedor::find($this->proveedor_id);
            if ($proveedor) {
                $saldoAnterior = (float) $proveedor->saldo;
                $nuevoSaldo    = round($saldoAnterior + $total, 2);
                $proveedor->update(['saldo' => $nuevoSaldo]);

                CcProveedor::create([
                    'proveedor_id'   => $this->proveedor_id,
                    'fecha'          => $this->fecha,
                    'concepto'       => "Compra #{$compra->id}" . ($this->nro_factura ? " - Fac. {$this->letra}-{$this->nro_factura}" : ''),
                    'debe'           => $total,
                    'haber'          => 0,
                    'saldo'          => $nuevoSaldo,
                    'referencia_type'=> Compra::class,
                    'referencia_id'  => $compra->id,
                ]);
            }
        }

        session()->flash('success', "Compra registrada correctamente.");
        $this->redirect(route('compras'), navigate: true);
    }

    public function render()
    {
        $articulosData = Articulo::where('estado', 'A')
            ->orderBy('descripcion')
            ->get()
            ->map(fn ($a) => [
                'id'            => $a->id,
                'codigo'        => $a->codigo,
                'descripcion'   => $a->descripcion,
                'unidad_medida' => $a->unidad_medida,
                'alicuota_iva'  => (float) $a->alicuota_iva,
                'precio_costo'  => (float) $a->precio_costo,
            ])
            ->values()
            ->toArray();

        return view('livewire.compras.form', [
            'articulosData' => $articulosData,
            'proveedores'   => Proveedor::where('estado', 'A')->orderBy('razon_social')->get(),
        ]);
    }
}
