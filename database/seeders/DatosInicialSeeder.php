<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\TipoIva;
use App\Models\DocAfip;
use App\Models\VariableEntorno;
use App\Models\Cliente;

class DatosInicialSeeder extends Seeder
{
    public function run(): void
    {
        // Tipos IVA (códigos AFIP)
        $tiposIva = [
            ['descripcion' => 'Responsable Inscripto',  'codigo_afip' => '01'],
            ['descripcion' => 'Responsable No Inscripto','codigo_afip' => '02'],
            ['descripcion' => 'No Responsable',         'codigo_afip' => '03'],
            ['descripcion' => 'Sujeto Exento',          'codigo_afip' => '04'],
            ['descripcion' => 'Consumidor Final',        'codigo_afip' => '05'],
            ['descripcion' => 'Responsable Monotributo','codigo_afip' => '06'],
            ['descripcion' => 'Sujeto No Categorizado', 'codigo_afip' => '07'],
        ];
        foreach ($tiposIva as $tipo) {
            TipoIva::firstOrCreate(['codigo_afip' => $tipo['codigo_afip']], $tipo);
        }

        // Documentos AFIP
        $docs = [
            ['descripcion' => 'Factura A',      'tipo_cbte' => 1,  'letra' => 'A'],
            ['descripcion' => 'Factura B',      'tipo_cbte' => 6,  'letra' => 'B'],
            ['descripcion' => 'Factura C',      'tipo_cbte' => 11, 'letra' => 'C'],
            ['descripcion' => 'Nota Débito A',  'tipo_cbte' => 2,  'letra' => 'A'],
            ['descripcion' => 'Nota Débito B',  'tipo_cbte' => 7,  'letra' => 'B'],
            ['descripcion' => 'Nota Débito C',  'tipo_cbte' => 12, 'letra' => 'C'],
            ['descripcion' => 'Nota Crédito A', 'tipo_cbte' => 3,  'letra' => 'A'],
            ['descripcion' => 'Nota Crédito B', 'tipo_cbte' => 8,  'letra' => 'B'],
            ['descripcion' => 'Nota Crédito C', 'tipo_cbte' => 13, 'letra' => 'C'],
        ];
        foreach ($docs as $doc) {
            DocAfip::firstOrCreate(['tipo_cbte' => $doc['tipo_cbte'], 'letra' => $doc['letra']], $doc);
        }

        // Variables de entorno del sistema
        $variables = [
            ['clave' => 'pto_vta',              'valor' => '1',    'tipo' => 'int',   'descripcion' => 'Punto de venta AFIP'],
            ['clave' => 'razon_social',          'valor' => '',     'tipo' => 'string','descripcion' => 'Razón social del emisor'],
            ['clave' => 'cuit_emisor',           'valor' => '',     'tipo' => 'string','descripcion' => 'CUIT del emisor'],
            ['clave' => 'domicilio_fiscal',      'valor' => '',     'tipo' => 'string','descripcion' => 'Domicilio fiscal'],
            ['clave' => 'inicio_actividades',    'valor' => '',     'tipo' => 'string','descripcion' => 'Fecha inicio actividades'],
            ['clave' => 'afip_ambiente',         'valor' => 'homo', 'tipo' => 'string','descripcion' => 'Ambiente AFIP: homo o prod'],
            ['clave' => 'umbral_percepcion_iva', 'valor' => '3000', 'tipo' => 'float', 'descripcion' => 'Monto mínimo para percepción IVA'],
            ['clave' => 'alicuota_percepcion_iva_21',  'valor' => '3',   'tipo' => 'float','descripcion' => '% percepción sobre neto 21%'],
            ['clave' => 'alicuota_percepcion_iva_10_5','valor' => '1.5', 'tipo' => 'float','descripcion' => '% percepción sobre neto 10.5%'],
            ['clave' => 'moneda_base',           'valor' => 'ARS',  'tipo' => 'string','descripcion' => 'Moneda base del sistema'],
        ];
        foreach ($variables as $var) {
            VariableEntorno::firstOrCreate(['clave' => $var['clave']], $var);
        }

        // Cliente "Consumidor final" por defecto (id usado cuando no se selecciona cliente)
        // No le asignamos lista_precio_id explicitamente: null = usa la lista default del sistema.
        $tivaCF = TipoIva::where('codigo_afip', '05')->first();
        Cliente::firstOrCreate(
            ['razon_social' => 'Consumidor final'],
            [
                'tipoiva_id'      => $tivaCF?->id,
                'estado'          => 'A',
                'lista_precio_id' => null,
                'bonificacion'    => 0,
                'limite_credito'  => 0,
                'saldo'           => 0,
                'porcentaje_iibb' => 0,
                'percepcion_iva'  => false,
                'es_mayorista'    => false,
            ]
        );

        $this->command->info('✓ Datos iniciales cargados (tipos IVA, docs AFIP, variables, consumidor final).');
    }
}
