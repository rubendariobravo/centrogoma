<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Un ajuste masivo de precios aplicado a una lista (ver App\Services\Pricing\AjustePrecios). */
class AjustePrecio extends Model
{
    protected $table = 'ajustes_precios';

    protected $fillable = [
        'lista_precio_id', 'user_id', 'modo', 'tipo', 'porcentaje', 'redondeo',
        'filtros', 'descripcion', 'cantidad', 'revertido_at', 'revertido_por',
    ];

    protected $casts = [
        'filtros'      => 'array',
        'porcentaje'   => 'float',
        'redondeo'     => 'float',
        'revertido_at' => 'datetime',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(AjustePrecioItem::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function lista(): BelongsTo
    {
        return $this->belongsTo(ListaPrecio::class, 'lista_precio_id');
    }
}
