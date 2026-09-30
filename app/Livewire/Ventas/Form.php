<?php

namespace App\Livewire\Ventas;

use App\Models\Articulo;
use App\Models\Caja;
use App\Models\CcCliente;
use App\Models\Banco;
use App\Models\Cliente;
use App\Models\DetVenta;
use App\Models\ListaPrecio;
use App\Models\MovimientoArticulo;
use App\Models\Presupuesto;
use App\Models\Venta;
use App\Models\VariableEntorno;
use App\Services\Caja\CajaService;
use App\Services\Cheque\ChequeService;
use App\Services\Pricing\CotizacionRequest;
use App\Services\Pricing\PriceEngine;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Nueva venta')]
class Form extends Component
{
    public ?int $ventaId = null;

    // Encabezado
    public ?int    $cliente_id        = null;
    public string  $fecha             = '';
    public string  $tipo_comprobante  = 'factura';
    public string  $letra             = 'B';
    public string  $forma_pago        = 'efectivo';
    public string  $observacion       = '';

    // Datos del cliente (para percepciones, se actualizan al cambiar cliente)
    public array $clienteData = [
        'razon_social'    => '',
        'percepcion_iva'  => false,
        'porcentaje_iibb' => 0,
        'es_mayorista'    => false,
        'tipoiva_codigo'  => '99', // consumidor final por defecto
    ];

    // Ítems de la venta
    public array $items = [];

    // Totales (calculados en Alpine, enviados por save para persistir)
    public float $descuento_general = 0;

    // Caja abierta
    public ?int $cajaId = null;
    public bool $sinCaja = false;

    // Lista de precios activa en esta venta (puede ser forzada por el operador)
    public ?int  $listaIdForzada  = null;  // null → motor resuelve automáticamente
    public bool  $listaModificada = false; // true → el operador la cambió de la default del cliente

    // Venta en modo lectura (ya guardada)
    public bool $readOnly = false;

    // Datos de la venta cargada (modo readOnly)
    public array $pagosVenta = [];
    public array $ventaInfo = [];

    public string $payloadJson = '';

    // Presupuesto origen (pre-carga el form desde un presupuesto)
    public ?int $presupuestoOrigenId = null;

    public function mount(?int $venta = null): void
    {
        $this->fecha = now()->toDateString();

        $caja = Caja::where('estado', 'abierta')->where('tipo', 'diaria')
            ->where('user_id', auth()->id())
            ->latest()->first();

        if ($caja) {
            $this->cajaId = $caja->id;
        } else {
            $this->sinCaja = true;
        }

        // Inicializar la lista activa con la default del sistema.
        // updatedClienteId() la sobreescribirá con la lista del cliente al seleccionarlo.
        $this->listaIdForzada = ListaPrecio::default()?->id;

        if ($venta) {
            $this->cargarVenta($venta);
        } else {
            // Pre-cargar desde presupuesto si viene por query param
            $presupuestoId = (int) request()->query('presupuesto_id');
            if ($presupuestoId) {
                $this->precargarDesdePresupuesto($presupuestoId);
            } else {
                $this->addItem();
            }
        }
    }

    private function precargarDesdePresupuesto(int $id): void
    {
        $p = Presupuesto::with('detalles.articulo', 'cliente')->find($id);
        if (! $p || ! in_array($p->estado, ['A', 'V'])) {
            $this->addItem();
            return;
        }

        $this->presupuestoOrigenId = $p->id;
        $this->cliente_id          = $p->cliente_id;
        $this->listaIdForzada      = $p->lista_precio_id;

        if ($p->cliente_id) {
            $this->cargarDatosCliente($p->cliente_id);
        }

        $this->items = $p->detalles
            ->filter(fn ($d) => $d->articulo_id)
            ->map(fn ($d) => [
                'articulo_id'       => $d->articulo_id,
                'rubro_id'          => $d->articulo?->rubro_id,
                'descripcion'       => $d->descripcion ?? $d->articulo?->descripcion ?? '',
                'tipo_venta'        => $d->articulo?->tipo_venta ?? 'CAJA',
                'unidad_medida'     => $d->articulo?->unidad_medida ?? 'unidad',
                'cantidad'          => (float) $d->cantidad,
                'precio'            => (float) $d->precio_unitario,
                'alicuota_iva'      => (float) $d->alicuota_iva,
                'descuento'         => (float) $d->descuento,
                'subtotal'          => (float) $d->subtotal,
                'precio_manual'     => false,
                'lista_precio_id'   => $p->lista_precio_id,
                'tramo_aplicado_id' => null,
                'regla_aplicada'    => 'presupuesto',
                'precio_motor'      => null,
            ])->values()->toArray();

        if (empty($this->items)) {
            $this->addItem();
        }
    }

    private function cargarVenta(int $id): void
    {
        $v = Venta::with(['detalles.articulo', 'cliente', 'pagos'])->findOrFail($id);
        $this->ventaId          = $v->id;
        $this->cliente_id       = $v->cliente_id;
        $this->fecha            = $v->fecha->toDateString();
        $this->tipo_comprobante = $v->tipo_comprobante;
        $this->letra            = $v->letra_comprobante ?? 'B';
        $this->forma_pago       = $v->pagos->first()?->forma_pago ?? 'efectivo';
        $this->observacion      = $v->observacion ?? '';
        $this->readOnly         = true;

        if ($v->cliente) {
            $this->cargarDatosCliente($v->cliente_id);
        }

        $this->items = $v->detalles->map(fn ($d) => [
            'articulo_id'    => $d->articulo_id,
            'rubro_id'       => $d->articulo?->rubro_id,
            'descripcion'    => $d->descripcion ?? $d->articulo?->descripcion ?? '',

            'unidad_medida'  => $d->articulo?->unidad_medida ?? 'unidad',
            'cantidad'       => (float) $d->cantidad,
            'precio'         => (float) $d->precio_unitario,
            'alicuota_iva'   => (float) $d->alicuota_iva,
            'descuento'      => (float) $d->descuento,
            'subtotal'       => (float) $d->subtotal,
        ])->toArray();

        $this->descuento_general = (float) $v->descuento;

        $this->pagosVenta = $v->pagos->map(fn ($p) => [
            'forma_pago' => $p->forma_pago,
            'monto'      => (float) $p->monto,
            'fecha'      => $p->fecha?->toDateString(),
        ])->toArray();

        $this->ventaInfo = [
            'nro_venta'    => $v->nro_venta,
            'neto_21'      => (float) $v->neto_21,
            'neto_10_5'    => (float) $v->neto_10_5,
            'monto_iva21'  => (float) $v->monto_iva21,
            'monto_iva10_5'=> (float) $v->monto_iva10_5,
            'percepcion'   => (float) $v->percepcion,
            'percepiva21'  => (float) $v->percepiva21,
            'percepiva10_5'=> (float) $v->percepiva10_5,
            'total'        => (float) $v->total,
            'saldo'        => (float) $v->saldo,
            'estado'       => $v->estado,
            'pagado'       => (float) $v->pagos->sum('monto'),
        ];
    }

    public function updatedClienteId(): void
    {
        if ($this->cliente_id) {
            $this->cargarDatosCliente($this->cliente_id);
            // Resetear la lista a la asignada al nuevo cliente (o default del sistema)
            $cliente = Cliente::find($this->cliente_id);
            $this->listaIdForzada  = $cliente?->lista_precio_id ?? ListaPrecio::default()?->id;
            $this->listaModificada = false;
        } else {
            $this->clienteData = [
                'razon_social'    => '',
                'percepcion_iva'  => false,
                'porcentaje_iibb' => 0,
                'es_mayorista'    => false,
                'tipoiva_codigo'  => '99',
            ];
            $this->listaIdForzada  = ListaPrecio::default()?->id;
            $this->listaModificada = false;
        }

        // Al cambiar de cliente puede cambiar la lista efectiva, re-cotizamos
        // todas las lineas que tengan articulo cargado y NO tengan precio manual.
        foreach ($this->items as $i => $item) {
            if (! empty($item['articulo_id']) && empty($item['precio_manual'])) {
                $this->cotizarItem($i);
            }
        }

        // Aviso al frontend Alpine que los items cambiaron y debe sincronizar.
        $this->dispatch('items-actualizados');
    }

    /**
     * Cambia la lista de precios activa para esta venta.
     * Solo accesible para roles con permiso (verificado en la vista y aquí).
     * Re-cotiza todos los ítems con la nueva lista (excepto los de precio manual).
     */
    public function cambiarLista(int $listaId): void
    {
        if (! auth()->user()->hasRole(['super-admin', 'admin'])) {
            return;
        }

        $this->listaIdForzada  = $listaId;
        $this->listaModificada = true;

        foreach ($this->items as $i => $item) {
            if (! empty($item['articulo_id']) && empty($item['precio_manual'])) {
                $this->cotizarItem($i);
            }
        }

        $this->dispatch('items-actualizados');
    }

    /**
     * Restaura la lista al default del cliente actual (o del sistema si no hay cliente).
     */
    public function restaurarLista(): void
    {
        if (! auth()->user()->hasRole(['super-admin', 'admin'])) {
            return;
        }

        $cliente = $this->cliente_id ? Cliente::find($this->cliente_id) : null;
        $this->listaIdForzada  = $cliente?->lista_precio_id ?? ListaPrecio::default()?->id;
        $this->listaModificada = false;

        foreach ($this->items as $i => $item) {
            if (! empty($item['articulo_id']) && empty($item['precio_manual'])) {
                $this->cotizarItem($i);
            }
        }

        $this->dispatch('items-actualizados');
    }

    private function cargarDatosCliente(int $id): void
    {
        $c = Cliente::with('tipoIva')->findOrFail($id);
        $this->clienteData = [
            'razon_social'    => $c->razon_social,
            'percepcion_iva'  => (bool) $c->percepcion_iva,
            'porcentaje_iibb' => (float) ($c->porcentaje_iibb * 100),
            'es_mayorista'    => (bool) $c->es_mayorista,
            'tipoiva_codigo'  => $c->tipoIva?->codigo_afip ?? '99',
        ];

        // Letra según tipo IVA del cliente
        $this->letra = $c->tipoIva?->codigo_afip === '01' ? 'A' : 'B';
    }

    public function addItem(): void
    {
        $this->items[] = [
            'articulo_id'   => null,
            'rubro_id'      => null,
            'descripcion'   => '',

            'unidad_medida' => 'unidad',
            'cantidad'      => 1,
            'precio'        => 0,
            'alicuota_iva'  => 21,
            'descuento'     => 0,
            'subtotal'      => 0,
        ];
    }

    public function removeItem(int $index): void
    {
        if (count($this->items) > 1) {
            $rubroId = (int) ($this->items[$index]['rubro_id'] ?? 0);
            $unidad  = $this->items[$index]['unidad_medida'] ?? '';

            array_splice($this->items, $index, 1);
            $this->items = array_values($this->items);

            // Si el artículo eliminado pertenecía a un grupo, re-cotizar los restantes
            if ($rubroId && $unidad) {
                $this->recotizarGrupo($rubroId, $unidad);
            }
        }
    }

    /**
     * Selecciona un articulo en la fila $index.
     * Si el articulo ya esta cargado en otra fila, suma cantidades en la fila
     * existente (regla del cliente: no se pueden armar dos lineas con el mismo
     * producto) y elimina la fila actual.
     * Si es nuevo, pre-carga datos estaticos y dispara la cotizacion.
     */
    public function seleccionarArticulo(int $index, int $articuloId, ?float $cantidad = null): void
    {
        if (! isset($this->items[$index])) {
            return;
        }

        $articulo = Articulo::find($articuloId);
        if (! $articulo) {
            $this->addError("items.$index.articulo_id", 'Articulo no encontrado.');
            return;
        }

        // Si el frontend envio cantidad reciente, la usamos
        if ($cantidad !== null && $cantidad > 0) {
            $this->items[$index]['cantidad'] = $cantidad;
        }

        // Detectar duplicado en otra fila
        foreach ($this->items as $i => $existing) {
            if ($i === $index) continue;
            if ((int) ($existing['articulo_id'] ?? 0) === $articuloId) {
                $cantidadAcum = (float) ($existing['cantidad'] ?? 0) + (float) ($this->items[$index]['cantidad'] ?? 1);
                $this->items[$i]['cantidad'] = $cantidadAcum;

                $rubroId = (int) ($existing['rubro_id'] ?? 0);
                $unidad  = $existing['unidad_medida'] ?? '';

                // Eliminar fila actual antes de re-cotizar (ajusta índices)
                array_splice($this->items, $index, 1);
                $this->items = array_values($this->items);

                if ($rubroId && $unidad) {
                    $this->recotizarGrupo($rubroId, $unidad);
                } else {
                    $newI = $i > $index ? $i - 1 : $i;
                    $this->cotizarItem($newI);
                }
                return;
            }
        }

        // Nuevo en esta fila: cargar datos estaticos
        $this->items[$index] = array_merge($this->items[$index], [
            'articulo_id'   => $articulo->id,
            'rubro_id'      => $articulo->rubro_id,
            'descripcion'   => $articulo->descripcion,

            'unidad_medida' => $articulo->unidad_medida,
            'alicuota_iva'  => (float) $articulo->alicuota_iva,
        ]);

        // Asegurar cantidad valida antes de cotizar
        if (! isset($this->items[$index]['cantidad']) || (float) $this->items[$index]['cantidad'] <= 0) {
            $this->items[$index]['cantidad'] = 1;
        }

        $rubroId = (int) ($articulo->rubro_id ?? 0);
        $unidad  = $articulo->unidad_medida;

        // Re-cotizar el grupo entero (incluye esta fila y las que ya estaban)
        if ($rubroId && $unidad) {
            $this->recotizarGrupo($rubroId, $unidad);
        } else {
            $this->cotizarItem($index);
        }
    }

    /**
     * Recotiza la fila cuando cambia la cantidad. Si el usuario habia
     * ingresado un precio manualmente (override), no lo pisa.
     *
     * IMPORTANTE: el frontend Alpine y el estado Livewire de items[] no estan
     * automaticamente sincronizados. Por eso recibimos la cantidad como
     * parametro: el JS la pasa explicitamente con el valor recien tipeado.
     */
    public function actualizarCantidad(int $index, ?float $cantidad = null, bool $forzarRecotizar = false): void
    {
        if (! isset($this->items[$index])) {
            return;
        }
        if (! ($this->items[$index]['articulo_id'] ?? null)) {
            return;
        }

        if ($cantidad !== null && $cantidad > 0) {
            $this->items[$index]['cantidad'] = $cantidad;
        }

        $rubroId = (int) ($this->items[$index]['rubro_id'] ?? 0);
        $unidad  = $this->items[$index]['unidad_medida'] ?? '';

        if (! $forzarRecotizar && ! empty($this->items[$index]['precio_manual'])) {
            // El usuario sobreescribio: solo actualizamos subtotal con su precio.
            $this->items[$index]['subtotal'] = round(
                (float) $this->items[$index]['cantidad'] * (float) $this->items[$index]['precio'],
                2
            );
            // Pero la cantidad del grupo cambió → re-cotizar los otros miembros del grupo.
            if ($rubroId && $unidad) {
                foreach ($this->items as $i => $item) {
                    if ($i === $index) continue;
                    if ((int) ($item['rubro_id'] ?? 0) === $rubroId && ($item['unidad_medida'] ?? '') === $unidad) {
                        if (empty($item['precio_manual'])) {
                            $this->cotizarItem($i);
                        }
                    }
                }
            }
            return;
        }

        if ($rubroId && $unidad) {
            $this->recotizarGrupo($rubroId, $unidad);
        } else {
            $this->cotizarItem($index);
        }
    }

    /**
     * Llama al motor de precios para resolver precio + subtotal + IVA + desglose
     * de la fila $index. Reemplaza el calculo en JS.
     */
    public function cotizarItem(int $index): void
    {
        if (! isset($this->items[$index])) {
            return;
        }
        $articuloId = (int) ($this->items[$index]['articulo_id'] ?? 0);
        $cantidad   = (float) ($this->items[$index]['cantidad'] ?? 0);
        if ($articuloId <= 0 || $cantidad <= 0) {
            return;
        }

        try {
            /** @var PriceEngine $engine */
            $engine = app(PriceEngine::class);

            $rubroId = (int) ($this->items[$index]['rubro_id'] ?? 0);
            $unidad  = $this->items[$index]['unidad_medida'] ?? '';
            $cantidadTramo = ($rubroId && $unidad)
                ? $this->calcularCantidadGrupo($rubroId, $unidad)
                : null;

            $resultado = $engine->cotizar(new CotizacionRequest(
                articuloId: $articuloId,
                cantidad: $cantidad,
                clienteId: $this->cliente_id,
                formaPago: 'efectivo', // recargo no efectivo se aplica al total al final, no por linea
                cantidadTramo: $cantidadTramo,
                forzarListaId: $this->listaIdForzada,
            ));

            $this->items[$index] = array_merge($this->items[$index], [
                'precio'             => $resultado->precioUnitario,
                'subtotal'           => $resultado->subtotal,
                'alicuota_iva'       => $resultado->alicuotaIva,
                'lista_precio_id'    => $resultado->listaPrecioId,
                'tramo_aplicado_id'  => $resultado->tramoAplicadoId,
                'regla_aplicada'     => $resultado->reglaAplicada,
                'desglose'           => array_map(fn ($p) => $p->toArray(), $resultado->desglose),
                'precio_manual'      => false,
            ]);
        } catch (\Throwable $e) {
            \Log::warning('cotizarItem fallo', [
                'index' => $index,
                'articulo_id' => $articuloId,
                'cantidad' => $cantidad,
                'error' => $e->getMessage(),
            ]);
            $this->addError("items.$index.precio", 'No se pudo calcular el precio: ' . $e->getMessage());
        }
    }

    /**
     * Marca el precio de la fila como "manual" y recalcula subtotal con
     * el precio que el usuario tipeo. No re-cotiza con el motor.
     */
    public function marcarPrecioManual(int $index, float $precio): void
    {
        if (! isset($this->items[$index])) {
            return;
        }
        $this->items[$index]['precio'] = $precio;
        $this->items[$index]['precio_manual'] = true;
        $this->items[$index]['subtotal'] = round(
            (float) $this->items[$index]['cantidad'] * $precio,
            2
        );
    }

    /**
     * Restaura el precio del motor para la fila (anula el override manual).
     */
    public function restaurarPrecio(int $index): void
    {
        if (! isset($this->items[$index])) {
            return;
        }
        $this->items[$index]['precio_manual'] = false;
        $this->cotizarItem($index);
    }

    public function registrarVenta(array $payload): void
    {
        if ($this->readOnly) {
            return;
        }


        $this->items = $payload['items'] ?? [];
        $this->clienteData = $payload['clienteData'] ?? [];
        $this->descuento_general = $payload['descuento_general'] ?? 0;
        $pagos = collect($payload['pagos'] ?? [])
            ->map(fn ($p) => [
                'forma_pago' => $p['forma_pago'] ?? 'efectivo',
                'monto' => (float) ($p['monto'] ?? 0),
                'cheque' => $p['cheque'] ?? null,
            ])
            ->filter(fn ($p) => $p['monto'] > 0)
            ->values();

        $totales = [
            'neto_21' => $payload['neto_21'] ?? 0,
            'neto_10_5' => $payload['neto_10_5'] ?? 0,
            'iva_21' => $payload['iva_21'] ?? 0,
            'iva_10_5' => $payload['iva_10_5'] ?? 0,
            'percepcion' => $payload['percepcion'] ?? 0,
            'percepiva21' => $payload['percepiva21'] ?? 0,
            'percepiva10_5' => $payload['percepiva10_5'] ?? 0,
            'total' => $payload['total'] ?? 0,
        ];

        $this->validate([
            'fecha'           => 'required|date',
            'tipo_comprobante'=> 'required|in:factura,nota_credito,nota_debito',
            'items'           => 'required|array|min:1',
            'items.*.articulo_id' => 'required|exists:articulos,id',
            'items.*.cantidad'    => 'required|numeric|min:0.001',
            'items.*.precio'      => 'required|numeric|min:0',
        ], [
            'items.*.articulo_id.required' => 'Seleccione un artículo en cada línea.',
            'items.*.cantidad.min'         => 'La cantidad debe ser mayor a 0.',
        ]);

        // ── Validacion dura de precios contra el motor ─────────────────────
        // Recotizamos cada item con el motor y verificamos que el precio
        // que llega del frontend coincida (con tolerancia) o que este
        // marcado como override manual por el usuario.
        /** @var PriceEngine $engine */
        $engine = app(PriceEngine::class);
        $tolerancia = 0.01;

        // Pre-calcular totales de grupo para tramos grupales (mismo rubro+presentación)
        $grupoTotales = [];
        foreach ($this->items as $item) {
            $rubroId = (int) ($item['rubro_id'] ?? 0);
            $unidad  = $item['unidad_medida'] ?? '';
            if ($rubroId && $unidad) {
                $key = "{$rubroId}|{$unidad}";
                $grupoTotales[$key] = ($grupoTotales[$key] ?? 0.0) + (float) ($item['cantidad'] ?? 0);
            }
        }

        foreach ($this->items as $i => $item) {
            $articuloId = (int) ($item['articulo_id'] ?? 0);
            $cantidad   = (float) ($item['cantidad'] ?? 0);
            $precioFE   = (float) ($item['precio'] ?? 0);
            $manual     = ! empty($item['precio_manual']);

            if ($articuloId <= 0 || $cantidad <= 0) {
                continue; // ya validado arriba
            }

            $rubroId = (int) ($item['rubro_id'] ?? 0);
            $unidad  = $item['unidad_medida'] ?? '';
            $cantidadTramo = null;
            if ($rubroId && $unidad) {
                $key = "{$rubroId}|{$unidad}";
                $cantidadTramo = $grupoTotales[$key] ?? $cantidad;
            }

            try {
                $cot = $engine->cotizar(new CotizacionRequest(
                    articuloId: $articuloId,
                    cantidad: $cantidad,
                    clienteId: $this->cliente_id,
                    formaPago: 'efectivo',
                    cantidadTramo: $cantidadTramo,
                    forzarListaId: $this->listaIdForzada,
                ));
            } catch (\Throwable $e) {
                $this->addError("items.$i.precio", 'No se pudo cotizar el item: ' . $e->getMessage());
                return;
            }

            if (! $manual && abs($precioFE - $cot->precioUnitario) > $tolerancia) {
                \Log::warning('registrarVenta: precio del frontend no coincide con motor', [
                    'fila'        => $i,
                    'articulo_id' => $articuloId,
                    'cantidad'    => $cantidad,
                    'precio_fe'   => $precioFE,
                    'precio_motor'=> $cot->precioUnitario,
                ]);
                // Forzamos el precio del motor (autoridad del backend).
                $this->items[$i]['precio']   = $cot->precioUnitario;
                $this->items[$i]['subtotal'] = $cot->subtotal;
            }

            // Guardamos metadata para auditoria en det_venta.
            $this->items[$i]['lista_precio_id']   = $cot->listaPrecioId;
            $this->items[$i]['tramo_aplicado_id'] = $cot->tramoAplicadoId;
            $this->items[$i]['regla_aplicada']    = $manual ? 'manual_override' : $cot->reglaAplicada;
            $this->items[$i]['precio_motor']      = $cot->precioUnitario;
        }

        if ($pagos->isEmpty()) {
            $this->addError('pagos', 'Debe ingresar al menos una forma de pago.');
            return;
        }

        if ($pagos->contains(fn ($p) => ! in_array($p['forma_pago'], ['efectivo', 'transferencia', 'cheque', 'tarjeta', 'cuenta_corriente', 'otro'], true))) {
            $this->addError('pagos', 'Una de las formas de pago no es válida.');
            return;
        }

        $hasCC = $pagos->contains(fn ($p) => $p['forma_pago'] === 'cuenta_corriente');

        if ($hasCC && ! $this->cliente_id) {
            $this->addError('cliente_id', 'Seleccione un cliente para registrar en cuenta corriente.');
            return;
        }

        $neto_21   = (float) ($totales['neto_21'] ?? 0);
        $neto_10_5 = (float) ($totales['neto_10_5'] ?? 0);
        $iva_21    = (float) ($totales['iva_21'] ?? 0);
        $iva_10_5  = (float) ($totales['iva_10_5'] ?? 0);
        $percepcion     = (float) ($totales['percepcion'] ?? 0);
        $percepiva21    = (float) ($totales['percepiva21'] ?? 0);
        $percepiva10_5  = (float) ($totales['percepiva10_5'] ?? 0);
        $total          = (float) ($totales['total'] ?? 0);
        $neto           = $neto_21 + $neto_10_5;
        $montoPagado    = round($pagos->where('forma_pago', '!=', 'cuenta_corriente')->sum('monto'), 2);
        $saldo          = max(round($total - $montoPagado, 2), 0);
        $formaPagoPrincipal = $pagos->first()['forma_pago'];

        $nroVenta = (Venta::max('nro_venta') ?? 0) + 1;

        // Si no hay cliente seleccionado, usar "Consumidor final" por defecto
        $clienteIdFinal = $this->cliente_id ?: Cliente::where('razon_social', 'Consumidor final')->value('id');

        $venta = Venta::create([
            'cliente_id'       => $clienteIdFinal,
            'user_id'          => auth()->id(),
            'caja_id'          => $this->cajaId,
            'nro_venta'        => $nroVenta,
            'fecha'            => $this->fecha,
            'hora'             => now()->toTimeString(),
            'neto'             => $neto,
            'descuento'        => $this->descuento_general,
            'neto_21'          => $neto_21,
            'neto_10_5'        => $neto_10_5,
            'monto_iva21'      => $iva_21,
            'monto_iva10_5'    => $iva_10_5,
            'total'            => $total,
            'saldo'            => $saldo,
            'percepcion'       => $percepcion,
            'percepiva21'      => $percepiva21,
            'percepiva10_5'    => $percepiva10_5,
            'tipo_comprobante' => $this->tipo_comprobante,
            'letra_comprobante'=> $this->letra,
            'estado'           => $saldo > 0 ? 'P' : 'C',
            'observacion'      => $this->observacion ?: null,
        ]);

        // Detalles + movimientos de stock
        foreach ($this->items as $item) {
            $art = Articulo::find($item['articulo_id']);
            if (! $art) continue;

            $subtotalNeto = round((float)$item['cantidad'] * (float)$item['precio'] * (1 - (float)$item['descuento'] / 100), 2);
            $subtotalIva  = round($subtotalNeto * ((float)$item['alicuota_iva'] / 100), 2);

            DetVenta::create([
                'venta_id'          => $venta->id,
                'articulo_id'       => $art->id,
                'descripcion'       => $art->descripcion,
                'cantidad'          => $item['cantidad'],
                'precio_unitario'   => $item['precio'],
                'alicuota_iva'      => $item['alicuota_iva'],
                'descuento'         => $item['descuento'],
                'subtotal'          => $subtotalNeto,
                'subtotal_iva'      => $subtotalIva,
                // Metadata de pricing (auditoria)
                'lista_precio_id'   => $item['lista_precio_id']   ?? null,
                'tramo_aplicado_id' => $item['tramo_aplicado_id'] ?? null,
                'regla_aplicada'    => $item['regla_aplicada']    ?? null,
                'precio_motor'      => $item['precio_motor']      ?? null,
                'precio_manual'     => ! empty($item['precio_manual']),
            ]);

            if ($art->modifica_stock) {
                $art->decrement('stock', $item['cantidad']);

                MovimientoArticulo::create([
                    'articulo_id'      => $art->id,
                    'tipo'             => 'salida',
                    'cantidad'         => $item['cantidad'],
                    'fecha'            => $this->fecha,
                    'referencia_type'  => Venta::class,
                    'referencia_id'    => $venta->id,
                    'observacion'      => "Venta #{$nroVenta}",
                ]);
            }
        }

        $this->forma_pago = $formaPagoPrincipal;

        foreach ($pagos as $pago) {
            if ($pago['forma_pago'] === 'cuenta_corriente') {
                continue;
            }

            $pagoVenta = $venta->pagos()->create([
                'caja_id'    => $this->cajaId,
                'fecha'      => $this->fecha,
                'forma_pago' => $pago['forma_pago'],
                'monto'      => $pago['monto'],
            ]);

            // Cheque de tercero recibido → ingresa a la cartera de valores (estado cartera).
            // La plata entra a la caja banco al acreditarlo desde /cheques.
            $ch = $pago['cheque'] ?? null;
            if ($pago['forma_pago'] === 'cheque' && ! empty($ch['nro'])) {
                app(ChequeService::class)->registrarRecibido([
                    'banco_id'        => $ch['banco_id'] ?? null,
                    'serie'           => ($ch['serie'] ?? '') ?: null,
                    'nro_cheque'      => $ch['nro'],
                    'monto'           => (float) $pago['monto'],
                    'cliente_id'      => $this->cliente_id,
                    'caja_origen_id'  => $this->cajaId,
                    'venta_id'        => $venta->id,
                    'es_echeq'        => (bool) ($ch['es_echeq'] ?? false),
                    'fecha_recepcion' => $this->fecha,
                    'fecha_pago'      => ($ch['fecha_pago'] ?? '') ?: $this->fecha,
                    'librador_nombre' => $ch['librador_nombre'] ?? null,
                    'librador_cuit'   => ($ch['librador_cuit'] ?? '') ?: null,
                    'observacion'     => "Venta #{$nroVenta}",
                    'referencia_type' => Venta::class,
                    'referencia_id'   => $venta->id,
                ]);
            }
        }

        // Libro de caja: un asiento por cada forma de pago usada, con su importe. Un único
        // asiento por el total imputado a la primera forma de pago rompía el arqueo en los
        // pagos mixtos (ej. efectivo + tarjeta contaba todo como efectivo). La suma sigue
        // siendo `total`, así que `cajas.total_ventas` no cambia.
        if ($this->cajaId && ($caja = Caja::find($this->cajaId))) {
            $cajaService = app(CajaService::class);
            $opts = [
                'referencia_type' => Venta::class,
                'referencia_id'   => $venta->id,
                'descripcion'     => "Venta #{$nroVenta}",
                'fecha'           => $this->fecha,
            ];

            foreach ($pagos as $pago) {
                if ($pago['forma_pago'] === 'cuenta_corriente' || (float) $pago['monto'] <= 0) {
                    continue;
                }
                $cajaService->registrarMovimiento($caja, 'ingreso', 'venta', (float) $pago['monto'],
                    [...$opts, 'forma_pago' => $pago['forma_pago']]);
            }

            // Lo que quedó a crédito entra al libro con forma cuenta_corriente: suma a
            // total_ventas pero no cuenta como efectivo en el arqueo.
            if ($saldo > 0.001) {
                $cajaService->registrarMovimiento($caja, 'ingreso', 'venta', $saldo,
                    [...$opts, 'forma_pago' => 'cuenta_corriente']);
            }
        }

        if ($hasCC && $saldo > 0 && $this->cliente_id) {
            $cliente = Cliente::find($this->cliente_id);
            if ($cliente) {
                $saldoAnterior = (float) $cliente->saldo;
                $nuevoSaldo    = round($saldoAnterior + $saldo, 2);
                $cliente->update(['saldo' => $nuevoSaldo]);

                CcCliente::create([
                    'cliente_id'     => $this->cliente_id,
                    'fecha'          => $this->fecha,
                    'concepto'       => "Venta #{$nroVenta}",
                    'debe'           => $saldo,
                    'haber'          => 0,
                    'saldo'          => $nuevoSaldo,
                    'referencia_type'=> Venta::class,
                    'referencia_id'  => $venta->id,
                ]);
            }
        }

        // Marcar presupuesto origen como Facturado
        if ($this->presupuestoOrigenId) {
            Presupuesto::where('id', $this->presupuestoOrigenId)
                ->whereIn('estado', ['A', 'V'])
                ->update(['estado' => 'F', 'venta_id' => $venta->id]);
        }

        session()->flash('success', "Venta #{$nroVenta} registrada correctamente.");

        // Si auto-impresión está activa, abrimos la página de impresión (fuera de
        // la navegación SPA para que window.print() funcione con el DOM completo).
        $autoImprimir = (bool) VariableEntorno::get('impresion_auto_venta', false);
        if ($autoImprimir) {
            $this->redirect(route('ventas.imprimir', $venta->id), navigate: false);
        } else {
            $this->redirect(route('ventas'), navigate: true);
        }
    }

    public function registrarVentaDesdePayload(): void
    {
        $payload = json_decode($this->payloadJson, true);

        if (! is_array($payload)) {
            $this->addError('payloadJson', 'No se pudieron procesar los datos de la venta.');
            return;
        }

        $this->registrarVenta($payload);
    }

    public function registrarVentaDesdeJson(string $payloadJson): void
    {
        $payload = json_decode($payloadJson, true);

        if (! is_array($payload)) {
            $this->addError('payloadJson', 'No se pudieron procesar los datos de la venta.');
            return;
        }

        $this->registrarVenta($payload);
    }

    /**
     * Suma las cantidades de todos los ítems que comparten rubro e unidad de medida.
     * Se usa para determinar el tramo correcto cuando hay múltiples artículos del mismo
     * rubro+presentación en el carrito.
     */
    private function calcularCantidadGrupo(int $rubroId, string $unidad): float
    {
        $total = 0.0;
        foreach ($this->items as $item) {
            if ((int) ($item['rubro_id'] ?? 0) === $rubroId && ($item['unidad_medida'] ?? '') === $unidad) {
                $total += (float) ($item['cantidad'] ?? 0);
            }
        }
        return $total;
    }

    /**
     * Re-cotiza todos los ítems del grupo (rubro+unidad) que no tengan precio manual.
     * Llama a cotizarItem() que internamente calcula cantidadTramo con calcularCantidadGrupo().
     */
    private function recotizarGrupo(int $rubroId, string $unidad): void
    {
        foreach ($this->items as $i => $item) {
            if ((int) ($item['rubro_id'] ?? 0) === $rubroId && ($item['unidad_medida'] ?? '') === $unidad) {
                if (empty($item['precio_manual'])) {
                    $this->cotizarItem($i);
                }
            }
        }
    }

    public function render()
    {
        // Datos estaticos del catalogo. El precio NO se serializa al frontend:
        // cada cambio de articulo o cantidad pide cotizacion al motor (PriceEngine)
        // via cotizarItem(). Esto centraliza el calculo en backend y permite
        // aplicar lista del cliente, overrides por rubro, precio especial, etc.
        $articulosData = Articulo::where('estado', 'A')
            ->orderBy('descripcion')
            ->get()
            ->map(fn ($a) => [
                'id'             => $a->id,
                'codigo'         => $a->codigo,
                'descripcion'    => $a->descripcion,
                'unidad_medida'  => $a->unidad_medida,
                'alicuota_iva'   => (float) $a->alicuota_iva,
            ])
            ->values()
            ->toArray();

        $listasPrecios = ListaPrecio::orderBy('orden')->get();
        $listaActual   = $this->listaIdForzada
            ? $listasPrecios->firstWhere('id', $this->listaIdForzada)
            : null;

        return view('livewire.ventas.form', [
            'articulosData'     => $articulosData,
            'clientes'          => Cliente::where('estado', 'A')->orderBy('razon_social')->get(),
            'items'             => $this->items,
            'clienteData'       => $this->clienteData,
            'descuento_general' => $this->descuento_general,
            'listasPrecios'     => $listasPrecios,
            'listaActual'       => $listaActual,
            'bancos'            => Banco::where('activo', true)->orderBy('descripcion')->get(),
        ]);
    }
}
