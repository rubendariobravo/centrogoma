<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CategoriaGasto extends Model
{
    protected $table = 'categorias_gasto';

    protected $fillable = ['nombre', 'cuenta_contable_id', 'activo'];

    protected $casts = ['activo' => 'boolean'];

    public function gastos(): HasMany
    {
        return $this->hasMany(Gasto::class);
    }

    public function scopeActivas(Builder $query): Builder
    {
        return $query->where('activo', true)->orderBy('nombre');
    }
}
