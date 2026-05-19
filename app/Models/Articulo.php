<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;

class Articulo extends Model implements Auditable
{
    use SoftDeletes, \OwenIt\Auditing\Auditable;

    protected array $auditExclude = ['stock', 'stock_minorista', 'precio_costo', 'fecha_modif'];

    protected $fillable = [
        'marca_id', 'rubro_id', 'codigo', 'descripcion', 'imagen_url',
        'tipo_venta', 'unidad_medida', 'presentacion', 'presentacion_caja',
        'cantidad_caja', 'precio_costo', 'precio_unitario', 'precio_unitario_caja',
        'stock', 'stock_minorista', 'stock_minimo', 'stock_inicial',
        'alicuota_iva', 'margen', 'bonificacion', 'perdida',
        'modifica_stock', 'art_mayorista', 'acepta_subrubro', 'estado', 'fecha_modif',
    ];

    protected $casts = [
        'cantidad_caja'       => 'decimal:2',
        'precio_costo'        => 'float',
        'precio_unitario'     => 'float',
        'precio_unitario_caja'=> 'float',
        'stock'               => 'float',
        'stock_minorista'     => 'float',
        'stock_minimo'        => 'float',
        'stock_inicial'       => 'float',
        'alicuota_iva'        => 'decimal:2',
        'margen'              => 'float',
        'bonificacion'        => 'float',
        'perdida'             => 'float',
        'modifica_stock'      => 'boolean',
        'art_mayorista'       => 'boolean',
        'acepta_subrubro'     => 'boolean',
        'fecha_modif'         => 'date',
    ];

    public function marca()
    {
        return $this->belongsTo(Marca::class);
    }

    public function rubro()
    {
        return $this->belongsTo(Rubro::class);
    }

    public function precios()
    {
        return $this->hasMany(ArticuloPrecio::class)->orderBy('orden')->orderBy('cantidad_desde');
    }

    public function preciosActivos()
    {
        return $this->hasMany(ArticuloPrecio::class)->where('activo', true)->orderBy('orden')->orderBy('cantidad_desde');
    }

    public function subArticulos()
    {
        return $this->hasMany(SubArticulo::class, 'articulo_padre_id');
    }

    public function productosDe()
    {
        return $this->hasMany(SubArticulo::class, 'articulo_hijo_id');
    }

    public function movimientos()
    {
        return $this->hasMany(MovimientoArticulo::class)->orderByDesc('fecha')->orderByDesc('id');
    }

    public function detVentas()
    {
        return $this->hasMany(DetVenta::class);
    }

    public function estaActivo(): bool
    {
        return $this->estado === 'A';
    }

    public function tieneStock(): bool
    {
        return $this->stock > 0;
    }

    public function getPrecioPorCantidad(float $cantidad, bool $esMinorista = false): float
    {
        $stock = $esMinorista ? $this->stock_minorista : $this->stock;

        $tier = $this->preciosActivos()
            ->where('cantidad_desde', '<=', $cantidad)
            ->where(function ($q) use ($cantidad) {
                $q->whereNull('cantidad_hasta')->orWhere('cantidad_hasta', '>=', $cantidad);
            })
            ->first();

        return $tier ? (float) $tier->precio : (float) $this->precio_unitario;
    }

    public function getPrecioConIva(float $cantidad = 1): float
    {
        $precio = $this->getPrecioPorCantidad($cantidad);
        return round($precio * (1 + ($this->alicuota_iva / 100)), 2);
    }
}
