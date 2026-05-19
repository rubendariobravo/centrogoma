<?php

namespace Database\Seeders;

use App\Models\Cliente;
use App\Models\TipoIva;
use Illuminate\Database\Seeder;

/**
 * Importación inicial de clientes extraídos del Excel "Clientes avisur.xlsx".
 *
 * Todos los registros son Consumidor Final (código AFIP 99).
 * Los datos faltantes (CUIT, celular, etc.) deben completarse manualmente
 * desde el ABM de Clientes.
 *
 * Idempotente: usa firstOrCreate por razon_social para que se pueda
 * ejecutar más de una vez sin duplicar registros.
 */
class ClientesAvisurSeeder extends Seeder
{
    public function run(): void
    {
        // Código AFIP 05 = Consumidor Final (según tabla tipo_iva del sistema)
        $cf = TipoIva::where('codigo_afip', '05')->first();

        if (! $cf) {
            $this->command->error('TipoIva Consumidor Final (05) no encontrado. Ejecute DatosInicialSeeder primero.');
            return;
        }

        // [razon_social, domicilio|null, localidad|null, celular|null, observacion|null]
        $clientes = [
            // ─── Sin domicilio registrado ──────────────────────────────────────────
            ['Giussepe Restaurante (Jose)',         null,                                                                   null,              null,           null],
            ['El Viejo Sanzo Centro (Seba)',         null,                                                                   null,              null,           null],
            ['El Viejo Sanzo (Kevin)',               null,                                                                   null,              null,           null],
            ['Mati Molina',                          null,                                                                   null,              null,           null],
            ['Bodegon Los Descamisados',             null,                                                                   null,              null,           null],
            ['Barbara Zulli',                        null,                                                                   null,              null,           null],
            ['Yanina Olivera',                       null,                                                                   null,              null,           null],
            ['Lauti Cornaglia',                      null,                                                                   null,              null,           null],
            ['Tute',                                 null,                                                                   null,              null,           null],
            ['Luciano MDM',                          null,                                                                   null,              null,           null],
            ['Thomas Uriel',                         null,                                                                   null,              null,           null],
            ['Miguel Zulli',                         null,                                                                   null,              null,           null],
            ['Mauro Coronel',                        null,                                                                   null,              null,           null],

            // ─── Con domicilio registrado ──────────────────────────────────────────
            ['Carniceria Doña Delia (Ezequiel)',     'Fabian Onsari 783',                                                    'Wilde',           '1137041324',   'Avellaneda, Bs. As.'],
            ['Lucas Carniceria',                     'Bouchard 5103 (Esq. De Camino y Bouchard)',                            'Wilde',           null,           'Avellaneda, Bs. As.'],
            ['Karina (Mamá Mati)',                   'Chascomus 1799',                                                       'Wilde',           null,           'Avellaneda, Bs. As.'],
            ['Nazareno',                             'Genova esq. Cucha 2708 (A 2 cdras. cancha Arsenal)',                   'Sarandí',         null,           'Avellaneda, Bs. As.'],
            ['Analía',                               'Lamadrid 459 (Entre Drago y 26 de Julio)',                             'Bernal',          null,           'Quilmes, Bs. As.'],
            ['Solci Tesouro',                        'Emilio Zola 5839 (Portón negro)',                                      'Wilde',           null,           'Avellaneda, Bs. As.'],
            ['Mariana',                              'Combate Vuelta de Obligado 671 (Entre Ituzaingo y Sgto. Cabral)',       'Bernal',          null,           'Quilmes, Bs. As.'],
            ['Mirta',                                'Rodó y Rivadavia - Instituto de Rehabilitación (pegados a la plaza)',  'Wilde',           null,           'Avellaneda, Bs. As.'],
            ['Aldana',                               'Pasaje Homero 1656',                                                   'Dock Sud',        null,           'Avellaneda, Bs. As.'],
            ['Matias Cliente',                       'General Álvarez 1762 (Entre Neuquén y Bermejo)',                       'Bernal Oeste',    null,           'Bs. As.'],
            ['Daniel',                               'San Vicente 503 (Local - Entre Soreda y Bolívar)',                     'Wilde',           null,           'Avellaneda, Bs. As.'],
            ['Barbara Clienta',                      'Caxaraville 4151 (Entre Perdenera y Barceló)',                         'Villa Domínico',  null,           'Avellaneda, Bs. As.'],
            ['Martin Cliente',                       'Combatiente de Malvinas / C. Sereno 4180 (Granja Bastián)',            'Wilde',           null,           'Avellaneda, Bs. As.'],
            ['Alejandra Torre 15',                   'Las Flores - Torre 15 N°1600 (Entrar por calle Robles)',               'Wilde',           null,           'Avellaneda, Bs. As.'],
            ['Patricia Sarandí',                     'Brandsen 2976 (Entre Paunero y Ferré)',                                'Sarandí',         null,           'Avellaneda, Bs. As.'],
            ['Centro de Día Wilde',                  'Bahía Blanca 62 (Entre Mitre y Mariano Moreno)',                       'Wilde',           null,           'Avellaneda, Bs. As.'],
            ['Romina Maltese',                       'Las Flores 453',                                                       'Wilde',           null,           'Avellaneda, Bs. As.'],
            ['Pablo Paz',                            'Av. Mosconi y 12 de Octubre 1851 (Entre B.P. Galdós y Castelverde)',  'Quilmes',         null,           'Bs. As.'],
            ['Sol Minorista',                        'Hilario Ascasubi 1349 (Entre Casacuberta y Malvinas Argentinas)',      'Wilde',           null,           'Avellaneda, Bs. As.'],
        ];

        $creados   = 0;
        $existentes = 0;

        foreach ($clientes as [$razonSocial, $domicilio, $localidad, $celular, $observacion]) {
            [$modelo, $esNuevo] = [
                Cliente::firstOrCreate(
                    ['razon_social' => $razonSocial],
                    [
                        'tipoiva_id'  => $cf->id,
                        'domicilio'   => $domicilio,
                        'localidad'   => $localidad,
                        'provincia'   => 'Buenos Aires',
                        'celular'     => $celular,
                        'observacion' => $observacion,
                        'estado'      => 'A',
                        'saldo'       => 0,
                    ]
                ),
                null,
            ];

            $esNuevo = $modelo->wasRecentlyCreated;
            $esNuevo ? $creados++ : $existentes++;
        }

        $total = count($clientes);
        $this->command->info("✓ Clientes Avisur: {$total} registros procesados ({$creados} nuevos, {$existentes} ya existían).");
        $this->command->info('  Todos como Consumidor Final. Completar CUIT/DNI/datos adicionales desde el ABM.');
    }
}
