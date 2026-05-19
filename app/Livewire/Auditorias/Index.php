<?php

namespace App\Livewire\Auditorias;

use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use OwenIt\Auditing\Models\Audit;
use App\Models\User;

#[Title('Auditorías')]
class Index extends Component
{
    use WithPagination;

    public string $search      = '';
    public string $filterEvento = '';
    public string $filterModulo = '';
    public string $filterUser   = '';
    public string $filterDesde  = '';
    public string $filterHasta  = '';

    public ?int   $auditId     = null;
    public ?array $auditDetalle = null;

    protected array $modelMap = [
        'App\\Models\\Venta'      => ['label' => 'Venta',          'modulo' => 'ventas'],
        'App\\Models\\Compra'     => ['label' => 'Compra',         'modulo' => 'compras'],
        'App\\Models\\Cliente'    => ['label' => 'Cliente',        'modulo' => 'ventas'],
        'App\\Models\\Proveedor'  => ['label' => 'Proveedor',      'modulo' => 'compras'],
        'App\\Models\\Articulo'   => ['label' => 'Artículo',       'modulo' => 'articulos'],
        'App\\Models\\Caja'       => ['label' => 'Caja',           'modulo' => 'cajas'],
        'App\\Models\\Recibo'     => ['label' => 'Recibo',         'modulo' => 'ventas'],
        'App\\Models\\OrdenPago'  => ['label' => 'Orden de Pago',  'modulo' => 'compras'],
        'App\\Models\\Produccion' => ['label' => 'Producción',     'modulo' => 'produccion'],
        'App\\Models\\Descarte'   => ['label' => 'Descarte',       'modulo' => 'produccion'],
        'App\\Models\\Pesada'     => ['label' => 'Pesada',         'modulo' => 'produccion'],
        'App\\Models\\Remito'     => ['label' => 'Remito',         'modulo' => 'produccion'],
        'App\\Models\\Rubro'      => ['label' => 'Rubro',          'modulo' => 'articulos'],
        'App\\Models\\Marca'      => ['label' => 'Marca',          'modulo' => 'articulos'],
        'App\\Models\\Empleado'   => ['label' => 'Empleado',       'modulo' => 'produccion'],
        'App\\Models\\User'       => ['label' => 'Usuario',        'modulo' => 'admin'],
    ];

    protected array $moduloLabels = [
        'ventas'     => 'Ventas',
        'compras'    => 'Compras',
        'articulos'  => 'Artículos',
        'cajas'      => 'Caja y Finanzas',
        'produccion' => 'Producción',
        'admin'      => 'Administración',
    ];

    protected array $fieldLabels = [
        'razon_social'      => 'Razón Social',
        'nombre_fantasia'   => 'Nombre Fantasía',
        'cuit'              => 'CUIT',
        'dni'               => 'DNI',
        'tipoiva_id'        => 'Tipo IVA',
        'empleado_id'       => 'Empleado',
        'telefono'          => 'Teléfono',
        'celular'           => 'Celular',
        'email'             => 'Email',
        'domicilio'         => 'Domicilio',
        'localidad'         => 'Localidad',
        'provincia'         => 'Provincia',
        'codigo_postal'     => 'Código Postal',
        'lista_precio_id'   => 'Lista de Precio',
        'bonificacion'      => 'Bonificación',
        'limite_credito'    => 'Límite Crédito',
        'porcentaje_iibb'   => '% IIBB',
        'percepcion_iva'    => 'Percepción IVA',
        'es_mayorista'      => '¿Es Mayorista?',
        'estado'            => 'Estado',
        'observacion'       => 'Observación',
        'cliente_id'        => 'Cliente',
        'proveedor_id'      => 'Proveedor',
        'user_id'           => 'Usuario',
        'caja_id'           => 'Caja',
        'doc_afip_id'       => 'Tipo Comprobante',
        'nro_venta'         => 'Nro. Venta',
        'fecha'             => 'Fecha',
        'neto'              => 'Neto',
        'total'             => 'Total',
        'descuento'         => 'Descuento',
        'nro_factura'       => 'Nro. Factura',
        'letra_comprobante' => 'Letra',
        'tipo_cbte'         => 'Tipo Cbte.',
        'pto_vta'           => 'Pto. Venta',
        'cae'               => 'CAE',
        'vto_cae'           => 'Vto. CAE',
        'tipo'              => 'Tipo',
        'saldo_inicial'     => 'Saldo Inicial',
        'saldo_final'       => 'Saldo Final',
        'hora_apertura'     => 'Hora Apertura',
        'hora_cierre'       => 'Hora Cierre',
        'descripcion'       => 'Descripción',
        'codigo'            => 'Código',
        'marca_id'          => 'Marca',
        'rubro_id'          => 'Rubro',
        'tipo_venta'        => 'Tipo Venta',
        'unidad_medida'     => 'Unidad Medida',
        'precio_unitario'   => 'Precio Unitario',
        'stock_minimo'      => 'Stock Mínimo',
        'alicuota_iva'      => 'Alícuota IVA',
        'margen'            => 'Margen',
        'cantidad_entrada'  => 'Cantidad Entrada',
        'cantidad_salida'   => 'Cantidad Salida',
        'merma'             => 'Merma',
        'merma_porcentaje'  => 'Merma %',
        'stock_sistema'     => 'Stock Sistema',
        'stock_real'        => 'Stock Real',
        'diferencia'        => 'Diferencia',
        'motivo'            => 'Motivo',
        'cantidad'          => 'Cantidad',
        'nombre'            => 'Nombre',
        'apellido'          => 'Apellido',
        'rol'               => 'Rol',
        'comision'          => 'Comisión',
        'nro_recibo'        => 'Nro. Recibo',
        'nro_orden'         => 'Nro. Orden',
        'forma_pago'        => 'Forma de Pago',
        'referencia'        => 'Referencia',
        'nro_remito'        => 'Nro. Remito',
        'total_peso'        => 'Total Peso',
        'total_cajas'       => 'Total Cajas',
        'name'              => 'Nombre',
        'contacto'          => 'Contacto',
    ];

    public function updatingSearch():      void { $this->resetPage(); }
    public function updatingFilterEvento(): void { $this->resetPage(); }
    public function updatingFilterModulo(): void { $this->resetPage(); }
    public function updatingFilterUser():   void { $this->resetPage(); }
    public function updatingFilterDesde():  void { $this->resetPage(); }
    public function updatingFilterHasta():  void { $this->resetPage(); }

    public function clearFilters(): void
    {
        $this->search       = '';
        $this->filterEvento = '';
        $this->filterModulo = '';
        $this->filterUser   = '';
        $this->filterDesde  = '';
        $this->filterHasta  = '';
        $this->resetPage();
    }

    public function verDetalle(int $id): void
    {
        $audit = Audit::with('user')->findOrFail($id);

        $modelInfo = $this->modelMap[$audit->auditable_type] ?? ['label' => class_basename($audit->auditable_type), 'modulo' => ''];

        $this->auditDetalle = [
            'id'          => $audit->id,
            'evento'      => $audit->event,
            'modelo'      => $modelInfo['label'],
            'modulo'      => $this->moduloLabels[$modelInfo['modulo']] ?? $modelInfo['modulo'],
            'entidad_id'  => $audit->auditable_id,
            'usuario'     => $audit->user?->name ?? 'Sistema',
            'ip'          => $audit->ip_address ?? '—',
            'url'         => $audit->url ?? '—',
            'fecha'       => $audit->created_at->format('d/m/Y H:i:s'),
            'old_values'  => $this->formatValues($audit->old_values ?? []),
            'new_values'  => $this->formatValues($audit->new_values ?? []),
            'all_keys'    => array_unique(array_merge(
                array_keys($audit->old_values ?? []),
                array_keys($audit->new_values ?? [])
            )),
        ];
    }

    public function cerrarDetalle(): void
    {
        $this->auditDetalle = null;
        $this->auditId      = null;
    }

    protected function formatValues(array $values): array
    {
        $result = [];
        foreach ($values as $key => $value) {
            $label = $this->fieldLabels[$key] ?? $key;
            if (is_bool($value)) {
                $value = $value ? 'Sí' : 'No';
            } elseif (is_null($value)) {
                $value = '—';
            }
            $result[$key] = ['label' => $label, 'value' => $value];
        }
        return $result;
    }

    protected function getModuloModels(string $modulo): array
    {
        return collect($this->modelMap)
            ->filter(fn($m) => $m['modulo'] === $modulo)
            ->keys()
            ->toArray();
    }

    public function render()
    {
        $query = Audit::with('user')
            ->when($this->filterEvento, fn($q) => $q->where('event', $this->filterEvento))
            ->when($this->filterUser,   fn($q) => $q->where('user_id', $this->filterUser))
            ->when($this->filterModulo, fn($q) => $q->whereIn('auditable_type', $this->getModuloModels($this->filterModulo)))
            ->when($this->filterDesde,  fn($q) => $q->whereDate('created_at', '>=', $this->filterDesde))
            ->when($this->filterHasta,  fn($q) => $q->whereDate('created_at', '<=', $this->filterHasta))
            ->when($this->search, function ($q) {
                $term = $this->search;
                $q->where(function ($q2) use ($term) {
                    $q2->where('old_values', 'like', "%{$term}%")
                       ->orWhere('new_values', 'like', "%{$term}%");
                });
            })
            ->orderByDesc('created_at');

        return view('livewire.auditorias.index', [
            'audits'       => $query->paginate(25),
            'users'        => User::orderBy('name')->get(),
            'modelMap'     => $this->modelMap,
            'moduloLabels' => $this->moduloLabels,
        ]);
    }
}
