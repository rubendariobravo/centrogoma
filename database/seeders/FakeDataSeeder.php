<?php

namespace Database\Seeders;

use App\Models\Articulo;
use App\Models\ArticuloPrecio;
use App\Models\Caja;
use App\Models\CcCliente;
use App\Models\CcProveedor;
use App\Models\Cliente;
use App\Models\Compra;
use App\Models\Descarte;
use App\Models\DetCompra;
use App\Models\DetVenta;
use App\Models\DetalleOrdenPago;
use App\Models\Empleado;
use App\Models\ListaPrecio;
use App\Models\Marca;
use App\Models\MovimientoArticulo;
use App\Models\OrdenPago;
use App\Models\PagoVenta;
use App\Models\Pesada;
use App\Models\Produccion;
use App\Models\Proveedor;
use App\Models\Recibo;
use App\Models\ReciboDetalle;
use App\Models\Remito;
use App\Models\Rubro;
use App\Models\TipoIva;
use App\Models\User;
use App\Models\Venta;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class FakeDataSeeder extends Seeder
{
    public function run(): void
    {
        // Disable auditing so the seeder doesn't flood the audit log
        config(['audit.enabled' => false]);

        // ── Rubros ─────────────────────────────────────────────────────────────
        $rubros = [
            ['descripcion' => 'Pollo entero',           'alicuota_iva' => 10.5, 'es_mayorista' => false],
            ['descripcion' => 'Cortes de pollo',         'alicuota_iva' => 10.5, 'es_mayorista' => false],
            ['descripcion' => 'Subproductos avícolas',   'alicuota_iva' => 10.5, 'es_mayorista' => false],
            ['descripcion' => 'Huevos',                  'alicuota_iva' => 0,    'es_mayorista' => false],
            ['descripcion' => 'Fiambres y embutidos',    'alicuota_iva' => 21,   'es_mayorista' => false],
            ['descripcion' => 'Alimentos balanceados',   'alicuota_iva' => 10.5, 'es_mayorista' => true],
            ['descripcion' => 'Envases y embalajes',     'alicuota_iva' => 21,   'es_mayorista' => true],
            ['descripcion' => 'Varios',                  'alicuota_iva' => 21,   'es_mayorista' => false],
        ];

        $rubroObjs = [];
        foreach ($rubros as $r) {
            $rubroObjs[] = Rubro::create(array_merge($r, ['estado' => 'A']));
        }

        [$rPollo, $rCortes, $rSubprod, $rHuevos, $rFiambres, $rBalanceado, $rEnvases, $rVarios] = $rubroObjs;

        // ── Proveedores ────────────────────────────────────────────────────────
        $proveedores = [
            [
                'razon_social'    => 'Frigorífico La Pampa S.A.',
                'nombre_fantasia' => 'La Pampa',
                'cuit'            => '30-71234567-9',
                'telefono'        => '011-4523-8800',
                'celular'         => '11-5523-8801',
                'email'           => 'ventas@lapampa.com.ar',
                'domicilio'       => 'Av. Rivadavia 4520',
                'localidad'       => 'CABA',
                'provincia'       => 'Buenos Aires',
                'contacto'        => 'Roberto Fernández',
                'saldo'           => 0,
                'estado'          => 'A',
            ],
            [
                'razon_social'    => 'Avícola del Norte S.R.L.',
                'nombre_fantasia' => 'Avícola Norte',
                'cuit'            => '30-68901234-5',
                'telefono'        => '0341-450-0011',
                'celular'         => '341-5450-0011',
                'email'           => 'pedidos@avicola-norte.com',
                'domicilio'       => 'Ruta 9 km 15',
                'localidad'       => 'Rosario',
                'provincia'       => 'Santa Fe',
                'contacto'        => 'Claudia Gómez',
                'saldo'           => 0,
                'estado'          => 'A',
            ],
            [
                'razon_social'    => 'Distribuidora San José S.R.L.',
                'nombre_fantasia' => 'San José',
                'cuit'            => '30-59876543-1',
                'telefono'        => '0351-455-2200',
                'celular'         => '351-5455-2201',
                'email'           => 'info@sanjose.com.ar',
                'domicilio'       => 'Vélez Sarsfield 2100',
                'localidad'       => 'Córdoba',
                'provincia'       => 'Córdoba',
                'contacto'        => 'Mario Benedetti',
                'saldo'           => 0,
                'estado'          => 'A',
            ],
            [
                'razon_social'    => 'Granja Los Álamos S.A.',
                'cuit'            => '30-71452369-8',
                'telefono'        => '02477-40-1234',
                'domicilio'       => 'Ruta Provincial 65 km 8',
                'localidad'       => 'San Antonio de Areco',
                'provincia'       => 'Buenos Aires',
                'contacto'        => 'Héctor Alamo',
                'saldo'           => 0,
                'estado'          => 'A',
            ],
        ];

        $provObjs = [];
        foreach ($proveedores as $p) {
            $provObjs[] = Proveedor::create($p);
        }

        [$provLaPampa, $provNorte, $provSanJose, $provAlamos] = $provObjs;

        // ── Marcas ─────────────────────────────────────────────────────────────
        $marcas = [
            ['descripcion' => 'La Estrella',    'comision' => 0.02,  'proveedor_id' => $provLaPampa->id, 'estado' => 'A'],
            ['descripcion' => 'San Sebastián',  'comision' => 0.025, 'proveedor_id' => $provNorte->id,   'estado' => 'A'],
            ['descripcion' => 'Del Campo',       'comision' => 0.015, 'proveedor_id' => $provAlamos->id,  'estado' => 'A'],
            ['descripcion' => 'Granja Feliz',    'comision' => 0.02,  'proveedor_id' => $provNorte->id,   'estado' => 'A'],
            ['descripcion' => 'Sin marca',       'comision' => 0,     'proveedor_id' => null,             'estado' => 'A'],
        ];

        $marcaObjs = [];
        foreach ($marcas as $m) {
            $marcaObjs[] = Marca::create($m);
        }

        [$marcaEstrella, $marcaSanSeb, $marcaDelCampo, $marcaGranja, $marcaSinMarca] = $marcaObjs;

        // ── Empleados ──────────────────────────────────────────────────────────
        $adminUser = User::where('email', 'admin@avicola.test')->first()
            ?? User::firstOrCreate(
                ['email' => 'admin@avicola.test'],
                ['name' => 'Administrador', 'password' => Hash::make('password'), 'email_verified_at' => now()]
            );

        $userVendedor = User::firstOrCreate(
            ['email' => 'vendedor@avicola.test'],
            ['name' => 'María García', 'password' => Hash::make('password'), 'email_verified_at' => now()]
        );

        $empleados = [
            [
                'nombre'    => 'María',
                'apellido'  => 'García',
                'dni'       => '28456789',
                'cuil'      => '27-28456789-4',
                'telefono'  => '011-4523-1122',
                'celular'   => '11-5523-1122',
                'email'     => 'maria.garcia@avicola.test',
                'domicilio' => 'Lavalle 1234, CABA',
                'rol'       => 'vendedor',
                'comision'  => 3.5,
                'user_id'   => $userVendedor->id,
                'estado'    => 'A',
            ],
            [
                'nombre'    => 'Juan',
                'apellido'  => 'Pérez',
                'dni'       => '31234567',
                'cuil'      => '20-31234567-1',
                'celular'   => '11-5589-4455',
                'domicilio' => 'Corrientes 5678, CABA',
                'rol'       => 'repartidor',
                'comision'  => 0,
                'estado'    => 'A',
            ],
            [
                'nombre'    => 'Carlos',
                'apellido'  => 'López',
                'dni'       => '25789012',
                'cuil'      => '20-25789012-5',
                'telefono'  => '011-4522-9900',
                'email'     => 'carlos.lopez@avicola.test',
                'domicilio' => 'Belgrano 890, CABA',
                'rol'       => 'administrativo',
                'comision'  => 0,
                'user_id'   => $adminUser?->id,
                'estado'    => 'A',
            ],
            [
                'nombre'    => 'Ana',
                'apellido'  => 'Martínez',
                'dni'       => '34567890',
                'cuil'      => '27-34567890-3',
                'celular'   => '11-5534-7788',
                'domicilio' => 'San Martín 321, La Matanza',
                'rol'       => 'vendedor',
                'comision'  => 3.0,
                'estado'    => 'A',
            ],
            [
                'nombre'    => 'Diego',
                'apellido'  => 'Ramírez',
                'dni'       => '29876543',
                'rol'       => 'produccion',
                'comision'  => 0,
                'estado'    => 'A',
            ],
        ];

        $empObjs = [];
        foreach ($empleados as $e) {
            $empObjs[] = Empleado::create($e);
        }

        [$empMaria, $empJuan, $empCarlos, $empAna, $empDiego] = $empObjs;

        // ── Tipos IVA ─────────────────────────────────────────────────────────
        $tivaRI  = TipoIva::where('codigo_afip', '01')->first();
        $tivaCF  = TipoIva::where('codigo_afip', '05')->first();
        $tivaEx  = TipoIva::where('codigo_afip', '04')->first();
        $tivaRS  = TipoIva::where('codigo_afip', '06')->first();

        // ── Clientes ───────────────────────────────────────────────────────────
        // Resolvemos los ids de lista por codigo para no hardcodear.
        // Mayoristas => lista MAYORISTA. Minoristas / consumidor final => null (lista default).
        $listaMayoristaId = ListaPrecio::where('codigo', 'MAYORISTA')->value('id');
        $listaMinoristaId = ListaPrecio::where('codigo', 'MINORISTA')->value('id');

        $clientesData = [
            [
                'razon_social'    => 'Carnicería El Gaucho S.A.',
                'nombre_fantasia' => 'El Gaucho',
                'cuit'            => '30-62345678-9',
                'tipoiva_id'      => $tivaRI?->id,
                'empleado_id'     => $empMaria->id,
                'telefono'        => '011-4678-2233',
                'celular'         => '11-5678-2233',
                'email'           => 'compras@elgaucho.com.ar',
                'domicilio'       => 'Cabildo 3456',
                'localidad'       => 'CABA',
                'provincia'       => 'Buenos Aires',
                'lista_precio_id' => $listaMayoristaId,
                'bonificacion'    => 5.0,
                'limite_credito'  => 500000,
                'porcentaje_iibb' => 0.03,
                'percepcion_iva'  => true,
                'es_mayorista'    => true,
                'saldo'           => 0,
                'estado'          => 'A',
            ],
            [
                'razon_social'    => 'Supermercado Norte S.R.L.',
                'nombre_fantasia' => 'Super Norte',
                'cuit'            => '30-71234580-1',
                'tipoiva_id'      => $tivaRI?->id,
                'empleado_id'     => $empAna->id,
                'telefono'        => '0341-480-5500',
                'email'           => 'compras@supernorte.com',
                'domicilio'       => 'San Martín 1500',
                'localidad'       => 'Rosario',
                'provincia'       => 'Santa Fe',
                'lista_precio_id' => $listaMayoristaId,
                'bonificacion'    => 3.0,
                'limite_credito'  => 800000,
                'porcentaje_iibb' => 0.03,
                'percepcion_iva'  => true,
                'es_mayorista'    => true,
                'saldo'           => 0,
                'estado'          => 'A',
            ],
            [
                'razon_social'    => 'Distribuidora Avícola Cba S.R.L.',
                'nombre_fantasia' => 'Avícola Cba',
                'cuit'            => '30-59012345-7',
                'tipoiva_id'      => $tivaRI?->id,
                'empleado_id'     => $empMaria->id,
                'telefono'        => '0351-455-7722',
                'email'           => 'pedidos@avicola-cba.com.ar',
                'domicilio'       => 'Av. Colón 2340',
                'localidad'       => 'Córdoba',
                'provincia'       => 'Córdoba',
                'lista_precio_id' => $listaMayoristaId,
                'bonificacion'    => 7.0,
                'limite_credito'  => 1200000,
                'porcentaje_iibb' => 0.025,
                'percepcion_iva'  => false,
                'es_mayorista'    => true,
                'saldo'           => 0,
                'estado'          => 'A',
            ],
            [
                'razon_social'    => 'Pollo Express S.A.',
                'cuit'            => '30-65432109-3',
                'tipoiva_id'      => $tivaRI?->id,
                'empleado_id'     => $empAna->id,
                'telefono'        => '011-4455-3300',
                'domicilio'       => 'Bogotá 234',
                'localidad'       => 'CABA',
                'provincia'       => 'Buenos Aires',
                'lista_precio_id' => $listaMayoristaId,
                'bonificacion'    => 4.0,
                'limite_credito'  => 300000,
                'porcentaje_iibb' => 0.03,
                'percepcion_iva'  => true,
                'es_mayorista'    => true,
                'saldo'           => 0,
                'estado'          => 'A',
            ],
            [
                'razon_social'    => 'Varela Hector Eduardo',
                'cuit'            => '20-12345678-9',
                'tipoiva_id'      => $tivaRS?->id,
                'empleado_id'     => $empMaria->id,
                'celular'         => '11-5234-5678',
                'domicilio'       => 'Urquiza 567',
                'localidad'       => 'Lomas de Zamora',
                'provincia'       => 'Buenos Aires',
                'lista_precio_id' => $listaMayoristaId,
                'bonificacion'    => 0,
                'limite_credito'  => 50000,
                'porcentaje_iibb' => 0,
                'percepcion_iva'  => false,
                'es_mayorista'    => false,
                'saldo'           => 0,
                'estado'          => 'A',
            ],
            [
                'razon_social'    => 'González María Elena',
                'dni'             => '22345678',
                'tipoiva_id'      => $tivaCF?->id,
                'celular'         => '11-5345-6789',
                'localidad'       => 'CABA',
                'provincia'       => 'Buenos Aires',
                'lista_precio_id' => null, // consumidor final usa lista default
                'bonificacion'    => 0,
                'limite_credito'  => 0,
                'porcentaje_iibb' => 0,
                'percepcion_iva'  => false,
                'es_mayorista'    => false,
                'saldo'           => 0,
                'estado'          => 'A',
            ],
            [
                'razon_social'    => 'Rodríguez Pablo Javier',
                'dni'             => '35678901',
                'tipoiva_id'      => $tivaCF?->id,
                'celular'         => '11-5456-7890',
                'domicilio'       => 'Sarmiento 890',
                'localidad'       => 'Buenos Aires',
                'provincia'       => 'Buenos Aires',
                'lista_precio_id' => null, // consumidor final usa lista default
                'bonificacion'    => 0,
                'limite_credito'  => 10000,
                'porcentaje_iibb' => 0,
                'percepcion_iva'  => false,
                'es_mayorista'    => false,
                'saldo'           => 0,
                'estado'          => 'A',
            ],
            [
                'razon_social'    => 'Ferreyra Claudia',
                'dni'             => '27890123',
                'tipoiva_id'      => $tivaCF?->id,
                'celular'         => '351-5567-8901',
                'localidad'       => 'Córdoba',
                'provincia'       => 'Córdoba',
                'lista_precio_id' => null, // consumidor final usa lista default
                'bonificacion'    => 2.0,
                'limite_credito'  => 0,
                'porcentaje_iibb' => 0,
                'percepcion_iva'  => false,
                'es_mayorista'    => false,
                'saldo'           => 0,
                'estado'          => 'A',
            ],
        ];

        $clienteObjs = [];
        foreach ($clientesData as $c) {
            $clienteObjs[] = Cliente::create($c);
        }

        [$cliGaucho, $cliSuperNorte, $cliAvicola, $cliExpress, $cliVarela, $cliGonzalez, $cliRodriguez, $cliFerreyra] = $clienteObjs;

        // ── Artículos con tramos de precio ─────────────────────────────────────
        $crearArticulo = function (array $art, array $precios) use ($listaMinoristaId) {
            $a = Articulo::create(array_merge($art, [
                'modifica_stock' => true,
                'perdida'        => 3,
                'estado'         => 'A',
                'fecha_modif'    => now()->toDateString(),
            ]));
            foreach ($precios as $i => $p) {
                ArticuloPrecio::create([
                    'articulo_id'     => $a->id,
                    'lista_precio_id' => $listaMinoristaId, // tramos demo cargados en lista default
                    'cantidad_desde'  => $p[0],
                    'cantidad_hasta'  => $p[1],
                    'precio'          => $p[2],
                    'activo'          => true,
                    'orden'           => $i,
                ]);
            }
            $a->update(['precio_unitario' => $precios[0][2]]);
            return $a;
        };

        $artPolloEnteroC = $crearArticulo([
            'codigo'           => 'POL001',
            'descripcion'      => 'Pollo entero frío 2 kg (caja)',
            'presentacion'     => 'Caja x 12 unidades',
            'presentacion_caja'=> 'Caja x 12 un',
            'cantidad_caja'    => 12,
            'tipo_venta'       => 'CAJA',
            'unidad_medida'    => 'unidad',
            'rubro_id'         => $rPollo->id,
            'marca_id'         => $marcaEstrella->id,
            'alicuota_iva'     => 10.5,
            'precio_costo'     => 2800,
            'margen'           => 25,
            'stock'            => 240,
            'stock_minorista'  => 48,
            'stock_minimo'     => 24,
        ], [
            [1,   11,  3150.00],
            [12,  59,  2950.00],
            [60,  119, 2780.00],
            [120, null,2650.00],
        ]);

        $artSuprema = $crearArticulo([
            'codigo'       => 'COR001',
            'descripcion'  => 'Suprema de pollo (bandeja x 4)',
            'tipo_venta'   => 'CAJA',
            'unidad_medida'=> 'bandeja',
            'rubro_id'     => $rCortes->id,
            'marca_id'     => $marcaEstrella->id,
            'alicuota_iva' => 10.5,
            'precio_costo' => 4200,
            'margen'       => 30,
            'stock'        => 85,
            'stock_minimo' => 10,
        ], [
            [1,  5,  5600.00],
            [6, 19,  5200.00],
            [20, null,4850.00],
        ]);

        $artPataMuslo = $crearArticulo([
            'codigo'       => 'COR002',
            'descripcion'  => 'Pata muslo (bandeja x 6)',
            'tipo_venta'   => 'CAJA',
            'unidad_medida'=> 'bandeja',
            'rubro_id'     => $rCortes->id,
            'marca_id'     => $marcaSanSeb->id,
            'alicuota_iva' => 10.5,
            'precio_costo' => 2900,
            'margen'       => 28,
            'stock'        => 120,
            'stock_minimo' => 12,
        ], [
            [1,  5,  3900.00],
            [6, 23,  3600.00],
            [24, null,3350.00],
        ]);

        $artPechugas = $crearArticulo([
            'codigo'       => 'COR003',
            'descripcion'  => 'Pechuga deshuesada (kg/vac)',
            'tipo_venta'   => 'CAJA',
            'unidad_medida'=> 'paquete',
            'rubro_id'     => $rCortes->id,
            'marca_id'     => $marcaDelCampo->id,
            'alicuota_iva' => 10.5,
            'precio_costo' => 5800,
            'margen'       => 22,
            'stock'        => 60,
            'stock_minimo' => 8,
        ], [
            [1,  4,  7300.00],
            [5, 14,  6900.00],
            [15, null,6500.00],
        ]);

        $artAlaPollo = $crearArticulo([
            'codigo'       => 'COR004',
            'descripcion'  => 'Ala de pollo (bolsa x 2 kg)',
            'tipo_venta'   => 'CAJA',
            'unidad_medida'=> 'bolsa',
            'rubro_id'     => $rCortes->id,
            'marca_id'     => $marcaGranja->id,
            'alicuota_iva' => 10.5,
            'precio_costo' => 1800,
            'margen'       => 30,
            'stock'        => 95,
            'stock_minimo' => 10,
        ], [
            [1,  9,  2400.00],
            [10, 29, 2200.00],
            [30, null,2050.00],
        ]);

        $artHuevosDoc = $crearArticulo([
            'codigo'       => 'HUE001',
            'descripcion'  => 'Huevos blancos x docena',
            'tipo_venta'   => 'CAJA',
            'unidad_medida'=> 'docena',
            'rubro_id'     => $rHuevos->id,
            'marca_id'     => $marcaSinMarca->id,
            'alicuota_iva' => 0,
            'precio_costo' => 1400,
            'margen'       => 20,
            'stock'        => 320,
            'stock_minimo' => 60,
        ], [
            [1,  11,  1700.00],
            [12, 29,  1620.00],
            [30, 59,  1560.00],
            [60, null,1500.00],
        ]);

        $artHuevosMayor = $crearArticulo([
            'codigo'       => 'HUE002',
            'descripcion'  => 'Huevos blancos x mapa (30 u.)',
            'tipo_venta'   => 'CAJA',
            'unidad_medida'=> 'mapa',
            'rubro_id'     => $rHuevos->id,
            'marca_id'     => $marcaSinMarca->id,
            'art_mayorista'=> true,
            'alicuota_iva' => 0,
            'precio_costo' => 3800,
            'margen'       => 18,
            'stock'        => 210,
            'stock_minimo' => 30,
        ], [
            [1,  9,  4500.00],
            [10, 29, 4300.00],
            [30, null,4100.00],
        ]);

        $artMorci = $crearArticulo([
            'codigo'       => 'FIA001',
            'descripcion'  => 'Morcilla de pollo (pack x 2)',
            'tipo_venta'   => 'CAJA',
            'unidad_medida'=> 'pack',
            'rubro_id'     => $rFiambres->id,
            'marca_id'     => $marcaEstrella->id,
            'alicuota_iva' => 21,
            'precio_costo' => 1600,
            'margen'       => 35,
            'stock'        => 45,
            'stock_minimo' => 6,
        ], [
            [1,  5,  2200.00],
            [6, 23,  2050.00],
            [24, null,1900.00],
        ]);

        $artPolloEnteroKg = $crearArticulo([
            'codigo'       => 'POL002',
            'descripcion'  => 'Pollo entero caliente (kg)',
            'tipo_venta'   => 'PESO',
            'unidad_medida'=> 'kg',
            'rubro_id'     => $rPollo->id,
            'marca_id'     => $marcaEstrella->id,
            'alicuota_iva' => 10.5,
            'precio_costo' => 2600,
            'margen'       => 28,
            'stock'        => 850.5,
            'stock_minimo' => 100,
        ], [
            [1,     9.999,  3350.00],
            [10,    49.999, 3150.00],
            [50,    199.999,2980.00],
            [200,   null,   2820.00],
        ]);

        $artMenuden = $crearArticulo([
            'codigo'       => 'SUB001',
            'descripcion'  => 'Menudencias (kg)',
            'tipo_venta'   => 'PESO',
            'unidad_medida'=> 'kg',
            'rubro_id'     => $rSubprod->id,
            'marca_id'     => $marcaSinMarca->id,
            'alicuota_iva' => 10.5,
            'precio_costo' => 800,
            'margen'       => 40,
            'stock'        => 125.0,
            'stock_minimo' => 20,
        ], [
            [1,    9.999,  1200.00],
            [10,   49.999, 1100.00],
            [50,   null,   1000.00],
        ]);

        $artHigado = $crearArticulo([
            'codigo'       => 'SUB002',
            'descripcion'  => 'Hígado de pollo (kg)',
            'tipo_venta'   => 'PESO',
            'unidad_medida'=> 'kg',
            'rubro_id'     => $rSubprod->id,
            'marca_id'     => $marcaSinMarca->id,
            'alicuota_iva' => 10.5,
            'precio_costo' => 900,
            'margen'       => 38,
            'stock'        => 78.5,
            'stock_minimo' => 15,
        ], [
            [0.5,  4.999,  1350.00],
            [5,    19.999, 1250.00],
            [20,   null,   1150.00],
        ]);

        $artCarcasa = $crearArticulo([
            'codigo'       => 'SUB003',
            'descripcion'  => 'Carcasa de pollo (kg)',
            'tipo_venta'   => 'PESO',
            'unidad_medida'=> 'kg',
            'rubro_id'     => $rSubprod->id,
            'marca_id'     => $marcaSinMarca->id,
            'alicuota_iva' => 10.5,
            'precio_costo' => 500,
            'margen'       => 35,
            'stock'        => 200.0,
            'stock_minimo' => 30,
        ], [
            [1,    9.999,  700.00],
            [10,   49.999, 650.00],
            [50,   null,   600.00],
        ]);

        $artBalanceado = $crearArticulo([
            'codigo'       => 'BAL001',
            'descripcion'  => 'Alimento balanceado iniciador (bolsa 25 kg)',
            'tipo_venta'   => 'CAJA',
            'unidad_medida'=> 'bolsa',
            'rubro_id'     => $rBalanceado->id,
            'marca_id'     => $marcaDelCampo->id,
            'art_mayorista'=> true,
            'alicuota_iva' => 10.5,
            'precio_costo' => 18000,
            'margen'       => 15,
            'stock'        => 38,
            'stock_minimo' => 5,
        ], [
            [1,  4,  21500.00],
            [5, 19, 20800.00],
            [20, null,20000.00],
        ]);

        $articulosPrincipales = [
            $artPolloEnteroC, $artSuprema, $artPataMuslo, $artPechugas,
            $artAlaPollo, $artHuevosDoc, $artHuevosMayor, $artMorci,
            $artPolloEnteroKg, $artMenuden, $artHigado, $artCarcasa, $artBalanceado,
        ];

        // ── Cajas (últimos 30 días) ────────────────────────────────────────────
        $cajasHistorial = []; // keyed by days-ago (30..1)

        for ($d = 30; $d >= 1; $d--) {
            $fecha = Carbon::today()->subDays($d);
            $cajasHistorial[$d] = Caja::create([
                'user_id'        => $adminUser->id,
                'fecha_apertura' => $fecha->toDateString(),
                'hora_apertura'  => $fecha->copy()->setTime(8, 0)->toDateTimeString(),
                'fecha_cierre'   => $fecha->toDateString(),
                'hora_cierre'    => $fecha->copy()->setTime(18, random_int(0, 59))->toDateTimeString(),
                'saldo_inicial'  => 5000.00,
                'total_ventas'   => 0,
                'total_cobros'   => 0,
                'total_gastos'   => 0,
                'saldo_final'    => 0,
                'estado'         => 'cerrada',
            ]);
        }

        $cajaHoy = Caja::create([
            'user_id'        => $adminUser->id,
            'fecha_apertura' => today()->toDateString(),
            'hora_apertura'  => now()->setTime(8, 0)->toDateTimeString(),
            'saldo_inicial'  => 5000.00,
            'total_ventas'   => 0,
            'total_cobros'   => 0,
            'total_gastos'   => 0,
            'saldo_final'    => 0,
            'estado'         => 'abierta',
        ]);

        // ── Ventas (últimos 30 días) ───────────────────────────────────────────
        $clientes   = [$cliGaucho, $cliSuperNorte, $cliAvicola, $cliExpress, $cliVarela, $cliGonzalez, $cliRodriguez, $cliFerreyra];
        $formasPago = ['efectivo', 'transferencia', 'tarjeta', 'cuenta_corriente', 'efectivo', 'efectivo', 'tarjeta'];
        $nroVenta   = 1;

        // Track cc debts per client for recibo creation: [cliente_id => [['venta' => $v, 'saldo' => $total], ...]]
        $ccPendientesCli = [];
        $ccSaldoCli      = []; // [cliente_id => running saldo]

        for ($d = 30; $d >= 1; $d--) {
            $fecha      = Carbon::today()->subDays($d);
            $cajaDelDia = $cajasHistorial[$d];
            $cantVentas = random_int(4, 8);

            for ($v = 0; $v < $cantVentas; $v++) {
                $cliente   = $clientes[array_rand($clientes)];
                $formaPago = $formasPago[array_rand($formasPago)];

                // Consumidor final nunca usa cuenta corriente
                $esMayorista = (bool) $cliente->es_mayorista;
                if (!$esMayorista && $formaPago === 'cuenta_corriente') {
                    $formaPago = 'efectivo';
                }

                $artRandom = array_rand($articulosPrincipales, min(random_int(2, 4), count($articulosPrincipales)));
                if (!is_array($artRandom)) $artRandom = [$artRandom];

                $tipoiva = TipoIva::find($cliente->tipoiva_id);
                $letra   = ($tipoiva?->codigo_afip === '01') ? 'A' : 'B';

                $venta = Venta::create([
                    'cliente_id'        => $cliente->id,
                    'user_id'           => $adminUser->id,
                    'caja_id'           => $cajaDelDia->id,
                    'nro_venta'         => $nroVenta++,
                    'fecha'             => $fecha->toDateString(),
                    'hora'              => sprintf('%02d:%02d:00', random_int(8, 17), random_int(0, 59)),
                    'tipo_comprobante'  => 'factura',
                    'letra_comprobante' => $letra,
                    'neto'              => 0,
                    'descuento'         => 0,
                    'neto_21'           => 0,
                    'neto_10_5'         => 0,
                    'monto_iva21'       => 0,
                    'monto_iva10_5'     => 0,
                    'percepcion'        => 0,
                    'percepiva21'       => 0,
                    'percepiva10_5'     => 0,
                    'total'             => 0,
                    'saldo'             => 0,
                    'estado'            => 'P',
                ]);

                $totalVenta = 0;
                $neto21     = 0;
                $neto105    = 0;

                foreach ($artRandom as $artIdx) {
                    $art      = $articulosPrincipales[$artIdx];
                    $cantidad = $art->tipo_venta === 'PESO'
                        ? round(random_int(5, 50) + mt_rand(0, 99) / 100.0, 2)
                        : random_int(1, 24);

                    $precio   = (float) ($art->preciosActivos->first()?->precio ?? $art->precio_unitario);
                    $subtotal = round($cantidad * $precio, 2);
                    $subtIva  = round($subtotal * ($art->alicuota_iva / 100), 2);

                    DetVenta::create([
                        'venta_id'        => $venta->id,
                        'articulo_id'     => $art->id,
                        'descripcion'     => $art->descripcion,
                        'tipo_venta'      => $art->tipo_venta,
                        'cantidad'        => $cantidad,
                        'precio_unitario' => $precio,
                        'alicuota_iva'    => $art->alicuota_iva,
                        'descuento'       => 0,
                        'subtotal'        => $subtotal,
                        'subtotal_iva'    => $subtIva,
                    ]);

                    MovimientoArticulo::create([
                        'articulo_id'      => $art->id,
                        'user_id'          => $adminUser->id,
                        'fecha'            => $fecha->toDateString(),
                        'tipo'             => 'salida',
                        'cantidad'         => $cantidad,
                        'stock_anterior'   => $art->stock,
                        'stock_posterior'  => max(0, $art->stock - $cantidad),
                        'referencia_type'  => Venta::class,
                        'referencia_id'    => $venta->id,
                        'es_minorista'     => !$esMayorista,
                    ]);

                    if ((float) $art->alicuota_iva === 21.0) {
                        $neto21 += $subtotal;
                    } elseif ((float) $art->alicuota_iva === 10.5) {
                        $neto105 += $subtotal;
                    }

                    $totalVenta += $subtotal + $subtIva;
                }

                $iva21      = round($neto21 * 0.21, 2);
                $iva105     = round($neto105 * 0.105, 2);
                $totalVenta = round($neto21 + $iva21 + $neto105 + $iva105, 2);

                $percepIIBB = 0;
                if ((float) $cliente->porcentaje_iibb > 0) {
                    $percepIIBB = round(($neto21 + $neto105) * (float) $cliente->porcentaje_iibb, 2);
                }
                $totalVenta += $percepIIBB;

                $saldo   = $formaPago === 'cuenta_corriente' ? $totalVenta : 0;
                $estadoV = $formaPago === 'cuenta_corriente' ? 'P' : 'C';

                $venta->update([
                    'neto'          => $neto21 + $neto105,
                    'neto_21'       => $neto21,
                    'neto_10_5'     => $neto105,
                    'monto_iva21'   => $iva21,
                    'monto_iva10_5' => $iva105,
                    'percepcion'    => $percepIIBB,
                    'total'         => $totalVenta,
                    'saldo'         => $saldo,
                    'estado'        => $estadoV,
                ]);

                if ($formaPago !== 'cuenta_corriente') {
                    PagoVenta::create([
                        'venta_id'   => $venta->id,
                        'caja_id'    => $cajaDelDia->id,
                        'fecha'      => $fecha->toDateString(),
                        'forma_pago' => $formaPago,
                        'monto'      => $totalVenta,
                    ]);
                } else {
                    // Registrar en cuenta corriente del cliente
                    $oldSaldo = $ccSaldoCli[$cliente->id] ?? 0;
                    $newSaldo = round($oldSaldo + $totalVenta, 2);
                    CcCliente::create([
                        'cliente_id'      => $cliente->id,
                        'fecha'           => $fecha->toDateString(),
                        'concepto'        => "Factura {$letra} #{$venta->nro_venta}",
                        'debe'            => $totalVenta,
                        'haber'           => 0,
                        'saldo'           => $newSaldo,
                        'referencia_type' => Venta::class,
                        'referencia_id'   => $venta->id,
                    ]);
                    $ccSaldoCli[$cliente->id] = $newSaldo;

                    $ccPendientesCli[$cliente->id][] = ['venta' => $venta, 'saldo' => $totalVenta];
                }

                $cajaDelDia->increment('total_ventas', $totalVenta);
            }
        }

        // ── Compras (cada ~5 días) ────────────────────────────────────────────
        $comprasScript = [
            // [dias_atras, proveedor, [[articulo, cantidad]], pago_cc]
            [28, $provLaPampa, [[$artPolloEnteroKg, 500], [$artMenuden, 80]],    true],
            [23, $provNorte,   [[$artPataMuslo, 50],   [$artHigado, 30]],        true],
            [18, $provAlamos,  [[$artBalanceado, 20]],                           false],
            [13, $provLaPampa, [[$artPolloEnteroKg, 400], [$artSuprema, 30]],    true],
            [8,  $provSanJose, [[$artHuevosDoc, 120],  [$artBalanceado, 10]],    false],
            [4,  $provNorte,   [[$artPataMuslo, 60],   [$artCarcasa, 100]],      true],
        ];

        $nroFactura  = 1;
        $ccSaldoProv = []; // [proveedor_id => running saldo]
        $ccPendientesProv = []; // [proveedor_id => [['compra' => $c, 'saldo' => $total], ...]]

        foreach ($comprasScript as [$diasAtras, $proveedor, $lineas, $esCc]) {
            $fecha  = Carbon::today()->subDays($diasAtras);
            $caja   = $cajasHistorial[$diasAtras];
            $neto21 = 0;
            $neto105 = 0;

            $compra = Compra::create([
                'proveedor_id' => $proveedor->id,
                'user_id'      => $adminUser->id,
                'caja_id'      => $caja->id,
                'fecha'        => $fecha->toDateString(),
                'nro_factura'  => 'A-0001-' . str_pad($nroFactura++, 8, '0', STR_PAD_LEFT),
                'letra'        => 'A',
                'neto'         => 0,
                'neto_21'      => 0,
                'neto_10_5'    => 0,
                'monto_iva21'  => 0,
                'monto_iva10_5'=> 0,
                'total'        => 0,
                'saldo'        => 0,
                'estado'       => 'P',
            ]);

            foreach ($lineas as [$art, $cantidad]) {
                $precio   = (float) $art->precio_costo;
                $subtotal = round($cantidad * $precio, 2);
                $subtIva  = round($subtotal * ($art->alicuota_iva / 100), 2);

                DetCompra::create([
                    'compra_id'       => $compra->id,
                    'articulo_id'     => $art->id,
                    'cantidad'        => $cantidad,
                    'precio_unitario' => $precio,
                    'alicuota_iva'    => $art->alicuota_iva,
                    'subtotal'        => $subtotal,
                    'subtotal_iva'    => $subtIva,
                ]);

                MovimientoArticulo::create([
                    'articulo_id'     => $art->id,
                    'user_id'         => $adminUser->id,
                    'fecha'           => $fecha->toDateString(),
                    'tipo'            => 'entrada',
                    'cantidad'        => $cantidad,
                    'stock_anterior'  => $art->stock,
                    'stock_posterior' => $art->stock + $cantidad,
                    'referencia_type' => Compra::class,
                    'referencia_id'   => $compra->id,
                    'es_minorista'    => false,
                ]);

                if ((float) $art->alicuota_iva === 21.0) {
                    $neto21 += $subtotal;
                } elseif ((float) $art->alicuota_iva === 10.5) {
                    $neto105 += $subtotal;
                }
            }

            $iva21       = round($neto21 * 0.21, 2);
            $iva105      = round($neto105 * 0.105, 2);
            $totalCompra = round($neto21 + $iva21 + $neto105 + $iva105, 2);
            $saldoCompra = $esCc ? $totalCompra : 0;
            $estadoC     = $esCc ? 'P' : 'C';

            $compra->update([
                'neto'          => $neto21 + $neto105,
                'neto_21'       => $neto21,
                'neto_10_5'     => $neto105,
                'monto_iva21'   => $iva21,
                'monto_iva10_5' => $iva105,
                'total'         => $totalCompra,
                'saldo'         => $saldoCompra,
                'estado'        => $estadoC,
            ]);

            if ($esCc) {
                $oldSaldo = $ccSaldoProv[$proveedor->id] ?? 0;
                $newSaldo = round($oldSaldo + $totalCompra, 2);
                CcProveedor::create([
                    'proveedor_id'    => $proveedor->id,
                    'fecha'           => $fecha->toDateString(),
                    'concepto'        => "Factura A #{$compra->nro_factura}",
                    'debe'            => 0,
                    'haber'           => $totalCompra,
                    'saldo'           => $newSaldo,
                    'referencia_type' => Compra::class,
                    'referencia_id'   => $compra->id,
                ]);
                $ccSaldoProv[$proveedor->id] = $newSaldo;
                $ccPendientesProv[$proveedor->id][] = ['compra' => $compra, 'saldo' => $totalCompra];
            }

            $caja->increment('total_gastos', $totalCompra);
        }

        // ── Recibos de cobro (cancelan cc de clientes) ────────────────────────
        $nroRecibo = 1;

        // Crear 2 rondas de cobros: día 18 y día 7
        foreach ([18, 7] as $diasAtras) {
            $fechaRecibo = Carbon::today()->subDays($diasAtras);
            $cajaRecibo  = $cajasHistorial[$diasAtras];

            foreach ($ccPendientesCli as $clienteId => $ventas) {
                if (empty($ventas)) continue;

                // Cobrar la mitad de las ventas pendientes de ese cliente hasta ese día
                $ventasAplicar = array_filter($ventas, function ($item) use ($fechaRecibo) {
                    return Carbon::parse($item['venta']->fecha)->lt($fechaRecibo);
                });
                if (empty($ventasAplicar)) continue;

                // Pagar solo las más antiguas (60% del total pendiente)
                $ventasAplicar = array_slice(array_values($ventasAplicar), 0, max(1, intdiv(count($ventasAplicar), 2)));
                $montoTotal = round(array_sum(array_column($ventasAplicar, 'saldo')), 2);

                if ($montoTotal <= 0) continue;

                $formasPagoRecibo = ['transferencia', 'cheque', 'efectivo'];
                $recibo = Recibo::create([
                    'cliente_id' => $clienteId,
                    'caja_id'    => $cajaRecibo->id,
                    'user_id'    => $adminUser->id,
                    'nro_recibo' => $nroRecibo++,
                    'fecha'      => $fechaRecibo->toDateString(),
                    'total'      => $montoTotal,
                    'forma_pago' => $formasPagoRecibo[array_rand($formasPagoRecibo)],
                    'referencia' => 'TRF-' . str_pad($nroRecibo, 6, '0', STR_PAD_LEFT),
                    'estado'     => 'C',
                ]);

                $oldSaldo = $ccSaldoCli[$clienteId] ?? 0;
                $newSaldo = round($oldSaldo - $montoTotal, 2);
                CcCliente::create([
                    'cliente_id'      => $clienteId,
                    'fecha'           => $fechaRecibo->toDateString(),
                    'concepto'        => "Recibo #{$recibo->nro_recibo}",
                    'debe'            => 0,
                    'haber'           => $montoTotal,
                    'saldo'           => $newSaldo,
                    'referencia_type' => Recibo::class,
                    'referencia_id'   => $recibo->id,
                ]);
                $ccSaldoCli[$clienteId] = $newSaldo;

                foreach ($ventasAplicar as $item) {
                    ReciboDetalle::create([
                        'recibo_id'      => $recibo->id,
                        'venta_id'       => $item['venta']->id,
                        'monto_aplicado' => $item['saldo'],
                    ]);
                    // Mark venta as cobrada
                    $item['venta']->update(['saldo' => 0, 'estado' => 'C']);
                }

                $cajaRecibo->increment('total_cobros', $montoTotal);

                // Remove applied ventas from pending list
                $ccPendientesCli[$clienteId] = array_values(array_diff_key(
                    $ccPendientesCli[$clienteId],
                    array_flip(array_keys($ventasAplicar))
                ));
            }
        }

        // ── Órdenes de Pago (cancelan cc a proveedores) ───────────────────────
        $nroOrden = 1;

        foreach ([20, 6] as $diasAtras) {
            $fechaOrden = Carbon::today()->subDays($diasAtras);
            $cajaOrden  = $cajasHistorial[$diasAtras];

            foreach ($ccPendientesProv as $provId => $compras) {
                if (empty($compras)) continue;

                $comprasAplicar = array_filter($compras, function ($item) use ($fechaOrden) {
                    return Carbon::parse($item['compra']->fecha)->lt($fechaOrden);
                });
                if (empty($comprasAplicar)) continue;

                $comprasAplicar = array_slice(array_values($comprasAplicar), 0, max(1, intdiv(count($comprasAplicar), 2)));
                $montoTotal = round(array_sum(array_column($comprasAplicar, 'saldo')), 2);

                if ($montoTotal <= 0) continue;

                $formasPagoOp = ['transferencia', 'cheque'];
                $orden = OrdenPago::create([
                    'proveedor_id' => $provId,
                    'caja_id'      => $cajaOrden->id,
                    'user_id'      => $adminUser->id,
                    'nro_orden'    => $nroOrden++,
                    'fecha'        => $fechaOrden->toDateString(),
                    'total'        => $montoTotal,
                    'forma_pago'   => $formasPagoOp[array_rand($formasPagoOp)],
                    'referencia'   => 'CHQ-' . str_pad($nroOrden, 6, '0', STR_PAD_LEFT),
                    'estado'       => 'C',
                ]);

                $oldSaldo = $ccSaldoProv[$provId] ?? 0;
                $newSaldo = round($oldSaldo - $montoTotal, 2);
                CcProveedor::create([
                    'proveedor_id'    => $provId,
                    'fecha'           => $fechaOrden->toDateString(),
                    'concepto'        => "Orden de Pago #{$orden->nro_orden}",
                    'debe'            => $montoTotal,
                    'haber'           => 0,
                    'saldo'           => $newSaldo,
                    'referencia_type' => OrdenPago::class,
                    'referencia_id'   => $orden->id,
                ]);
                $ccSaldoProv[$provId] = $newSaldo;

                foreach ($comprasAplicar as $item) {
                    DetalleOrdenPago::create([
                        'orden_pago_id' => $orden->id,
                        'compra_id'     => $item['compra']->id,
                        'monto'         => $item['saldo'],
                    ]);
                    $item['compra']->update(['saldo' => 0, 'estado' => 'C']);
                }

                $cajaOrden->increment('total_gastos', $montoTotal);

                $ccPendientesProv[$provId] = array_values(array_diff_key(
                    $ccPendientesProv[$provId],
                    array_flip(array_keys($comprasAplicar))
                ));
            }
        }

        // ── Producciones ──────────────────────────────────────────────────────
        $nroProduccion = 1;
        for ($d = 29; $d >= 2; $d -= 3) {
            $fecha          = Carbon::today()->subDays($d);
            $cantEntrada    = round(random_int(150, 400) + mt_rand(0, 99) / 100.0, 2);
            $mermaPorc      = round(mt_rand(0, 99) / 100.0 * 5 + 2, 3);
            $cantSalida     = round($cantEntrada * (1 - $mermaPorc / 100), 2);
            $merma          = round($cantEntrada - $cantSalida, 2);

            Produccion::create([
                'empleado_id'      => $empDiego->id,
                'user_id'          => $adminUser->id,
                'articulo_id'      => $artPolloEnteroKg->id,
                'nro_produccion'   => $nroProduccion++,
                'fecha'            => $fecha->toDateString(),
                'cantidad_entrada' => $cantEntrada,
                'cantidad_salida'  => $cantSalida,
                'merma'            => $merma,
                'merma_porcentaje' => $mermaPorc,
                'estado'           => 'cerrada',
            ]);

            MovimientoArticulo::create([
                'articulo_id'     => $artPolloEnteroKg->id,
                'user_id'         => $adminUser->id,
                'fecha'           => $fecha->toDateString(),
                'tipo'            => 'produccion',
                'cantidad'        => $cantEntrada,
                'stock_anterior'  => $artPolloEnteroKg->stock,
                'stock_posterior' => max(0, $artPolloEnteroKg->stock - $cantEntrada),
                'es_minorista'    => false,
                'observacion'     => "Producción #{$nroProduccion}",
            ]);
        }

        // ── Remitos ──────────────────────────────────────────────────────────
        $nroRemito = 1;
        for ($d = 28; $d >= 1; $d -= 3) {
            $fecha = Carbon::today()->subDays($d);
            Remito::create([
                'empleado_id' => $empJuan->id,
                'user_id'     => $adminUser->id,
                'nro_remito'  => $nroRemito++,
                'fecha'       => $fecha->toDateString(),
                'estado'      => 'cerrado',
                'total_peso'  => round(random_int(80, 300) + mt_rand(0, 99) / 100.0, 3),
                'total_cajas' => random_int(8, 40),
            ]);
        }

        // ── Pesadas ──────────────────────────────────────────────────────────
        $nroPesada = 1;
        $artsPesar  = [$artPolloEnteroKg, $artCarcasa, $artMenuden, $artHigado];

        for ($d = 28; $d >= 1; $d -= 7) {
            $fecha = Carbon::today()->subDays($d);
            foreach ($artsPesar as $art) {
                $stockSistema = (float) $art->stock;
                $diferencia   = round((mt_rand(0, 99) / 100.0 * 10) - 5, 2);
                $stockReal    = max(0.0, $stockSistema + $diferencia);

                Pesada::create([
                    'user_id'          => $adminUser->id,
                    'articulo_id'      => $art->id,
                    'nro_pesada'       => $nroPesada++,
                    'fecha'            => $fecha->toDateString(),
                    'stock_sistema'    => $stockSistema,
                    'stock_real'       => $stockReal,
                    'diferencia'       => round($stockReal - $stockSistema, 2),
                    'merma_porcentaje' => $stockSistema > 0
                        ? round(abs($diferencia) / $stockSistema * 100, 3)
                        : 0,
                    'estado'           => 'aplicada',
                ]);
            }
        }

        // ── Descartes ────────────────────────────────────────────────────────
        $motivosDescarte = ['vencimiento', 'daño', 'transporte', 'contaminacion', 'otro'];
        $artsDescarte    = [$artPolloEnteroKg, $artPataMuslo, $artSuprema, $artHigado, $artMenuden];

        for ($d = 26; $d >= 4; $d -= 6) {
            $fecha    = Carbon::today()->subDays($d);
            $art      = $artsDescarte[($d % count($artsDescarte))];
            $cantidad = round(random_int(2, 25) + mt_rand(0, 99) / 100.0, 2);
            $motivo   = $motivosDescarte[array_rand($motivosDescarte)];

            Descarte::create([
                'empleado_id' => $empDiego->id,
                'user_id'     => $adminUser->id,
                'articulo_id' => $art->id,
                'fecha'       => $fecha->toDateString(),
                'cantidad'    => $cantidad,
                'motivo'      => $motivo,
            ]);

            MovimientoArticulo::create([
                'articulo_id'     => $art->id,
                'user_id'         => $adminUser->id,
                'fecha'           => $fecha->toDateString(),
                'tipo'            => 'descarte',
                'cantidad'        => $cantidad,
                'stock_anterior'  => $art->stock,
                'stock_posterior' => max(0, $art->stock - $cantidad),
                'es_minorista'    => false,
                'observacion'     => "Descarte: {$motivo}",
            ]);
        }

        // ── Actualizar saldo_final de cajas históricas ────────────────────────
        foreach ($cajasHistorial as $caja) {
            $caja->refresh();
            $caja->update([
                'saldo_final' => round(
                    $caja->saldo_inicial + $caja->total_ventas + $caja->total_cobros - $caja->total_gastos,
                    2
                ),
            ]);
        }

        // ── Actualizar saldos de clientes y proveedores ───────────────────────
        foreach ($ccSaldoCli as $clienteId => $saldo) {
            Cliente::where('id', $clienteId)->update(['saldo' => -$saldo]);
        }
        foreach ($ccSaldoProv as $provId => $saldo) {
            Proveedor::where('id', $provId)->update(['saldo' => -$saldo]);
        }

        // ── Resumen ───────────────────────────────────────────────────────────
        $this->command->info('✓ FakeDataSeeder completado:');
        $this->command->info('  - ' . count($rubros) . ' rubros');
        $this->command->info('  - ' . count($proveedores) . ' proveedores');
        $this->command->info('  - ' . count($marcas) . ' marcas');
        $this->command->info('  - ' . count($empleados) . ' empleados');
        $this->command->info('  - ' . count($clientesData) . ' clientes');
        $this->command->info('  - ' . count($articulosPrincipales) . ' artículos con tramos de precio');
        $this->command->info('  - 1 caja abierta (hoy) + 30 cajas cerradas (historial)');
        $this->command->info('  - ' . ($nroVenta - 1) . ' ventas (últimos 30 días)');
        $this->command->info('  - ' . count($comprasScript) . ' compras a proveedores');
        $this->command->info('  - ' . ($nroRecibo - 1) . ' recibos de cobro');
        $this->command->info('  - ' . ($nroOrden - 1) . ' órdenes de pago');
        $this->command->info('  - ' . ($nroProduccion - 1) . ' producciones');
        $this->command->info('  - ' . ($nroRemito - 1) . ' remitos');
        $this->command->info('  - ' . ($nroPesada - 1) . ' pesadas');
    }
}
