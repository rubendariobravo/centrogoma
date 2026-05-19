<?php

namespace App\Livewire\Config;

use App\Models\VariableEntorno;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Title('Configuración')]
class Index extends Component
{
    use WithFileUploads;

    // Datos de la empresa
    public string $empresa_nombre       = '';
    public string $empresa_razon_social = '';
    public string $empresa_cuit         = '';
    public string $empresa_domicilio    = '';
    public string $empresa_localidad    = '';
    public string $empresa_telefono     = '';
    public string $empresa_email        = '';
    public string $empresa_logo_url     = '';
    public $logoFile                    = null;

    // Configuración de facturación
    public string $punto_venta          = '0001';
    public string $iibb_tipo            = 'multilateral'; // multilateral, convenio, local
    public string $iibb_numero          = '';

    // Configuración de stock
    public bool   $stock_negativo       = false;
    public bool   $ajuste_automatico    = true;

    // Configuración de ventas
    public int    $listas_precio        = 3;
    public bool   $pide_vendedor        = true;

    // Configuración de impresión
    public string $impresion_formato    = 'ticket';  // ticket | pdf
    public bool   $impresion_auto_venta = false;
    public string $impresora_nombre     = '';

    public bool $saved = false;

    public function mount(): void
    {
        $vars = VariableEntorno::all()->keyBy('clave');

        foreach ([
            'empresa_nombre', 'empresa_razon_social', 'empresa_cuit',
            'empresa_domicilio', 'empresa_localidad', 'empresa_telefono',
            'empresa_email', 'empresa_logo_url', 'punto_venta',
            'iibb_tipo', 'iibb_numero',
        ] as $key) {
            if (isset($vars[$key])) {
                $this->$key = (string) $vars[$key]->valor;
            }
        }

        $this->stock_negativo        = filter_var($vars['stock_negativo']?->valor ?? false, FILTER_VALIDATE_BOOLEAN);
        $this->ajuste_automatico     = filter_var($vars['ajuste_automatico']?->valor ?? true, FILTER_VALIDATE_BOOLEAN);
        $this->pide_vendedor         = filter_var($vars['pide_vendedor']?->valor ?? true, FILTER_VALIDATE_BOOLEAN);
        $this->listas_precio         = (int) ($vars['listas_precio']?->valor ?? 3);
        $this->impresion_formato     = (string) ($vars['impresion_formato']?->valor ?? 'ticket');
        $this->impresion_auto_venta  = filter_var($vars['impresion_auto_venta']?->valor ?? false, FILTER_VALIDATE_BOOLEAN);
        $this->impresora_nombre      = (string) ($vars['impresora_nombre']?->valor ?? '');
    }

    public function removeLogo(): void
    {
        if ($this->empresa_logo_url && str_starts_with($this->empresa_logo_url, '/storage/')) {
            $relative = ltrim(str_replace('/storage/', '', $this->empresa_logo_url), '/');
            if (Storage::disk('public')->exists($relative)) {
                Storage::disk('public')->delete($relative);
            }
        }
        $this->empresa_logo_url = '';
        $this->logoFile = null;
        VariableEntorno::set('empresa_logo_url', '');
    }

    public function save(): void
    {
        $this->validate([
            'empresa_nombre'       => 'required|string|max:255',
            'empresa_razon_social' => 'required|string|max:255',
            'empresa_cuit'         => 'required|string|max:20',
            'punto_venta'          => 'required|string|max:4',
            'listas_precio'        => 'required|integer|min:1|max:6',
            'logoFile'             => 'nullable|image|mimes:png,jpg,jpeg,gif,webp,svg|max:2048',
        ]);

        if ($this->logoFile) {
            // Eliminar archivo local anterior si lo hay
            if ($this->empresa_logo_url && str_starts_with($this->empresa_logo_url, '/storage/')) {
                $old = ltrim(str_replace('/storage/', '', $this->empresa_logo_url), '/');
                if (Storage::disk('public')->exists($old)) {
                    Storage::disk('public')->delete($old);
                }
            }
            $path = $this->logoFile->store('logos', 'public');
            $this->empresa_logo_url = '/storage/' . $path;
            $this->logoFile = null;
        }

        $values = [
            'empresa_nombre'       => $this->empresa_nombre,
            'empresa_razon_social' => $this->empresa_razon_social,
            'empresa_cuit'         => $this->empresa_cuit,
            'empresa_domicilio'    => $this->empresa_domicilio,
            'empresa_localidad'    => $this->empresa_localidad,
            'empresa_telefono'     => $this->empresa_telefono,
            'empresa_email'        => $this->empresa_email,
            'empresa_logo_url'     => $this->empresa_logo_url,
            'punto_venta'          => $this->punto_venta,
            'iibb_tipo'            => $this->iibb_tipo,
            'iibb_numero'          => $this->iibb_numero,
            'stock_negativo'        => $this->stock_negativo ? '1' : '0',
            'ajuste_automatico'     => $this->ajuste_automatico ? '1' : '0',
            'pide_vendedor'         => $this->pide_vendedor ? '1' : '0',
            'listas_precio'         => (string) $this->listas_precio,
            'impresion_formato'     => $this->impresion_formato,
            'impresion_auto_venta'  => $this->impresion_auto_venta ? '1' : '0',
            'impresora_nombre'      => $this->impresora_nombre,
        ];

        foreach ($values as $clave => $valor) {
            VariableEntorno::set($clave, $valor);
        }

        $this->saved = true;
    }

    public function render()
    {
        return view('livewire.config.index');
    }
}
