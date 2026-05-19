<?php

namespace Database\Seeders;

use App\Models\Articulo;
use App\Models\ArticuloPrecio;
use App\Models\ListaPrecio;
use App\Models\Rubro;
use Illuminate\Database\Seeder;

/**
 * Seeder con artículos reales del cliente extraídos del Excel
 * "LISTA DE PRECIOS SISTEMA NUEVOO.xlsx" (5 hojas → 5 listas).
 *
 * Convenciones:
 *  - Los precios del Excel son FINALES (con IVA incluido). El motor trabaja
 *    con netos y agrega IVA al cierre, así que el seeder divide cada precio
 *    por (1 + alícuota/100) antes de persistirlo.
 *  - Alícuotas: Pollo y Cerdo → 10.5% | Resto → 21%
 *  - tipo_venta=CAJA → cantidad = número de cajones/bolsas/unidades.
 *  - tipo_venta=PESO → cantidad = kilogramos.
 *  - Idempotente: borra tramos previos de las 5 listas antes de re-insertar.
 *
 * Listas:
 *   MINORISTA       ← hoja "Precios de Lista"
 *   MAYORISTA       ← hoja "Mayorista Retiro Local" (renombrado)
 *   MAY-MEDIANO     ← hoja "Mayorista Mediano"      (nueva)
 *   MAY-GRANDE      ← hoja "Mayorista Grande"        (nueva)
 *   REPARTO         ← hoja "Reparto"                 (nueva)
 *
 * Ejecutar:
 *   php artisan db:seed --class=PreciosFrangoExcelSeeder
 */
class PreciosFrangoExcelSeeder extends Seeder
{
    public function run(): void
    {
        // ── 1. Listas de precios ──────────────────────────────────────────────
        $minorista = ListaPrecio::firstOrCreate(
            ['codigo' => 'MINORISTA'],
            ['nombre' => 'Minorista', 'tipo' => 'minorista', 'es_default' => true,
             'descuento_efectivo' => 5.00, 'orden' => 1, 'activo' => true]
        );

        $mayorista = ListaPrecio::firstOrCreate(
            ['codigo' => 'MAYORISTA'],
            ['nombre' => 'Mayorista Retiro Local', 'tipo' => 'mayorista', 'es_default' => false,
             'recargo_no_efectivo' => 0, 'orden' => 2, 'activo' => true]
        );
        $mayorista->update(['nombre' => 'Mayorista Retiro Local']);

        $mayMediano = ListaPrecio::firstOrCreate(
            ['codigo' => 'MAY-MEDIANO'],
            ['nombre' => 'Mayorista Mediano', 'tipo' => 'mayorista', 'es_default' => false,
             'recargo_no_efectivo' => 0, 'orden' => 3, 'activo' => true]
        );

        $mayGrande = ListaPrecio::firstOrCreate(
            ['codigo' => 'MAY-GRANDE'],
            ['nombre' => 'Mayorista Grande', 'tipo' => 'mayorista', 'es_default' => false,
             'recargo_no_efectivo' => 0, 'orden' => 4, 'activo' => true]
        );

        $reparto = ListaPrecio::firstOrCreate(
            ['codigo' => 'REPARTO'],
            ['nombre' => 'Reparto', 'tipo' => 'mayorista', 'es_default' => false,
             'recargo_no_efectivo' => 0, 'orden' => 5, 'activo' => true]
        );

        $listaIds = [$minorista->id, $mayorista->id, $mayMediano->id, $mayGrande->id, $reparto->id];

        // ── 2. Rubros ─────────────────────────────────────────────────────────
        $rubros = [];
        foreach ([
            'POLLOS', 'POLLO TROZADO X CAJA', 'TROCEO DE POLLO',
            'REBOZADORES Y PAN', 'HUEVOS', 'CONGELADOS',
            'CONDIMENTOS', 'ACEITES',
            'CERDO X CAJA', 'TROCEO DE CERDO', 'EMBUTIDOS',
        ] as $nombre) {
            $rubros[$nombre] = Rubro::firstOrCreate(
                ['descripcion' => $nombre],
                ['estado' => 'A']
            )->id;
        }

        // ── 3. Artículos ──────────────────────────────────────────────────────
        // Formato por fila:
        // [codigo, descripcion, rubro, tipo_venta, unidad_medida, alicuota_iva,
        //  precio_costo_final, precio_unitario_final,
        //  tramos_MIN, tramos_MAY-RETIRO, tramos_MAY-MEDIANO, tramos_MAY-GRANDE, tramos_REPARTO]
        //
        // Cada tramo: [desde, hasta|null, precio_final_con_iva]
        // El seeder convierte a neto antes de persistir.
        // [] = sin tramos en esa lista (el motor hace fallback a la lista default).

        $articulos = [
            // ── CAJONES POLLO (CAJA, cajón, IVA 10.5%) ────────────────────────
            ['POL-SS-CJ',  'Pollo San Sebastián x Cajón', 'POLLOS', 'CAJA', 'cajón', 10.5, 57000, 64737,
                [[1, 4, 64737],       [5, null, 63684.36]],
                [[1, 4, 61500],       [5, 14,  60500],    [15, null, 60000]],
                [[1, null, 60000]],
                [[1, null, 59000]],
                [[1, 4, 63000],       [5, 9,   62000],    [10, null, 61500]],
            ],
            ['POL-OPT-CJ', 'Pollo Óptimo x Cajón', 'POLLOS', 'CAJA', 'cajón', 10.5, 57000, 66316.32,
                [[1, 4, 66316.32],    [5, null, 65263.68]],
                [[1, 4, 60500],       [5, 14,  59500],    [15, null, 59000]],
                [[1, null, 60000]],
                [[1, null, 59000]],
                [[1, 4, 62000],       [5, 9,   61000],    [10, null, 60500]],
            ],
            ['POL-BN-CJ',  'Pollo Bonnin x Cajón', 'POLLOS', 'CAJA', 'cajón', 10.5, 61000, 70000.56,
                [[1, 4, 70000.56],    [5, null, 68947.92]],
                [[1, 4, 63500],       [5, 14,  62500],    [15, null, 62000]],
                [[1, null, 64000]],
                [[1, null, 63000]],
                [[1, 4, 65000],       [5, 9,   64000],    [10, null, 63500]],
            ],
            ['POL-BNLD-CJ', 'Pollo Bonnin Línea Dorada x Cajón', 'POLLOS', 'CAJA', 'cajón', 10.5, 68000, 77895.36,
                [[1, 4, 77895.36],    [5, null, 76842.72]],
                [[1, 4, 72500],       [5, 14,  71500],    [15, null, 71000]],
                [[1, null, 71000]],
                [[1, null, 70000]],
                [[1, 4, 74000],       [5, 9,   73000],    [10, null, 72500]],
            ],
            ['POL-CR-CJ',  'Pollo Cresta Roja x Cajón', 'POLLOS', 'CAJA', 'cajón', 10.5, 60000, 67368.96,
                [[1, 4, 67368.96],    [5, null, 66316.32]],
                [[1, 4, 64500],       [5, 14,  63500],    [15, null, 63000]],
                [[1, null, 63000]],
                [[1, null, 62000]],
                [[1, 4, 66000],       [5, 9,   65000],    [10, null, 64500]],
            ],
            ['POL-CN-CJ',  'Pollo Carnave (seco) x Cajón', 'POLLOS', 'CAJA', 'cajón', 10.5, 64000, 71579.52,
                [[1, 4, 71579.52],    [5, null, 70526.88]],
                [[1, 4, 68500],       [5, 14,  67500],    [15, null, 67000]],
                [[1, null, 67000]],
                [[1, null, 66000]],
                [[1, 4, 70000],       [5, 9,   69000],    [10, null, 68500]],
            ],

            // ── MDM (CAJA, caja, IVA 10.5%) ───────────────────────────────────
            ['MDM-BN-CJ', 'Caja MDM Bonnin', 'POLLOS', 'CAJA', 'caja', 10.5, 28500, 35263.44,
                [[1, 4, 35263.44],    [5, null, 34737.12]],
                [[1, 4, 33500],       [5, 14,  33000],    [15, null, 32500]],
                [[1, null, 33000]],
                [[1, null, 32500]],
                [[1, 9, 35000],       [10, 19, 34500],    [20, null, 34000]],
            ],
            ['MDM-SS-CJ', 'Caja MDM San Sebastián', 'POLLOS', 'CAJA', 'caja', 10.5, 26000, 32105.52,
                [[1, 4, 32105.52],    [5, null, 31579.20]],
                [[1, 4, 31000],       [5, 14,  30500],    [15, null, 30000]],
                [[1, null, 30000]],
                [[1, null, 29500]],
                [[1, 9, 32500],       [10, 19, 30500],    [20, null, 30000]],
            ],

            // ── POLLO TROZADO X CAJA (PESO, kg, IVA 10.5%; 1 caja = 15 kg) ───
            ['TRZ-FILET-BN', 'Filet de Pollo Bonnin (caja 15kg) x Kg', 'POLLO TROZADO X CAJA', 'PESO', 'kg', 10.5, 6200, 6894.792,
                [[15, 74.99, 6894.792],  [75, null, 6789.532]],
                [[15, 74.99, 6550],      [75, 224.99, 6450],   [225, null, 6350]],
                [[1, null, 6450]],
                [[1, null, 6350]],
                [[15, 74.99, 6800],      [75, 149.99, 6700],   [150, null, 6600]],
            ],
            ['TRZ-FILET-BR', 'Filet de Pollo Brasilero (caja 15kg) x Kg', 'POLLO TROZADO X CAJA', 'PESO', 'kg', 10.5, 5800, 6473.736,
                [[15, 74.99, 6473.736],  [75, null, 6368.476]],
                [[15, 74.99, 6150],      [75, 224.99, 6050],   [225, null, 5950]],
                [[1, null, 6050]],
                [[1, null, 5950]],
                [[15, 74.99, 6400],      [75, 149.99, 6300],   [150, null, 6200]],
            ],
            ['TRZ-CTRAS-BN', 'Cuarto Trasero Bonnin (caja 15kg) x Kg', 'POLLO TROZADO X CAJA', 'PESO', 'kg', 10.5, 2700, 3315.81,
                [[15, 74.99, 3315.81],   [75, null, 3210.55]],
                [[15, 74.99, 3050],      [75, 224.99, 2950],   [225, null, 2850]],
                [[1, null, 2950]],
                [[1, null, 2850]],
                [[15, 74.99, 3200],      [75, 149.99, 3100],   [150, null, 3000]],
            ],
            ['TRZ-CTRAS-SS', 'Cuarto Trasero San Sebastián (caja 15kg) x Kg', 'POLLO TROZADO X CAJA', 'PESO', 'kg', 10.5, 2700, 3210.552,
                [[15, 74.99, 3210.552],  [75, null, 3105.292]],
                [[15, 74.99, 3050],      [75, 224.99, 2950],   [225, null, 2850]],
                [[1, null, 2950]],
                [[1, null, 2850]],
                [[15, 74.99, 3142.5],    [75, 149.99, 3100],   [150, null, 3000]],
            ],

            // ── TROCEO DE POLLO (PESO, kg, IVA 10.5%) solo MINORISTA ──────────
            ['POL-SUPF-KG',    'Suprema Fresca x Kg',                'TROCEO DE POLLO', 'PESO', 'kg', 10.5, 0, 8400,
                [[1, null, 8400]], [], [], [], []],
            ['POL-CTRAS-FRES', 'Cuarto Trasero Fresco x Kg',         'TROCEO DE POLLO', 'PESO', 'kg', 10.5, 0, 3700,
                [[1, 2.99, 3700], [3, 9.99, 3600], [10, null, 3300]], [], [], [], []],
            ['POL-ALITAS-KG',  'Alitas x Kg',                        'TROCEO DE POLLO', 'PESO', 'kg', 10.5, 0, 2500,
                [[1, 1.99, 2500], [2, null, 2000]], [], [], [], []],
            ['POL-CARC-KG',    'Carcasa x Kg',                       'TROCEO DE POLLO', 'PESO', 'kg', 10.5, 0, 1200,
                [[1, null, 1200]], [], [], [], []],
            ['POL-MENU-KG',    'Menudos x Kg',                       'TROCEO DE POLLO', 'PESO', 'kg', 10.5, 0, 1200,
                [[1, null, 1200]], [], [], [], []],
            ['POL-ENT-SS-KG',  'Pollo Entero San Sebastián x Kg',    'TROCEO DE POLLO', 'PESO', 'kg', 10.5, 0, 4300,
                [[1, null, 4300]], [], [], [], []],
            ['POL-ENT-BN-KG',  'Pollo Entero Bonnin x Kg',           'TROCEO DE POLLO', 'PESO', 'kg', 10.5, 0, 4600,
                [[1, null, 4600]], [], [], [], []],
            ['POL-ENT-BNLD-KG','Pollo Entero Bonnin Línea Dorada x Kg','TROCEO DE POLLO','PESO','kg', 10.5, 0, 4750,
                [[1, null, 4750]], [], [], [], []],
            ['POL-ENT-OPT-KG', 'Pollo Entero Óptimo x Kg',           'TROCEO DE POLLO', 'PESO', 'kg', 10.5, 0, 4300,
                [[1, null, 4300]], [], [], [], []],
            ['POL-HAMB-KG',    'Hamburguesa de Pollo x Kg',          'TROCEO DE POLLO', 'PESO', 'kg', 10.5, 0, 9000,
                [[1, null, 9000]], [], [], [], []],
            ['POL-PIC-KG',     'Picada de Pollo x Kg',               'TROCEO DE POLLO', 'PESO', 'kg', 10.5, 0, 8400,
                [[1, null, 8400]], [], [], [], []],
            ['POL-SUPC-KG',    'Suprema Congelada x Kg',             'TROCEO DE POLLO', 'PESO', 'kg', 10.5, 0, 7900,
                [[1, 4.99, 7900], [5, null, 7600]], [], [], [], []],
            ['POL-CTRAS-CONG', 'Cuarto Trasero Congelado x Kg',      'TROCEO DE POLLO', 'PESO', 'kg', 10.5, 0, 3600,
                [[1, 4.99, 3600], [5, null, 3500]], [], [], [], []],
            ['POL-MILA-KG',    'Milanesa de Pollo x Kg',             'TROCEO DE POLLO', 'PESO', 'kg', 10.5, 0, 6600,
                [[1, 4.99, 6600],    [5, 14.99, 6400],   [15, null, 5900]],
                [[5, 14.99, 6400],   [15, null, 5900]],
                [[1, null, 6400]],
                [[1, null, 5900]],
                [[5, 14.99, 6550],   [15, null, 6050]],
            ],

            // ── REBOZADORES Y PAN RALLADO (CAJA, bolsa, IVA 21%) ─────────────
            ['REB-RAYITO-25',   'Rayito de Sol Pan rallado x25kg (Bolsa)',    'REBOZADORES Y PAN', 'CAJA', 'bolsa', 21, 26000, 31512,
                [[1, 4, 31512],      [5, null, 30654]],
                [[1, 4, 29900],      [5, 29, 29120],     [30, null, 27820]],
                [[1, null, 29120]],
                [[1, null, 27820]],
                [[1, 4, 31395],      [5, 29, 30576],     [30, null, 29211]],
            ],
            ['REB-SOLCAMPO-20', 'Sol de Campo Pan rallado x20kg (Bolsa)',     'REBOZADORES Y PAN', 'CAJA', 'bolsa', 21, 19400, 23512.8,
                [[1, 4, 23512.8],    [5, null, 22872.6]],
                [[1, 4, 22310],      [5, 29, 21728],     [30, null, 20758]],
                [[1, null, 21728]],
                [[1, null, 20758]],
                [[1, 4, 23425.5],    [5, 29, 22814.4],   [30, null, 21795.9]],
            ],
            ['REB-FREC-20',     'Frec Pan rallado x20kg (Bolsa)',             'REBOZADORES Y PAN', 'CAJA', 'bolsa', 21, 19700, 23876.4,
                [[1, 4, 23876.4],    [5, null, 23226.3]],
                [[1, 4, 22655],      [5, 29, 22064],     [30, null, 21079]],
                [[1, null, 22064]],
                [[1, null, 21079]],
                [[1, 4, 23787.75],   [5, 29, 23167.2],   [30, null, 22132.95]],
            ],
            ['REB-SCRUZ-PR-20', 'Pan rallado Santa Cruz x20kg (Bolsa)',       'REBOZADORES Y PAN', 'CAJA', 'bolsa', 21, 20800, 25209.6,
                [[1, 4, 25209.6],    [5, null, 24523.2]],
                [[1, 4, 23920],      [5, 29, 23296],     [30, null, 22256]],
                [[1, null, 23296]],
                [[1, null, 22256]],
                [[1, 4, 25116],      [5, 29, 24460.8],   [30, null, 23368.8]],
            ],
            ['REB-SCRUZ-RB-25', 'Rebozador Santa Cruz x25kg (Bolsa)',         'REBOZADORES Y PAN', 'CAJA', 'bolsa', 21, 20800, 25209.6,
                [[1, 4, 25209.6],    [5, null, 24523.2]],
                [[1, 4, 23920],      [5, 29, 23296],     [30, null, 22256]],
                [[1, null, 23296]],
                [[1, null, 22256]],
                [[1, 4, 25116],      [5, 29, 24460.8],   [30, null, 23368.8]],
            ],
            ['REB-FREC-5',      'Frec Pan rallado x5kg (Bolsa)',              'REBOZADORES Y PAN', 'CAJA', 'bolsa', 21, 5400, 6544.8,
                [[1, 4, 6544.8],     [5, null, 6366.6]],
                [[1, 4, 6210],       [5, 29, 6048],      [30, null, 5778]],
                [[1, null, 6048]],
                [[1, null, 5778]],
                [[1, 4, 6520.5],     [5, 29, 6350.4],    [30, null, 6066.9]],
            ],

            // ── HUEVOS (CAJA, cajón, IVA 21%) ────────────────────────────────
            ['HUEVO-N1-CJ',   'Huevos N°1 Blancos x Cajón', 'HUEVOS', 'CAJA', 'cajón', 21, 0, 57000,
                [[1, null, 57000]],
                [[1, 2, 54000],      [3, 9, 53200],      [10, null, 52000]],
                [[1, null, 53200]],
                [[1, null, 52000]],
                [[1, 2, 55000],      [3, 9, 54200],      [10, null, 53000]],
            ],
            ['HUEVO-MAPLE-N1', 'Maple Huevo N°1 (Unidad)', 'HUEVOS', 'CAJA', 'maple', 21, 0, 5100,
                [[1, null, 5100]], [], [], [], []],

            // ── CONGELADOS Grupolar caja=6kg (PESO, kg, IVA 21%) ─────────────
            ['CON-BAST-MUZZ',    'Bastoncitos Muzzarella Grupolar (caja 6kg) x Kg',        'CONGELADOS', 'PESO', 'kg', 21, 9700, 12966.96,
                [[1, 5.99, 12966.96],  [6, null, 12251.10]],
                [[6, 29.99, 11640],    [30, 59.99, 11446],   [60, 89.99, 11252],   [90, 179.99, 11155],   [180, null, 10864]],
                [[6, null, 11155]],
                [[6, null, 10864]],
                [[6, 29.99, 11940],    [30, 59.99, 11746],   [60, 89.99, 11552],   [90, 179.99, 11455],   [180, null, 11164]],
            ],
            ['CON-BOC-MUZZ-CAL', 'Bocadito Muzza y Calabaza Grupolar (caja 6kg) x Kg',    'CONGELADOS', 'PESO', 'kg', 21, 4700, 6282.96,
                [[1, 5.99, 6282.96],   [6, null, 5936.10]],
                [[6, 29.99, 5640],     [30, 59.99, 5546],    [60, 89.99, 5452],    [90, 179.99, 5405],    [180, null, 5264]],
                [[6, null, 5405]],
                [[6, null, 5264]],
                [[6, 29.99, 5940],     [30, 59.99, 5846],    [60, 89.99, 5752],    [90, 179.99, 5705],    [180, null, 5564]],
            ],
            ['CON-BOC-ESP',      'Bocaditos Espinaca Grupolar (caja 6kg) x Kg',            'CONGELADOS', 'PESO', 'kg', 21, 4200, 5614.56,
                [[1, 5.99, 5614.56],   [6, null, 5304.60]],
                [[6, 29.99, 5040],     [30, 59.99, 4956],    [60, 89.99, 4872],    [90, 179.99, 4830],    [180, null, 4704]],
                [[6, null, 4830]],
                [[6, null, 4704]],
                [[6, 29.99, 5340],     [30, 59.99, 5256],    [60, 89.99, 5172],    [90, 179.99, 5130],    [180, null, 5004]],
            ],
            ['CON-MED-JYQ',      'Medallones Pollo c/JyQ Grupolar (caja 6kg) x Kg',       'CONGELADOS', 'PESO', 'kg', 21, 8800, 11763.84,
                [[1, 5.99, 11763.84],  [6, null, 11114.40]],
                [[6, 29.99, 10560],    [30, 59.99, 10384],   [60, 89.99, 10208],   [90, 179.99, 10120],   [180, null, 9856]],
                [[6, null, 10120]],
                [[6, null, 9856]],
                [[6, 29.99, 10860],    [30, 59.99, 10684],   [60, 89.99, 10508],   [90, 179.99, 10420],   [180, null, 10156]],
            ],
            ['CON-MED-POL',      'Medallones de Pollo Grupolar (caja 6kg) x Kg',           'CONGELADOS', 'PESO', 'kg', 21, 4500, 6015.60,
                [[1, 5.99, 6015.60],   [6, null, 5683.50]],
                [[6, 29.99, 5400],     [30, 59.99, 5310],    [60, 89.99, 5220],    [90, 179.99, 5175],    [180, null, 5040]],
                [[6, null, 5175]],
                [[6, null, 5040]],
                [[6, 29.99, 5700],     [30, 59.99, 5610],    [60, 89.99, 5520],    [90, 179.99, 5475],    [180, null, 5340]],
            ],
            ['CON-NUGG-GRUP',    'Nuggets de Pollo Grupolar (caja 6kg) x Kg',              'CONGELADOS', 'PESO', 'kg', 21, 6900, 9223.92,
                [[1, 5.99, 9223.92],   [6, null, 8714.70]],
                [[6, 29.99, 8280],     [30, 59.99, 8142],    [60, 89.99, 8004],    [90, 179.99, 7935],    [180, null, 7728]],
                [[6, null, 7935]],
                [[6, null, 7728]],
                [[6, 29.99, 8580],     [30, 59.99, 8442],    [60, 89.99, 8304],    [90, 179.99, 8235],    [180, null, 8028]],
            ],
            ['CON-PAL-CROC',     'Palitos Crocantes Pollo Grupolar (caja 5kg) x Kg',       'CONGELADOS', 'PESO', 'kg', 21, 7850, 10493.88,
                [[1, 4.99, 10493.88],  [5, null, 9914.55]],
                [[5, 24.99, 9420],     [25, 49.99, 9263],    [50, 74.99, 9106],    [75, 149.99, 9027.5],  [150, null, 8792]],
                [[5, null, 9027.5]],
                [[5, null, 8792]],
                [[5, 24.99, 9720],     [25, 49.99, 9563],    [50, 74.99, 9406],    [75, 149.99, 9327.5],  [150, null, 9092]],
            ],
            ['CON-PAT-REB',      'Patitas Rebozadas Grupolar (caja 6kg) x Kg',             'CONGELADOS', 'PESO', 'kg', 21, 4500, 6015.60,
                [[1, 5.99, 6015.60],   [6, null, 5683.50]],
                [[6, 29.99, 5400],     [30, 59.99, 5310],    [60, 89.99, 5220],    [90, 179.99, 5175],    [180, null, 5040]],
                [[6, null, 5175]],
                [[6, null, 5040]],
                [[6, 29.99, 5700],     [30, 59.99, 5610],    [60, 89.99, 5520],    [90, 179.99, 5475],    [180, null, 5340]],
            ],
            ['CON-REBOSAURIO',   'Rebosaurio Grupolar (caja 5kg) x Kg',                    'CONGELADOS', 'PESO', 'kg', 21, 6500, 8689.20,
                [[1, 4.99, 8689.20],   [5, null, 8209.50]],
                [[5, 24.99, 7800],     [25, 49.99, 7670],    [50, 74.99, 7540],    [75, 149.99, 7475],    [150, null, 7280]],
                [[5, null, 7475]],
                [[5, null, 7280]],
                [[5, 24.99, 8100],     [25, 49.99, 7970],    [50, 74.99, 7840],    [75, 149.99, 7775],    [150, null, 7580]],
            ],
            ['CON-MERLU-REB',    'Merluza Rebozada Grupolar (caja 5kg) x Kg',              'CONGELADOS', 'PESO', 'kg', 21, 8850, 11830.68,
                [[1, 4.99, 11830.68],  [5, null, 11177.55]],
                [[5, 24.99, 10620],    [25, 49.99, 10443],   [50, 74.99, 10266],   [75, 149.99, 10177.5], [150, null, 9912]],
                [[5, null, 10177.5]],
                [[5, null, 9912]],
                [[5, 24.99, 10920],    [25, 49.99, 10743],   [50, 74.99, 10566],   [75, 149.99, 10477.5], [150, null, 10212]],
            ],

            // ── CONGELADOS Tres Arroyos caja=5kg ──────────────────────────────
            ['CON-RICOSAUR',  'Ricosaurios Tres Arroyos (caja 5kg) x Kg',          'CONGELADOS', 'PESO', 'kg', 21, 6900, 9223.92,
                [[1, 4.99, 9223.92],   [5, null, 8714.70]],
                [[5, 24.99, 8280],     [25, 49.99, 8142],    [50, 74.99, 8004],    [75, 149.99, 7935],    [150, null, 7728]],
                [[5, null, 8004]],
                [[5, null, 7935]],
                [[5, 24.99, 8580],     [25, 49.99, 8442],    [50, 74.99, 8304],    [75, 149.99, 8235],    [150, null, 8028]],
            ],
            ['CON-NUGG-3A',   'Nuggets Tres Arroyos (caja 5kg) x Kg',              'CONGELADOS', 'PESO', 'kg', 21, 7950, 10627.56,
                [[1, 4.99, 10627.56],  [5, null, 10040.85]],
                [[5, 24.99, 9540],     [25, 49.99, 9381],    [50, 74.99, 9222],    [75, 149.99, 9142.5],  [150, null, 8904]],
                [[5, null, 9222]],
                [[5, null, 9142.5]],
                [[5, 24.99, 9840],     [25, 49.99, 9681],    [50, 74.99, 9522],    [75, 149.99, 9442.5],  [150, null, 9204]],
            ],

            // ── CONGELADOS Sadia caja=6kg (2x3kg) ────────────────────────────
            ['CON-PECHU-SADIA',  'Pechuguitas Sadia (caja 6kg) x Kg',              'CONGELADOS', 'PESO', 'kg', 21, 9200, 12107.20,
                [[1, 5.99, 12107.20],  [6, null, 11619.60]],
                [[6, 29.99, 11040],    [30, 59.99, 10856],   [60, 89.99, 10672],   [90, 179.99, 10580],   [180, null, 10304]],
                [[6, null, 10672]],
                [[6, null, 10580]],
                [[6, 29.99, 11340],    [30, 59.99, 11156],   [60, 89.99, 10972],   [90, 179.99, 10880],   [180, null, 10604]],
            ],
            ['CON-FINGER-SADIA', 'Chicken Fingers Sadia (caja 6kg) x Kg',          'CONGELADOS', 'PESO', 'kg', 21, 9600, 12633.60,
                [[1, 5.99, 12633.60],  [6, null, 12124.80]],
                [[6, 29.99, 11520],    [30, 59.99, 11328],   [60, 89.99, 11136],   [90, 179.99, 11040],   [180, null, 10752]],
                [[6, null, 11136]],
                [[6, null, 11040]],
                [[6, 29.99, 11820],    [30, 59.99, 11628],   [60, 89.99, 11436],   [90, 179.99, 11340],   [180, null, 11052]],
            ],

            // ── CONGELADOS Mc Cain ────────────────────────────────────────────
            ['CON-PAP-NOIS',    'Papas Noisette Mc Cain (caja 10kg = 4x2,5kg) x Kg',  'CONGELADOS', 'PESO', 'kg', 21, 18790, 25118.472,
                [[1, 9.99, 25118.472],  [10, null, 23731.77]],
                [[10, 49.99, 22548],    [50, 99.99, 22172.2],  [100, 149.99, 21796.4], [150, 299.99, 21608.5], [300, null, 21044.8]],
                [[10, null, 21796.4]],
                [[10, null, 21608.5]],
                [[10, 49.99, 22848],    [50, 99.99, 22472.2],  [100, 149.99, 22096.4], [150, 299.99, 21908.5], [300, null, 21344.8]],
            ],
            ['CON-PAP-BAST-25', 'Papas Bastón Mc Cain 2,5kg (caja 15kg = 6x2,5kg) x Kg', 'CONGELADOS', 'PESO', 'kg', 21, 10000, 13368,
                [[1, 14.99, 13368],     [15, null, 12630]],
                [[15, 74.99, 12000],    [75, 149.99, 11800],   [150, 224.99, 11600],  [225, 449.99, 11500],  [450, null, 11200]],
                [[15, null, 11600]],
                [[15, null, 11500]],
                [[15, 74.99, 12300],    [75, 149.99, 12100],   [150, 224.99, 11900],  [225, 449.99, 11800],  [450, null, 11500]],
            ],
            ['CON-PAP-CAR',     'Papas Carita Mc Cain 1,5kg (caja 9kg = 6x1,5kg) x Kg',  'CONGELADOS', 'PESO', 'kg', 21, 11280, 15079.104,
                [[1, 8.99, 15079.104],  [9, null, 14246.64]],
                [[9, 44.99, 13536],     [45, 89.99, 13310.4],  [90, 134.99, 13084.8], [135, 269.99, 12972],  [270, null, 12633.6]],
                [[9, null, 13084.8]],
                [[9, null, 12972]],
                [[9, 44.99, 13836],     [45, 89.99, 13610.4],  [90, 134.99, 13384.8], [135, 269.99, 13272],  [270, null, 12933.6]],
            ],
            ['CON-PAP-BAST-700','Papas Bastón Mc Cain 700gr (caja 11.2kg = 16x700gr) x Kg','CONGELADOS','PESO','kg', 21, 4300, 5748.24,
                [[1, 11.19, 5748.24],   [11.2, null, 5430.90]],
                [[11.2, 55.99, 5160],   [56, 111.99, 5074],    [112, 167.99, 4988],   [168, 335.99, 4945],   [336, null, 4816]],
                [[11.2, null, 4988]],
                [[11.2, null, 4945]],
                [[11.2, 55.99, 5460],   [56, 111.99, 5374],    [112, 167.99, 5288],   [168, 335.99, 5245],   [336, null, 5116]],
            ],

            // ── CONDIMENTOS / ACEITES (IVA 21%) ──────────────────────────────
            ['CND-SAL-25',  'Sal Celusal x25kg (Bolsa)', 'CONDIMENTOS', 'CAJA', 'bolsa', 21, 10920, 13224.12,
                [[1, null, 13224.12]],
                [[1, 4, 12800],     [5, 9, 12558],    [10, null, 12012]],
                [[1, null, 12558]],
                [[1, null, 12012]],
                [[1, 4, 13800],     [5, 9, 13558],    [10, null, 13012]],
            ],
            ['ACE-GIRA-4L', 'Aceite Girasol Puro x4 litros (Bidón)', 'ACEITES', 'CAJA', 'bidón', 21, 11071, 13150,
                [[1, 4, 13150],     [5, null, 13050]],
                [[1, 4, 12500],     [5, 9, 12399.52], [10, 19, 12178.1],  [20, null, 11956.68]],
                [[1, null, 12178.1]],
                [[1, null, 11956.68]],
                [[1, 4, 13500],     [5, 9, 13399.52], [10, 19, 13178.1],  [20, null, 12956.68]],
            ],

            // ── CERDO X CAJA (PESO, kg, IVA 10.5%) ───────────────────────────
            ['CER-BD-CAJA', 'Caja de Bondiola x Kg', 'CERDO X CAJA', 'PESO', 'kg', 10.5, 5500, 6631.38,
                [[1, null, 6631.38]],
                [[1, 74.99, 6300],  [75, 224.99, 6000],  [225, null, 5900]],
                [[1, null, 6000]],
                [[1, null, 5900]],
                [[1, 74.99, 6450],  [75, 224.99, 6150],  [225, null, 6050]],
            ],
            ['CER-JD-CAJA', 'Caja Jamón Deshuesado x Kg', 'CERDO X CAJA', 'PESO', 'kg', 10.5, 5800, 0,
                [[1, null, 0]],
                [[1, 74.99, 6500],  [75, 224.99, 6200],  [225, null, 6100]],
                [[1, null, 6200]],
                [[1, null, 6100]],
                [[1, 74.99, 6650],  [75, 224.99, 6350],  [225, null, 6250]],
            ],

            // ── TROCEO DE CERDO (PESO, kg, IVA 10.5%) ────────────────────────
            ['CER-BD-KG',    'Bondiola x Kg',               'TROCEO DE CERDO', 'PESO', 'kg', 10.5, 0, 7500,
                [[1, null, 7500]], [], [], [], []],
            ['CER-BD-PZ',    'Bondiola x Pieza x Kg',        'TROCEO DE CERDO', 'PESO', 'kg', 10.5, 5500, 6800,
                [[1, null, 6800]],
                [[1, null, 6460]],
                [[1, null, 6460]],
                [[1, null, 6460]],
                [[1, null, 6660]],
            ],
            ['CER-MAT',      'Matambre de Cerdo x Kg',       'TROCEO DE CERDO', 'PESO', 'kg', 10.5, 0, 10200,
                [[1, null, 10200]], [], [], [], []],
            ['CER-PUL-KG',   'Pulpa x Kg',                   'TROCEO DE CERDO', 'PESO', 'kg', 10.5, 0, 8500,
                [[1, null, 8500]], [], [], [], []],
            ['CER-JD-PZ',    'Jamón Deshuesado x Pieza x Kg','TROCEO DE CERDO', 'PESO', 'kg', 10.5, 5800, 7000,
                [[1, null, 7000]],
                [[1, null, 6650]],
                [[1, null, 6650]],
                [[1, null, 6650]],
                [[1, null, 6850]],
            ],
            ['CER-JE-KG',    'Jamón Pata Entera x Kg',       'TROCEO DE CERDO', 'PESO', 'kg', 10.5, 4000, 4700,
                [[1, null, 4700]],
                [[1, null, 4600]],
                [[1, null, 4600]],
                [[1, null, 4600]],
                [[1, null, 4800]],
            ],
            ['CER-PECHO-KG', 'Pecho de Cerdo x Kg',          'TROCEO DE CERDO', 'PESO', 'kg', 10.5, 0, 7500,
                [[1, null, 7500]], [], [], [], []],
            ['CER-PECHO-PZ', 'Pecho de Cerdo x Pieza x Kg',  'TROCEO DE CERDO', 'PESO', 'kg', 10.5, 6100, 6800,
                [[1, null, 6800]],
                [[1, null, 6460]],
                [[1, null, 6460]],
                [[1, null, 6460]],
                [[1, null, 6660]],
            ],
            ['CER-PALETA',   'Paleta de Cerdo x Kg',          'TROCEO DE CERDO', 'PESO', 'kg', 10.5, 0, 4100,
                [[1, null, 4100]], [], [], [], []],
            ['CER-CARRE-KG', 'Carré de Cerdo x Kg',           'TROCEO DE CERDO', 'PESO', 'kg', 10.5, 0, 7500,
                [[1, null, 7500]], [], [], [], []],
            ['CER-CARRE-PZ', 'Carré de Cerdo x Pieza x Kg',   'TROCEO DE CERDO', 'PESO', 'kg', 10.5, 6100, 6800,
                [[1, null, 6800]],
                [[1, null, 6460]],
                [[1, null, 6460]],
                [[1, null, 6460]],
                [[1, null, 6660]],
            ],
            ['CER-CARRE-DH', 'Carré Deshuesado x Kg',         'TROCEO DE CERDO', 'PESO', 'kg', 10.5, 0, 10600,
                [[1, null, 10600]], [], [], [], []],
            ['CER-RIBS',     'Ribs de Cerdo x Kg',             'TROCEO DE CERDO', 'PESO', 'kg', 10.5, 0, 7500,
                [[1, null, 7500]], [], [], [], []],
            ['CER-MILA-KG',  'Milanesa de Cerdo x Kg',         'TROCEO DE CERDO', 'PESO', 'kg', 10.5, 0, 6900,
                [[1, null, 6900]], [], [], [], []],
            ['CER-OSO-KG',   'Osobuco de Cerdo x Kg',          'TROCEO DE CERDO', 'PESO', 'kg', 10.5, 0, 4300,
                [[1, null, 4300]], [], [], [], []],
            ['CER-SOLO-KG',  'Solomillo de Cerdo x Kg',        'TROCEO DE CERDO', 'PESO', 'kg', 10.5, 0, 10500,
                [[1, null, 10500]], [], [], [], []],
            ['CER-AMER',     'Americano de Cerdo x Kg',         'TROCEO DE CERDO', 'PESO', 'kg', 10.5, 0, 8100,
                [[1, null, 8100]], [], [], [], []],
            ['CER-PIC-KG',   'Picada de Cerdo x Kg',           'TROCEO DE CERDO', 'PESO', 'kg', 10.5, 0, 9200,
                [[1, null, 9200]], [], [], [], []],
            ['CER-HAMB-KG',  'Hamburguesa de Cerdo x Kg',      'TROCEO DE CERDO', 'PESO', 'kg', 10.5, 0, 9500,
                [[1, null, 9500]], [], [], [], []],
            ['CER-VACIO',    'Vacío/Churrasquito de Cerdo x Kg','TROCEO DE CERDO', 'PESO', 'kg', 10.5, 0, 9600,
                [[1, null, 9600]], [], [], [], []],
            ['CER-HUESITO',  'Huesito x Kg',                   'TROCEO DE CERDO', 'PESO', 'kg', 10.5, 0, 1250,
                [[1, null, 1250]], [], [], [], []],
            ['CER-CUERO',    'Cuero x Kg',                     'TROCEO DE CERDO', 'PESO', 'kg', 10.5, 0, 2500,
                [[1, null, 2500]], [], [], [], []],
            ['CER-PANC-SAL', 'Panceta Salada x Kg',            'TROCEO DE CERDO', 'PESO', 'kg', 10.5, 12000, 14900,
                [[1, null, 14900]],
                [[1, null, 13200]],
                [[1, null, 13200]],
                [[1, null, 13200]],
                [[1, null, 13400]],
            ],

            // ── EMBUTIDOS (IVA 21%) ───────────────────────────────────────────
            ['EMB-CHORI-PURO', 'Chorizo Puro x Kg',          'EMBUTIDOS', 'PESO', 'kg', 21, 5400, 7100,
                [[1, null, 7100]],
                [[1, null, 6000]],
                [[1, null, 6000]],
                [[1, null, 6000]],
                [[1, null, 6200]],
            ],
            ['EMB-CHORI-BOMB', 'Bombón Chorizo x Kg',        'EMBUTIDOS', 'PESO', 'kg', 21, 0, 8500,
                [[1, null, 8500]], [], [], [], []],
            ['EMB-SAL-FRESCA', 'Salchicha Fresca x Kg',      'EMBUTIDOS', 'PESO', 'kg', 21, 0, 9900,
                [[1, null, 9900]], [], [], [], []],
            ['EMB-MORC-ROS',   'Morcilla Rosca x Kg',        'EMBUTIDOS', 'PESO', 'kg', 21, 3100, 4200,
                [[1, null, 4200]],
                [[1, null, 3400]],
                [[1, null, 3400]],
                [[1, null, 3400]],
                [[1, null, 3600]],
            ],
            ['EMB-MORC-AT',    'Morcilla Atada x Kg',        'EMBUTIDOS', 'PESO', 'kg', 21, 0, 4800,
                [[1, null, 4800]], [], [], [], []],
            ['EMB-MORC-BMB',   'Morcilla Bombón x Kg',       'EMBUTIDOS', 'PESO', 'kg', 21, 3500, 4700,
                [[1, null, 4700]],
                [[1, null, 3850]],
                [[1, null, 3850]],
                [[1, null, 3850]],
                [[1, null, 4050]],
            ],
            ['EMB-MORC-VAS',   'Morcilla Vasca x Kg',        'EMBUTIDOS', 'PESO', 'kg', 21, 0, 7300,
                [[1, null, 7300]], [], [], [], []],
            ['EMB-SAL-VIENA',  'Salchicha Viena x Kg',       'EMBUTIDOS', 'PESO', 'kg', 21, 0, 9500,
                [[1, null, 9500]], [], [], [], []],
            ['EMB-CHORI-COL',  'Chorizo Colorado (unidad)',  'EMBUTIDOS', 'CAJA', 'unidad', 21, 7700, 1600,
                [[1, null, 1600]],
                [[1, null, 8470]],
                [[1, null, 8470]],
                [[1, null, 8470]],
                [[1, null, 8670]],
            ],
            ['EMB-RIN-KG',     'Riñón x Kg',                 'EMBUTIDOS', 'PESO', 'kg', 21, 0, 7100,
                [[1, null, 7100]], [], [], [], []],
            ['EMB-CHIN-KG',    'Chinchulín x Kg',            'EMBUTIDOS', 'PESO', 'kg', 21, 0, 7300,
                [[1, null, 7300]], [], [], [], []],
        ];

        // Convierte precio FINAL (con IVA) a NETO para persistir en DB.
        $finalAneto = fn(float $final, float $iva) => round($final / (1 + $iva / 100), 4);

        $count = 0;
        foreach ($articulos as $row) {
            [$codigo, $descripcion, $rubro, $tipoVenta, $unidad, $iva,
             $costoFinal, $precioFinal,
             $tMin, $tMayRetiro, $tMayMed, $tMayGde, $tReparto] = $row;

            $precioUnitNeto = $finalAneto($precioFinal, $iva);
            $costoNeto      = $costoFinal > 0 ? $finalAneto($costoFinal, $iva) : 0;

            $art = Articulo::firstOrCreate(
                ['codigo' => $codigo],
                [
                    'descripcion'          => $descripcion,
                    'rubro_id'             => $rubros[$rubro],
                    'tipo_venta'           => $tipoVenta,
                    'unidad_medida'        => $unidad,
                    'alicuota_iva'         => $iva,
                    'precio_costo'         => $costoNeto,
                    'precio_unitario'      => $precioUnitNeto,
                    'precio_unitario_caja' => 0,
                    'cantidad_caja'        => 1,
                    'modifica_stock'       => true,
                    'estado'               => 'A',
                    'fecha_modif'          => now()->toDateString(),
                ]
            );

            $art->fill([
                'descripcion'     => $descripcion,
                'rubro_id'        => $rubros[$rubro],
                'tipo_venta'      => $tipoVenta,
                'unidad_medida'   => $unidad,
                'alicuota_iva'    => $iva,
                'precio_costo'    => $costoNeto,
                'precio_unitario' => $precioUnitNeto,
            ])->save();

            // Idempotente: borra tramos previos de las 5 listas para este artículo
            ArticuloPrecio::where('articulo_id', $art->id)
                ->whereIn('lista_precio_id', $listaIds)
                ->delete();

            $listasYTramos = [
                [$minorista,   $tMin],
                [$mayorista,   $tMayRetiro],
                [$mayMediano,  $tMayMed],
                [$mayGrande,   $tMayGde],
                [$reparto,     $tReparto],
            ];

            foreach ($listasYTramos as [$lista, $tramos]) {
                $orden = 0;
                foreach ($tramos as [$desde, $hasta, $precio]) {
                    ArticuloPrecio::create([
                        'articulo_id'     => $art->id,
                        'lista_precio_id' => $lista->id,
                        'cantidad_desde'  => $desde,
                        'cantidad_hasta'  => $hasta,
                        'precio'          => $finalAneto((float) $precio, $iva),
                        'activo'          => true,
                        'orden'           => $orden++,
                    ]);
                }
            }

            $count++;
        }

        $totales = [];
        foreach ([
            'MIN'        => $minorista,
            'MAY-RETIRO' => $mayorista,
            'MAY-MED'    => $mayMediano,
            'MAY-GDE'    => $mayGrande,
            'REPARTO'    => $reparto,
        ] as $label => $lista) {
            $n = ArticuloPrecio::where('lista_precio_id', $lista->id)->count();
            $totales[] = "{$label}: {$n}";
        }

        $this->command->info("✓ Cargados {$count} artículos con precios actualizados.");
        $this->command->info('  Tramos por lista → ' . implode(' | ', $totales));
    }
}
