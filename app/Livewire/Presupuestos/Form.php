<?php

namespace App\Livewire\Presupuestos;

use App\Livewire\Concerns\BuscaCliente;
use App\Livewire\Ventas\Form as VentaForm;
use App\Models\Articulo;
use App\Models\Cliente;
use App\Models\DetPresupuesto;
use App\Models\ListaPrecio;
use App\Models\Presupuesto;
use App\Services\Pricing\CotizacionRequest;
use App\Services\Pricing\PriceEngine;
use App\Support\Pricing;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Alta, edición y duplicado de presupuestos.
 *
 * En pantalla el precio de cada renglón es FINAL (con IVA), el que se le cotiza al cliente;
 * en det_presupuestos se guarda NETO con 4 decimales, igual que en ventas, para que al
 * reabrir (o al pasar a venta) vuelva exactamente el mismo final.
 */
#[Title('Presupuesto')]
class Form extends Component
{
    use BuscaCliente;

    public ?int $presupuestoId = null;

    /** Nro. del presupuesto que se está duplicando (el nuevo no existe hasta guardar). */
    public ?int $duplicadoDe = null;

    // Cabecera
    public ?int    $cliente_id       = null;
    public string  $cliente_nombre   = '';   // sin cliente registrado (como en el Delphi)
    public ?int    $lista_precio_id  = null;
    public string  $fecha            = '';
    public int     $dias_validez     = 7;
    public string  $condicion_pago   = '';
    public string  $plazo_entrega    = '';
    public string  $observacion      = '';

    // Items
    public array $items = [];

    // Totales
    public float $neto      = 0;
    public float $neto_21   = 0;
    public float $neto_10_5 = 0;
    public float $iva21     = 0;
    public float $iva10_5   = 0;
    public float $descuento = 0;
    public float $total     = 0;

    // Estado
    public string $estado = 'A';

    // Datos del cliente para mostrar
    public array $clienteData = [];

    protected function rules(): array
    {
        return [
            'cliente_id'     => 'nullable|exists:clientes,id',
            'cliente_nombre' => 'nullable|string|max:150',
            'fecha'          => 'required|date',
            'dias_validez'   => 'required|integer|min:1|max:365',
            'items'          => 'required|array|min:1',
            'items.*.articulo_id'    => 'required|exists:articulos,id',
            'items.*.cantidad'       => 'required|numeric|min:0.001',
            'items.*.precio'         => 'required|numeric|min:0',
        ];
    }

    protected function messages(): array
    {
        return [
            'items.required'                => 'Debe agregar al menos un artículo.',
            'items.min'                     => 'Debe agregar al menos un artículo.',
            'items.*.articulo_id.required'  => 'Seleccione un artículo en cada línea.',
            'items.*.cantidad.min'          => 'La cantidad debe ser mayor a cero.',
        ];
    }

    public function mount(?int $presupuesto = null): void
    {
        $this->fecha = now()->toDateString();
        $this->lista_precio_id = ListaPrecio::where('es_default', true)->value('id');

        if ($presupuesto) {
            $p = Presupuesto::with('detalles.articulo', 'cliente')->findOrFail($presupuesto);
            $this->cargarDesde($p);
            $this->presupuestoId = $p->id;
            $this->fecha         = $p->fecha->toDateString();
            $this->estado        = $p->estado;
        } elseif ($origen = (int) request()->query('duplicar')) {
            // Duplicar: misma cabecera y renglones, fecha de hoy. No se graba hasta guardar,
            // así se ajusta antes; el original no se toca (sirve cualquier estado).
            $p = Presupuesto::with('detalles.articulo', 'cliente')->findOrFail($origen);
            $this->cargarDesde($p);
            $this->duplicadoDe = $p->nro_presupuesto;
        }

        if (empty($this->items)) {
            $this->addItem();
        }
    }

    private function cargarDesde(Presupuesto $p): void
    {
        $this->cliente_id      = $p->cliente_id;
        $this->cliente_nombre  = $p->cliente_nombre ?? '';
        $this->lista_precio_id = $p->lista_precio_id;
        $this->dias_validez    = $p->dias_validez;
        $this->condicion_pago  = $p->condicion_pago ?? '';
        $this->plazo_entrega   = $p->plazo_entrega ?? '';
        $this->observacion     = $p->observacion ?? '';
        $this->clienteData     = $this->loadClienteData($p->cliente_id);

        $this->items = $p->detalles->map(fn ($d) => [
            'articulo_id'  => $d->articulo_id,
            'descripcion'  => $d->descripcion ?? $d->articulo?->descripcion ?? '',
            'unidad_medida'=> $d->articulo?->unidad_medida ?? 'unidad',
            'cantidad'     => (float) $d->cantidad,
            // Se guarda neto: en pantalla va el final que se cotizó.
            'precio'       => Pricing::final((float) $d->precio_unitario, (float) $d->alicuota_iva),
            'alicuota_iva' => (float) $d->alicuota_iva,
            'descuento'    => (float) $d->descuento,
            'subtotal'     => (float) $d->subtotal,
            '_search'      => $d->articulo?->codigo . ' — ' . $d->descripcion,
        ])->toArray();

        $this->recalcularTotales();
    }

    public function updatedClienteId(): void
    {
        $this->clienteData = $this->loadClienteData($this->cliente_id);
        if (! empty($this->clienteData['lista_precio_id'])) {
            $this->lista_precio_id = $this->clienteData['lista_precio_id'];
        }
    }

    private function loadClienteData(?int $clienteId): array
    {
        if (! $clienteId) return [];
        $c = Cliente::find($clienteId);
        if (! $c) return [];
        return [
            'razon_social'    => $c->razon_social,
            'lista_precio_id' => $c->lista_precio_id,
            'bonificacion'    => (float) $c->bonificacion,
            'es_mayorista'    => (bool) $c->es_mayorista,
        ];
    }

    public function addItem(): void
    {
        $this->items[] = [
            'articulo_id'   => null,
            'descripcion'   => '',
            'unidad_medida' => 'unidad',
            'cantidad'      => 1,
            'precio'        => 0,
            'alicuota_iva'  => 21,
            'descuento'     => 0,
            'subtotal'      => 0,
            '_search'       => '',
        ];
    }

    public function removeItem(int $index): void
    {
        if (count($this->items) > 1) {
            array_splice($this->items, $index, 1);
            $this->items = array_values($this->items);
            $this->recalcularTotales();
        }
    }

    public function seleccionarArticulo(int $index, int $articuloId): void
    {
        $art = Articulo::find($articuloId);
        if (! $art) return;

        $precio = $this->calcularPrecioArticulo($art, (float) ($this->items[$index]['cantidad'] ?? 1));

        $this->items[$index]['articulo_id']   = $art->id;
        $this->items[$index]['descripcion']   = $art->descripcion;
        $this->items[$index]['unidad_medida'] = $art->unidad_medida;
        $this->items[$index]['alicuota_iva']  = (float) $art->alicuota_iva;
        $this->items[$index]['precio']        = $precio;
        $this->items[$index]['_search']       = $art->codigo . ' — ' . $art->descripcion;

        $this->recalcularTotales();
    }

    /** Recotiza todos los renglones con la lista y los tramos de hoy (útil al duplicar uno viejo). */
    public function actualizarPreciosALista(): void
    {
        foreach ($this->items as $i => $item) {
            if ($art = Articulo::find($item['articulo_id'] ?? 0)) {
                $this->items[$i]['precio']       = $this->calcularPrecioArticulo($art, (float) ($item['cantidad'] ?? 1));
                $this->items[$i]['alicuota_iva'] = (float) $art->alicuota_iva;
            }
        }
        $this->recalcularTotales();
    }

    public function updatedItems(mixed $value, string $key): void
    {
        if (count(explode('.', $key)) === 2) {
            $this->recalcularTotales();
        }
    }

    private function calcularPrecioArticulo(Articulo $art, float $cantidad): float
    {
        try {
            $req = new CotizacionRequest(
                articuloId: $art->id,
                cantidad: $cantidad,
                clienteId: $this->cliente_id,
                forzarListaId: $this->lista_precio_id,
            );
            $resultado = app(PriceEngine::class)->cotizar($req);
            return Pricing::final($resultado->precioUnitario, $resultado->alicuotaIva);
        } catch (\Throwable) {
            // Sin tramo en la lista: precio base del artículo (neto) llevado a final.
            return Pricing::final((float) $art->precio_unitario, (float) $art->alicuota_iva);
        }
    }

    /** Renglones con el precio en NETO (4 decimales), como los espera el cálculo de ventas. */
    private function itemsEnNeto(): array
    {
        return array_map(fn ($item) => [
            'precio'       => Pricing::neto((float) ($item['precio'] ?? 0), (float) ($item['alicuota_iva'] ?? 21)),
            'cantidad'     => (float) ($item['cantidad'] ?? 0),
            'alicuota_iva' => (float) ($item['alicuota_iva'] ?? 21),
            'descuento'    => (float) ($item['descuento'] ?? 0),
        ], $this->items);
    }

    /** Misma fórmula que Ventas\Form::calcularTotales(): el presupuesto y su venta dan igual al centavo. */
    public function recalcularTotales(): array
    {
        $t = VentaForm::calcularTotales($this->itemsEnNeto(), []);

        foreach ($t['subtotales'] as $i => $subtotal) {
            $this->items[$i]['subtotal'] = $subtotal;
        }

        $this->neto_21   = $t['neto_21'];
        $this->neto_10_5 = $t['neto_10_5'];
        $this->iva21     = $t['iva_21'];
        $this->iva10_5   = $t['iva_10_5'];
        $this->neto      = round($t['neto_21'] + $t['neto_10_5'] + $t['neto_exento'], 2);
        $this->total     = $t['total'];

        return $t;
    }

    public function save(): void
    {
        if ($this->estado === 'F' || $this->estado === 'N') {
            session()->flash('error', 'No se puede modificar un presupuesto facturado o anulado.');
            return;
        }

        $this->validate();
        $totales = $this->recalcularTotales();
        $netos   = $this->itemsEnNeto();

        $data = [
            'cliente_id'      => $this->cliente_id,
            'cliente_nombre'  => $this->cliente_id ? null : (trim($this->cliente_nombre) ?: null),
            'user_id'         => auth()->id(),
            'lista_precio_id' => $this->lista_precio_id,
            'fecha'           => $this->fecha,
            'fecha_vencimiento' => now()->parse($this->fecha)->addDays($this->dias_validez)->toDateString(),
            'dias_validez'    => $this->dias_validez,
            'neto'            => $this->neto,
            'neto_21'         => $this->neto_21,
            'neto_10_5'       => $this->neto_10_5,
            'monto_iva21'     => $this->iva21,
            'monto_iva10_5'   => $this->iva10_5,
            'descuento'       => $this->descuento,
            'total'           => $this->total,
            'estado'          => $this->estado,
            'condicion_pago'  => $this->condicion_pago ?: null,
            'plazo_entrega'   => $this->plazo_entrega ?: null,
            'observacion'     => $this->observacion ?: null,
        ];

        $presupuesto = DB::transaction(function () use ($data, $totales, $netos) {
            if ($this->presupuestoId) {
                $presupuesto = Presupuesto::findOrFail($this->presupuestoId);
                $presupuesto->update($data);
            } else {
                $data['nro_presupuesto'] = (Presupuesto::withTrashed()->lockForUpdate()->max('nro_presupuesto') ?? 0) + 1;
                $presupuesto = Presupuesto::create($data);
            }

            $presupuesto->detalles()->delete();
            foreach ($this->items as $i => $item) {
                if (! $item['articulo_id']) continue;

                DetPresupuesto::create([
                    'presupuesto_id'  => $presupuesto->id,
                    'articulo_id'     => $item['articulo_id'],
                    'descripcion'     => $item['descripcion'] ?: Articulo::find($item['articulo_id'])?->descripcion,
                    'cantidad'        => $netos[$i]['cantidad'],
                    'precio_unitario' => $netos[$i]['precio'],
                    'alicuota_iva'    => $netos[$i]['alicuota_iva'],
                    'descuento'       => $netos[$i]['descuento'],
                    'subtotal'        => $totales['subtotales'][$i],
                    'subtotal_iva'    => round($totales['subtotales'][$i] + $totales['ivas'][$i], 2),
                ]);
            }

            return $presupuesto;
        });

        session()->flash('success', match (true) {
            (bool) $this->presupuestoId => 'Presupuesto actualizado.',
            (bool) $this->duplicadoDe   => "Presupuesto #{$presupuesto->nro_presupuesto} creado (copia del #{$this->duplicadoDe}).",
            default                     => 'Presupuesto creado.',
        });
        $this->redirect(route('presupuestos'), navigate: true);
    }

    #[Computed]
    public function articulosSearch(): array
    {
        return [];
    }

    public function buscarArticulos(string $q): array
    {
        if (strlen($q) < 2) return [];
        return Articulo::where('estado', 'A')
            ->where(fn ($query) =>
                $query->where('descripcion', 'like', "%{$q}%")
                      ->orWhere('codigo', 'like', "%{$q}%")
            )
            ->limit(15)
            ->get()
            ->map(fn ($a) => [
                'id'           => $a->id,
                'codigo'       => $a->codigo,
                'descripcion'  => $a->descripcion,
                'unidad_medida'=> $a->unidad_medida,
                'alicuota_iva' => (float) $a->alicuota_iva,
            ])
            ->values()
            ->toArray();
    }

    public function render()
    {
        return view('livewire.presupuestos.form', [
            'listasPrecios' => ListaPrecio::where('activo', true)->orderBy('orden')->get(),
        ]);
    }
}
