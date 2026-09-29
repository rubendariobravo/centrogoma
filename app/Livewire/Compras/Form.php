<?php

namespace App\Livewire\Compras;

use App\Models\Articulo;
use App\Models\Caja;
use App\Models\Compra;
use App\Models\DetCompra;
use App\Models\FormaPago;
use App\Models\Proveedor;
use App\Services\Compra\CompraService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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

    // Caja desde la que se paga cuando la compra es de contado (no cuenta corriente)
    public ?int $cajaId = null;

    // Modo lectura (ya guardada)
    public bool $readOnly = false;

    public function mount(?int $compra = null): void
    {
        $this->fecha = now()->toDateString();

        // Caja de pago: diaria del usuario + cajas generales/bancos abiertas.
        $disponibles  = $this->cajasDisponibles();
        $this->cajaId = $disponibles->firstWhere('tipo', 'diaria')?->id ?? $disponibles->first()?->id;

        if ($compra) {
            $this->cargarCompra($compra);
        } else {
            $this->addItem();
        }
    }

    /** Cajas abiertas que el usuario puede usar para pagar (diaria propia + generales/bancos). */
    public function cajasDisponibles()
    {
        return Caja::with('user')
            ->where('estado', 'abierta')
            ->where(function ($q) {
                $q->where(fn ($d) => $d->where('tipo', 'diaria')->where('user_id', Auth::id()))
                  ->orWhereIn('tipo', ['general', 'banco']);
            })
            ->orderBy('tipo')
            ->get();
    }

    /** True si la forma de pago elegida es de contado (paga ahora, descuenta de caja). */
    public function esPagoContado(): bool
    {
        return ! FormaPago::esCuentaCorriente($this->forma_pago);
    }

    /**
     * Formas de pago admitidas en compras. Cheque queda afuera: un cheque propio se
     * emite desde una Orden de Pago (carga la compra en CC y pagala con OP).
     */
    private function formasPago()
    {
        return FormaPago::activosParaSelect()->reject(fn ($f) => $f->codigo === 'cheque')->values();
    }

    private function cargarCompra(int $id): void
    {
        $c = Compra::with(['detalles.articulo', 'proveedor'])->findOrFail($id);
        $this->compraId    = $c->id;
        $this->proveedor_id = $c->proveedor_id;
        $this->fecha        = $c->fecha->toDateString();
        $this->nro_factura  = $c->nro_factura ?? '';
        $this->letra        = $c->letra ?? 'B';
        $this->forma_pago   = $c->forma_pago ?? 'cuenta_corriente';
        $this->cajaId       = $c->caja_id;
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
            'forma_pago'         => ['required', 'in:' . $this->formasPago()->pluck('codigo')->implode(',')],
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

        $esContado = $this->esPagoContado();
        if ($esContado) {
            $this->validate(
                ['cajaId' => 'required|exists:cajas,id'],
                ['cajaId.required' => 'Seleccione la caja desde la que se paga.']
            );
            if (! Caja::find($this->cajaId)?->estaAbierta()) {
                $this->addError('cajaId', 'La caja seleccionada no está abierta.');
                return;
            }
        }

        $neto_21     = (float) ($totales['neto_21'] ?? 0);
        $neto_10_5   = (float) ($totales['neto_10_5'] ?? 0);
        $neto_exento = (float) ($totales['neto_exento'] ?? 0);
        $iva_21      = (float) ($totales['iva_21'] ?? 0);
        $iva_10_5    = (float) ($totales['iva_10_5'] ?? 0);
        $total       = (float) ($totales['total'] ?? 0);
        $neto        = $neto_21 + $neto_10_5 + $neto_exento;

        DB::transaction(function () use ($esContado, $neto, $neto_21, $neto_10_5, $iva_21, $iva_10_5, $total) {
            $compra = Compra::create([
                'proveedor_id'  => $this->proveedor_id,
                'user_id'       => Auth::id(),
                'caja_id'       => $esContado ? $this->cajaId : null,
                'fecha'         => $this->fecha,
                'nro_factura'   => $this->nro_factura ?: null,
                'letra'         => $this->letra,
                'forma_pago'    => $this->forma_pago,
                'neto'          => $neto,
                'neto_21'       => $neto_21,
                'neto_10_5'     => $neto_10_5,
                'monto_iva21'   => $iva_21,
                'monto_iva10_5' => $iva_10_5,
                'total'         => $total,
                'saldo'         => 0,
                'estado'        => 'P',
                'observacion'   => $this->observacion ?: null,
            ]);

            foreach ($this->items as $item) {
                $subtotalNeto = round((float) $item['cantidad'] * (float) $item['precio'], 2);
                $subtotalIva  = round($subtotalNeto * ((float) $item['alicuota_iva'] / 100), 2);

                DetCompra::create([
                    'compra_id'       => $compra->id,
                    'articulo_id'     => $item['articulo_id'],
                    'cantidad'        => $item['cantidad'],
                    'precio_unitario' => $item['precio'],
                    'alicuota_iva'    => $item['alicuota_iva'],
                    'subtotal'        => $subtotalNeto,
                    'subtotal_iva'    => $subtotalIva,
                ]);
            }

            // Stock, costo y CC proveedor / egreso de caja
            app(CompraService::class)->procesar($compra);
        });

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
            'formasPago'    => $this->formasPago(),
            'cajas'         => $this->readOnly ? collect() : $this->cajasDisponibles(),
        ]);
    }
}
