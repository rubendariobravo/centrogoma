<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use OwenIt\Auditing\Contracts\Auditable;

class Banco extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable;

    protected $table = 'bancos';

    protected $fillable = [
        'descripcion', 'cuit', 'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    public function cheques(): HasMany
    {
        return $this->hasMany(Cheque::class);
    }
}
