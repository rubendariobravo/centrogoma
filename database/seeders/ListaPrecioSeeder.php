<?php

namespace Database\Seeders;

use App\Models\ListaPrecio;
use Illuminate\Database\Seeder;

class ListaPrecioSeeder extends Seeder
{
    public function run(): void
    {
        // La lista "Minorista" ya fue creada en la migracion para que el
        // backfill de FK funcione. Aca solo nos aseguramos que exista
        // (idempotente) y agregamos las listas iniciales adicionales.
        $listas = [
            [
                'codigo'              => 'MINORISTA',
                'nombre'              => 'Minorista',
                'descripcion'         => 'Lista de precios minorista (consumidor final). Lista default del sistema.',
                'tipo'                => 'minorista',
                'es_default'          => true,
                'recargo_no_efectivo' => 0,
                'descuento_efectivo'  => 5.00,
                'orden'               => 1,
                'activo'              => true,
            ],
            [
                'codigo'              => 'MAYORISTA',
                'nombre'              => 'Mayorista Retiro Local',
                'descripcion'         => 'Lista mayorista para retiro en local (efectivo/transferencia).',
                'tipo'                => 'mayorista',
                'es_default'          => false,
                'recargo_no_efectivo' => 0,
                'descuento_efectivo'  => 0,
                'orden'               => 2,
                'activo'              => true,
            ],
            [
                'codigo'              => 'MAY-MEDIANO',
                'nombre'              => 'Mayorista Mediano',
                'descripcion'         => 'Lista mayorista para clientes medianos (precio intermedio).',
                'tipo'                => 'mayorista',
                'es_default'          => false,
                'recargo_no_efectivo' => 0,
                'descuento_efectivo'  => 0,
                'orden'               => 3,
                'activo'              => true,
            ],
            [
                'codigo'              => 'MAY-GRANDE',
                'nombre'              => 'Mayorista Grande',
                'descripcion'         => 'Lista mayorista para clientes grandes (mejor precio).',
                'tipo'                => 'mayorista',
                'es_default'          => false,
                'recargo_no_efectivo' => 0,
                'descuento_efectivo'  => 0,
                'orden'               => 4,
                'activo'              => true,
            ],
            [
                'codigo'              => 'REPARTO',
                'nombre'              => 'Reparto',
                'descripcion'         => 'Lista mayorista para reparto a domicilio (incluye costo de entrega).',
                'tipo'                => 'mayorista',
                'es_default'          => false,
                'recargo_no_efectivo' => 0,
                'descuento_efectivo'  => 0,
                'orden'               => 5,
                'activo'              => true,
            ],
        ];

        foreach ($listas as $data) {
            ListaPrecio::firstOrCreate(['codigo' => $data['codigo']], $data);
        }

        $this->command->info('✓ Listas de precios cargadas (Minorista, Mayorista Retiro Local, Mediano, Grande, Reparto).');
    }
}
