<?php

namespace App\Livewire\Presupuestos;

use App\Models\Articulo;
use App\Models\Cliente;
use App\Models\DetPresupuesto;
use App\Models\ListaPrecio;
use App\Models\Presupuesto;
use App\Services\Pricing\PriceEngine;
use App\Services\Pricing\CotizacionRequest;
use Livewire\Attributes\Computed;
use App\Livewire\Concerns\BuscaCliente;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Presupuesto')]
class Form extends Component
{
    use BuscaCliente;
    public ?int $presupuestoId = null;

    // Cabecera
    public ?int    $cliente_id       = null;
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
            'cliente_id'    => 'required|exists:clientes,id',
            'fecha'         => 'required|date',
            'dias_validez'  => 'required|integer|min:1|max:365',
            'items'         => 'required|array|min:1',
            'items.*.articulo_id'    => 'required|exists:articulos,id',
            'items.*.cantidad'       => 'required|numeric|min:0.001',
            'items.*.precio'         => 'required|numeric|min:0',
        ];
    }

    protected function messages(): array
    {
        return [
            'cliente_id.required'           => 'Debe seleccionar un cliente.',
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
            $this->presupuestoId   = $p->id;
            $this->cliente_id      = $p->cliente_id;
            $this->lista_precio_id = $p->lista_precio_id;
            $this->fecha           = $p->fecha->toDateString();
            $this->dias_validez    = $p->dias_validez;
            $this->condicion_pago  = $p->condicion_pago ?? '';
            $this->plazo_entrega   = $p->plazo_entrega ?? '';
            $this->observacion     = $p->observacion ?? '';
            $this->estado          = $p->estado;
            $this->clienteData     = $this->loadClienteData($p->cliente_id);

            $this->items = $p->detalles->map(fn ($d) => [
                'articulo_id'  => $d->articulo_id,
                'descripcion'  => $d->descripcion ?? $d->articulo?->descripcion ?? '',
                'unidad_medida'=> $d->articulo?->unidad_medida ?? 'unidad',
                'cantidad'     => (float) $d->cantidad,
                'precio'       => (float) $d->precio_unitario,
                'alicuota_iva' => (float) $d->alicuota_iva,
                'descuento'    => (float) $d->descuento,
                'subtotal'     => (float) $d->subtotal,
                '_search'      => $d->articulo?->codigo . ' — ' . $d->descripcion,
            ])->toArray();

            $this->recalcularTotales();
        }

        if (empty($this->items)) {
            $this->addItem();
        }
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

        $this->recalcularItem($index);
    }

    public function updatedItems(mixed $value, string $key): void
    {
        $parts = explode('.', $key);
        if (count($parts) === 2) {
            $this->recalcularItem((int) $parts[0]);
        }
    }

    private function calcularPrecioArticulo(Articulo $art, float $cantidad): float
    {
        try {
            $req = new CotizacionRequest(
                articuloId: $art->id,
                clienteId: $this->cliente_id,
                listaPrecioId: $this->lista_precio_id,
                cantidad: $cantidad,
            );
            $resultado = app(PriceEngine::class)->cotizar($req);
            return $resultado->precioFinal;
        } catch (\Throwable) {
            return (float) $art->precio_unitario;
        }
    }

    private function recalcularItem(int $index): void
    {
        $item      = $this->items[$index];
        $precio    = (float) ($item['precio'] ?? 0);
        $cantidad  = (float) ($item['cantidad'] ?? 1);
        $descuento = (float) ($item['descuento'] ?? 0);
        $iva       = (float) ($item['alicuota_iva'] ?? 21);

        // precio ya viene con IVA (final), extraemos neto
        $neto      = $precio / (1 + $iva / 100);
        $netoDesc  = $neto * (1 - $descuento / 100);
        $subtotal  = round($netoDesc * $cantidad, 2);
        $subtIva   = round($subtotal * (1 + $iva / 100), 2);

        $this->items[$index]['subtotal'] = $subtotal;
        $this->recalcularTotales();
    }

    public function recalcularTotales(): void
    {
        $neto21 = $neto105 = $iva21 = $iva105 = 0;

        foreach ($this->items as $item) {
            $precio   = (float) ($item['precio'] ?? 0);
            $cantidad = (float) ($item['cantidad'] ?? 1);
            $desc     = (float) ($item['descuento'] ?? 0);
            $iva      = (float) ($item['alicuota_iva'] ?? 21);

            $neto     = $precio / (1 + $iva / 100);
            $netoDesc = $neto * (1 - $desc / 100);
            $subtotal = $netoDesc * $cantidad;

            if (abs($iva - 10.5) < 0.01) {
                $neto105 += $subtotal;
                $iva105  += $subtotal * 0.105;
            } else {
                $neto21 += $subtotal;
                $iva21  += $subtotal * ($iva / 100);
            }
        }

        $this->neto_21   = round($neto21, 2);
        $this->neto_10_5 = round($neto105, 2);
        $this->iva21     = round($iva21, 2);
        $this->iva10_5   = round($iva105, 2);
        $this->neto      = round($neto21 + $neto105, 2);
        $this->total     = round($neto21 + $iva21 + $neto105 + $iva105, 2);
    }

    public function save(): void
    {
        if ($this->estado === 'F' || $this->estado === 'N') {
            session()->flash('error', 'No se puede modificar un presupuesto facturado o anulado.');
            return;
        }

        $this->validate();
        $this->recalcularTotales();

        $fechaVto = now()->parse($this->fecha)->addDays($this->dias_validez)->toDateString();

        $data = [
            'cliente_id'      => $this->cliente_id,
            'user_id'         => auth()->id(),
            'lista_precio_id' => $this->lista_precio_id,
            'fecha'           => $this->fecha,
            'fecha_vencimiento' => $fechaVto,
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

        if ($this->presupuestoId) {
            $presupuesto = Presupuesto::findOrFail($this->presupuestoId);
            $presupuesto->update($data);
        } else {
            $ultimo = Presupuesto::max('nro_presupuesto') ?? 0;
            $data['nro_presupuesto'] = $ultimo + 1;
            $presupuesto = Presupuesto::create($data);
        }

        // Guardar detalles
        $presupuesto->detalles()->delete();
        foreach ($this->items as $item) {
            if (! $item['articulo_id']) continue;
            $art    = Articulo::find($item['articulo_id']);
            $precio = (float) $item['precio'];
            $iva    = (float) $item['alicuota_iva'];
            $desc   = (float) $item['descuento'];
            $cant   = (float) $item['cantidad'];
            $neto   = $precio / (1 + $iva / 100);
            $netoD  = $neto * (1 - $desc / 100);

            DetPresupuesto::create([
                'presupuesto_id'  => $presupuesto->id,
                'articulo_id'     => $item['articulo_id'],
                'descripcion'     => $item['descripcion'] ?: $art?->descripcion,
                'cantidad'        => $cant,
                'precio_unitario' => $neto,
                'alicuota_iva'    => $iva,
                'descuento'       => $desc,
                'subtotal'        => round($netoD * $cant, 2),
                'subtotal_iva'    => round($netoD * $cant * (1 + $iva / 100), 2),
            ]);
        }

        session()->flash('success', $this->presupuestoId ? 'Presupuesto actualizado.' : 'Presupuesto creado.');
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
