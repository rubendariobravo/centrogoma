<?php

namespace App\Livewire\Clientes;

use App\Models\Articulo;
use App\Models\Cliente;
use App\Models\ClientePrecio;
use App\Models\Empleado;
use App\Models\ListaPrecio;
use App\Models\Rubro;
use App\Models\TipoIva;
use App\Support\Pricing;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Cliente')]
class Form extends Component
{
    public ?int $clienteId = null;

    // Identificación
    public string  $razon_social     = '';
    public string  $nombre_fantasia  = '';
    public string  $cuit             = '';
    public string  $dni              = '';
    public ?int    $tipoiva_id       = null;

    // Contacto
    public string $telefono     = '';
    public string $celular      = '';
    public string $email        = '';

    // Domicilio
    public string $domicilio     = '';
    public string $localidad     = '';
    public string $provincia     = '';
    public string $codigo_postal = '';

    // Comercial
    public ?int   $empleado_id     = null;
    public ?int   $lista_precio_id = null; // null = lista default del sistema
    public float  $bonificacion    = 0;
    public float  $limite_credito = 0;
    public float  $porcentaje_iibb = 0;
    public bool   $percepcion_iva  = false;
    public bool   $es_mayorista    = false;

    // Estado
    public string $estado      = 'A';
    public string $observacion = '';

    /**
     * Overrides de precio por cliente (tabla cliente_precios).
     * Cada item del array tiene la forma:
     *   [
     *     'id'              => ?int,                     // null = nuevo
     *     'tipo'            => 'precio_articulo' | 'lista_rubro' | 'bonif_articulo' | 'bonif_rubro',
     *     'articulo_id'     => ?int,                     // segun tipo
     *     'rubro_id'        => ?int,                     // segun tipo
     *     'lista_precio_id' => ?int,                     // solo para lista_rubro
     *     'precio_especial' => ?float,                   // solo para precio_articulo
     *     'bonificacion'    => float,                    // bonif_articulo / bonif_rubro
     *     'activo'          => bool,
     *   ]
     */
    public array $overrides = [];

    protected function rules(): array
    {
        return [
            'razon_social'    => 'required|string|max:200',
            'nombre_fantasia' => 'nullable|string|max:200',
            'cuit'            => 'nullable|string|max:20',
            'dni'             => 'nullable|string|max:15',
            'tipoiva_id'      => 'required|exists:tipoiva,id',
            'telefono'        => 'nullable|string|max:30',
            'celular'         => 'nullable|string|max:30',
            'email'           => 'nullable|email|max:100',
            'domicilio'       => 'nullable|string|max:200',
            'localidad'       => 'nullable|string|max:100',
            'provincia'       => 'nullable|string|max:100',
            'codigo_postal'   => 'nullable|string|max:10',
            'empleado_id'     => 'nullable|exists:empleados,id',
            'lista_precio_id' => 'nullable|exists:listas_precios,id',
            'bonificacion'    => 'required|numeric|min:0|max:100',
            'limite_credito'  => 'required|numeric|min:0',
            'porcentaje_iibb' => 'required|numeric|min:0|max:100',
            'percepcion_iva'  => 'boolean',
            'es_mayorista'    => 'boolean',
            'estado'          => 'required|in:A,I',
            'observacion'     => 'nullable|string|max:500',
        ];
    }

    protected function messages(): array
    {
        return [
            'razon_social.required' => 'La razón social es obligatoria.',
            'tipoiva_id.required'   => 'El tipo de IVA es obligatorio.',
            'email.email'           => 'El correo electrónico no es válido.',
        ];
    }

    public function mount(?int $cliente = null): void
    {
        if ($cliente) {
            $c = Cliente::findOrFail($cliente);
            $this->clienteId      = $c->id;
            $this->razon_social   = $c->razon_social;
            $this->nombre_fantasia = $c->nombre_fantasia ?? '';
            $this->cuit           = $c->cuit ?? '';
            $this->dni            = $c->dni ?? '';
            $this->tipoiva_id     = $c->tipoiva_id;
            $this->telefono       = $c->telefono ?? '';
            $this->celular        = $c->celular ?? '';
            $this->email          = $c->email ?? '';
            $this->domicilio      = $c->domicilio ?? '';
            $this->localidad      = $c->localidad ?? '';
            $this->provincia      = $c->provincia ?? '';
            $this->codigo_postal  = $c->codigo_postal ?? '';
            $this->empleado_id     = $c->empleado_id;
            $this->lista_precio_id = $c->lista_precio_id;
            $this->bonificacion    = (float) $c->bonificacion;
            $this->limite_credito = (float) $c->limite_credito;
            $this->porcentaje_iibb = (float) ($c->porcentaje_iibb * 100);
            $this->percepcion_iva = (bool) $c->percepcion_iva;
            $this->es_mayorista   = (bool) $c->es_mayorista;
            $this->estado         = $c->estado;
            $this->observacion    = $c->observacion ?? '';

            // Cargamos overrides existentes. El precio_especial en DB esta
            // en neto; lo mostramos al usuario como final (con IVA) usando
            // la alicuota del articulo asociado.
            $this->overrides = $c->precios()->with('articulo')->get()->map(function (ClientePrecio $cp) {
                $tipo = match (true) {
                    $cp->articulo_id && $cp->precio_especial !== null => 'precio_articulo',
                    $cp->rubro_id && $cp->lista_precio_id           => 'lista_rubro',
                    $cp->articulo_id                                  => 'bonif_articulo',
                    $cp->rubro_id                                     => 'bonif_rubro',
                    default                                           => 'precio_articulo',
                };

                $precioFinal = null;
                if ($cp->precio_especial !== null) {
                    $ivaArt = $cp->articulo ? (float) $cp->articulo->alicuota_iva : 21.0;
                    $precioFinal = Pricing::final((float) $cp->precio_especial, $ivaArt);
                }

                return [
                    'id'              => $cp->id,
                    'tipo'            => $tipo,
                    'articulo_id'     => $cp->articulo_id,
                    'rubro_id'        => $cp->rubro_id,
                    'lista_precio_id' => $cp->lista_precio_id,
                    'precio_especial' => $precioFinal, // FINAL con IVA en el form
                    'bonificacion'    => (float) $cp->bonificacion,
                    'activo'          => (bool) $cp->activo,
                ];
            })->toArray();
        }
    }

    public function addOverride(): void
    {
        $this->overrides[] = [
            'id'              => null,
            'tipo'            => 'precio_articulo',
            'articulo_id'     => null,
            'rubro_id'        => null,
            'lista_precio_id' => null,
            'precio_especial' => null,
            'bonificacion'    => 0,
            'activo'          => true,
        ];
    }

    public function removeOverride(int $index): void
    {
        if (isset($this->overrides[$index])) {
            array_splice($this->overrides, $index, 1);
            $this->overrides = array_values($this->overrides);
        }
    }

    public function save(): void
    {
        $this->validate();

        $data = [
            'razon_social'    => $this->razon_social,
            'nombre_fantasia' => $this->nombre_fantasia ?: null,
            'cuit'            => $this->cuit ?: null,
            'dni'             => $this->dni ?: null,
            'tipoiva_id'      => $this->tipoiva_id,
            'telefono'        => $this->telefono ?: null,
            'celular'         => $this->celular ?: null,
            'email'           => $this->email ?: null,
            'domicilio'       => $this->domicilio ?: null,
            'localidad'       => $this->localidad ?: null,
            'provincia'       => $this->provincia ?: null,
            'codigo_postal'   => $this->codigo_postal ?: null,
            'empleado_id'     => $this->empleado_id ?: null,
            'lista_precio_id' => $this->lista_precio_id ?: null,
            'bonificacion'    => $this->bonificacion,
            'limite_credito'  => $this->limite_credito,
            'porcentaje_iibb' => $this->porcentaje_iibb / 100,
            'percepcion_iva'  => $this->percepcion_iva,
            'es_mayorista'    => $this->es_mayorista,
            'estado'          => $this->estado,
            'observacion'     => $this->observacion ?: null,
        ];

        if ($this->clienteId) {
            $cliente = Cliente::findOrFail($this->clienteId);
            $cliente->update($data);
            session()->flash('success', 'Cliente actualizado.');
        } else {
            $cliente = Cliente::create($data);
            session()->flash('success', 'Cliente creado.');
        }

        $this->sincronizarOverrides($cliente);

        $this->redirect(route('clientes'), navigate: true);
    }

    /**
     * Sincroniza la tabla cliente_precios con el array $overrides del form.
     * Estrategia: borra los que no estan, actualiza los que tienen id, crea
     * los nuevos. Los overrides incompletos (sin articulo/rubro o sin valor)
     * se descartan silenciosamente.
     */
    private function sincronizarOverrides(Cliente $cliente): void
    {
        $idsAMantener = [];

        foreach ($this->overrides as $ov) {
            $payload = $this->normalizarOverride($ov);
            if ($payload === null) continue; // descartar incompletos

            if (! empty($ov['id'])) {
                $cp = ClientePrecio::where('id', $ov['id'])
                    ->where('cliente_id', $cliente->id)
                    ->first();
                if ($cp) {
                    $cp->update($payload);
                    $idsAMantener[] = $cp->id;
                    continue;
                }
            }
            $cp = $cliente->precios()->create($payload);
            $idsAMantener[] = $cp->id;
        }

        // Borramos los que ya no estan
        $cliente->precios()
            ->whereNotIn('id', $idsAMantener)
            ->delete();
    }

    /**
     * Convierte una fila del form en un payload valido para cliente_precios,
     * o null si el override esta incompleto.
     */
    private function normalizarOverride(array $ov): ?array
    {
        $tipo   = $ov['tipo'] ?? null;
        $activo = ! empty($ov['activo']);

        return match ($tipo) {
            'precio_articulo' => (! empty($ov['articulo_id']) && $ov['precio_especial'] !== null && $ov['precio_especial'] !== '')
                ? (function () use ($ov, $activo) {
                    // El form maneja final con IVA; en DB persistimos neto.
                    $art = Articulo::find($ov['articulo_id']);
                    $iva = $art ? (float) $art->alicuota_iva : 21.0;
                    $precioNeto = Pricing::neto((float) $ov['precio_especial'], $iva);
                    return ['articulo_id' => (int) $ov['articulo_id'], 'rubro_id' => null, 'lista_precio_id' => null,
                            'precio_especial' => $precioNeto, 'bonificacion' => 0, 'activo' => $activo];
                })()
                : null,
            'bonif_articulo' => (! empty($ov['articulo_id']) && (float) $ov['bonificacion'] > 0)
                ? ['articulo_id' => (int) $ov['articulo_id'], 'rubro_id' => null, 'lista_precio_id' => null,
                   'precio_especial' => null, 'bonificacion' => (float) $ov['bonificacion'], 'activo' => $activo]
                : null,
            'lista_rubro' => (! empty($ov['rubro_id']) && ! empty($ov['lista_precio_id']))
                ? ['articulo_id' => null, 'rubro_id' => (int) $ov['rubro_id'], 'lista_precio_id' => (int) $ov['lista_precio_id'],
                   'precio_especial' => null, 'bonificacion' => 0, 'activo' => $activo]
                : null,
            'bonif_rubro' => (! empty($ov['rubro_id']) && (float) $ov['bonificacion'] > 0)
                ? ['articulo_id' => null, 'rubro_id' => (int) $ov['rubro_id'], 'lista_precio_id' => null,
                   'precio_especial' => null, 'bonificacion' => (float) $ov['bonificacion'], 'activo' => $activo]
                : null,
            default => null,
        };
    }

    public function render()
    {
        return view('livewire.clientes.form', [
            'tiposIva'   => TipoIva::where('estado', 'A')->orderBy('descripcion')->get(),
            'vendedores' => Empleado::where('estado', 'A')
                ->whereIn('rol', ['vendedor', 'administrativo'])
                ->orderBy('apellido')->orderBy('nombre')
                ->get(),
            'listasPrecio' => ListaPrecio::where('activo', true)->orderBy('orden')->orderBy('nombre')->get(),
            'articulos'    => Articulo::where('estado', 'A')->orderBy('descripcion')->get(['id', 'codigo', 'descripcion']),
            'rubros'       => Rubro::orderBy('descripcion')->get(['id', 'descripcion']),
        ]);
    }
}
