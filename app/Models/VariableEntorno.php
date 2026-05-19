<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VariableEntorno extends Model
{
    protected $table = 'variable_entorno';

    protected $fillable = ['clave', 'valor', 'descripcion', 'tipo'];

    public static function get(string $clave, mixed $default = null): mixed
    {
        $var = static::where('clave', $clave)->first();
        if (!$var) return $default;

        return match ($var->tipo) {
            'int'   => (int) $var->valor,
            'float' => (float) $var->valor,
            'bool'  => filter_var($var->valor, FILTER_VALIDATE_BOOLEAN),
            default => $var->valor,
        };
    }

    public static function set(string $clave, mixed $valor): void
    {
        static::updateOrCreate(
            ['clave' => $clave],
            ['valor' => (string) $valor]
        );
    }
}
