<?php

namespace App\Console\Commands;

use App\Models\Articulo;
use App\Models\ArticuloPrecio;
use App\Models\CategoriaGasto;
use App\Models\Cliente;
use App\Models\ListaPrecio;
use App\Models\Proveedor;
use App\Models\Rubro;
use App\Models\Tipoiva;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportarLegacy extends Command
{
    protected $signature = 'centrogoma:importar
                            {tabla? : categorias|productos|clientes|proveedores|plan_cuenta (omitir = todo)}
                            {--dry-run : Mostrar qué se haría sin guardar nada}
                            {--force : Sobrescribir registros ya importados}';

    protected $description = 'Importa datos maestros desde la BD legacy Delphi (centro_goma)';

    private bool $dryRun  = false;
    private bool $force   = false;
    private int  $created = 0;
    private int  $updated = 0;
    private int  $skipped = 0;
    private array $errors = [];

    /** Mapeo id_tipoiva legacy → id en tabla tipoiva nueva */
    private array $tipoivaMap = [];

    /** Mapeo id_categoria legacy → id en tabla rubros nueva */
    private array $rubroMap = [];

    /** Mapeo id_localidad legacy → texto de localidad */
    private array $localidadMap = [];

    public function handle(): int
    {
        $this->dryRun = $this->option('dry-run');
        $this->force  = $this->option('force');
        $tabla        = $this->argument('tabla');

        if ($this->dryRun) {
            $this->warn('⚠  MODO DRY-RUN — no se guardará nada');
        }

        $this->construirMaps();

        $tablas = $tabla
            ? [$tabla]
            : ['categorias', 'productos', 'clientes', 'proveedores', 'plan_cuenta'];

        foreach ($tablas as $t) {
            $this->resetContadores();
            match ($t) {
                'categorias'  => $this->importarCategorias(),
                'productos'   => $this->importarProductos(),
                'clientes'    => $this->importarClientes(),
                'proveedores' => $this->importarProveedores(),
                'plan_cuenta' => $this->importarPlanCuenta(),
                default       => $this->error("Tabla desconocida: {$t}"),
            };
            $this->resumen($t);
        }

        if (! empty($this->errors)) {
            $this->newLine();
            $this->warn('Errores encontrados:');
            foreach ($this->errors as $e) {
                $this->line("  • {$e}");
            }
        }

        return self::SUCCESS;
    }

    // ─────────────────────────────────────────────────────────────────
    //  PLAN_CUENTA → CATEGORIAS_GASTO
    // ─────────────────────────────────────────────────────────────────

    /**
     * Cuentas de `plan_cuenta` que en Delphi se usaban para movimientos de caja que NO son
     * gastos. En la app nueva tienen su propio circuito, así que no se importan:
     * 25 APORTE DE SOCIO (ajuste ingreso), 26 CHEQUES COBRADOS (ChequeService),
     * 27 PROVEEDORES (Órdenes de Pago), 28 DEPÓSITO BANCARIO (transferencia entre cajas).
     */
    private const PLAN_CUENTA_NO_GASTO = [25, 26, 27, 28];

    private function importarPlanCuenta(): void
    {
        $this->info('Importando plan_cuenta → categorias_gasto...');

        $rows = DB::connection('centro_goma_legacy')
            ->table('plan_cuenta')
            ->orderBy('id_plan')
            ->get();

        foreach ($rows as $plan) {
            $nombre = trim((string) $plan->plan_cuenta);
            if ($nombre === '' || in_array((int) $plan->id_plan, self::PLAN_CUENTA_NO_GASTO, true)) {
                $this->skipped++;
                continue;
            }

            $exists = CategoriaGasto::where('legacy_id', $plan->id_plan)->first();
            if ($exists && ! $this->force) {
                $this->skipped++;
                continue;
            }

            if ($this->dryRun) {
                $this->line("  [DRY] Categoría gasto: {$nombre} (legacy #{$plan->id_plan})");
                $this->created++;
                continue;
            }

            if ($exists) {
                $exists->update(['nombre' => $nombre]);
                $this->updated++;
            } else {
                // Si ya se creó a mano con el mismo nombre, se vincula en vez de duplicar.
                $porNombre = CategoriaGasto::whereNull('legacy_id')->where('nombre', $nombre)->first();
                if ($porNombre) {
                    $porNombre->update(['legacy_id' => $plan->id_plan]);
                    $this->updated++;
                } else {
                    CategoriaGasto::create(['nombre' => $nombre, 'activo' => true, 'legacy_id' => $plan->id_plan]);
                    $this->created++;
                }
            }
        }
    }

    // ─────────────────────────────────────────────────────────────────
    //  CATEGORIAS → RUBROS
    // ─────────────────────────────────────────────────────────────────

    private function importarCategorias(): void
    {
        $this->info('Importando categorias → rubros...');

        $rows = DB::connection('centro_goma_legacy')
            ->table('categoria')
            ->orderBy('id_categoria')
            ->get();

        foreach ($rows as $cat) {
            $descripcion = trim($cat->descripcion);
            if (empty($descripcion)) {
                $this->skipped++;
                continue;
            }

            $exists = Rubro::where('legacy_id', $cat->id_categoria)->first();

            if ($exists && ! $this->force) {
                $this->skipped++;
                continue;
            }

            $data = [
                'descripcion' => $descripcion,
                'estado'      => (strtoupper(trim($cat->estado)) === 'A' || trim($cat->estado) === '') ? 'A' : 'I',
            ];

            if (! $this->dryRun) {
                if ($exists) {
                    $exists->update($data);
                    $this->updated++;
                } else {
                    Rubro::create(array_merge($data, ['legacy_id' => $cat->id_categoria]));
                    $this->created++;
                }
            } else {
                $this->line("  [DRY] Rubro: {$descripcion} (legacy #{$cat->id_categoria})");
                $this->created++;
            }
        }

        // Reconstruir mapa después de importar
        $this->construirRubroMap();
    }

    // ─────────────────────────────────────────────────────────────────
    //  PRODUCTOS → ARTICULOS + ARTICULO_PRECIOS
    // ─────────────────────────────────────────────────────────────────

    private function importarProductos(): void
    {
        $this->info('Importando productos → artículos...');

        if (empty($this->rubroMap)) {
            $this->construirRubroMap();
        }

        $listaMinorista = ListaPrecio::where('es_default', true)->first();
        if (! $listaMinorista) {
            $this->error('No se encontró lista de precios default (Minorista). Correr seeders primero.');
            return;
        }

        $rows = DB::connection('centro_goma_legacy')
            ->table('producto')
            ->orderBy('id_producto')
            ->get();

        $bar = $this->output->createProgressBar($rows->count());
        $bar->start();

        foreach ($rows as $prod) {
            $bar->advance();

            $exists = Articulo::where('legacy_id', $prod->id_producto)->first();

            if ($exists && ! $this->force) {
                $this->skipped++;
                continue;
            }

            $descripcion = trim($prod->descripcion);
            $codigo      = trim($prod->codigo);
            $iva         = in_array((int) $prod->iva, [0, 10, 21]) ? (float) $prod->iva : 21.0;
            $rubroId     = $this->rubroMap[$prod->id_categoria] ?? null;

            // Los precios en Delphi son FINALES (con IVA) → convertir a neto
            $precioFinal = (float) $prod->venta;
            $precioNeto  = $iva > 0
                ? round($precioFinal / (1 + $iva / 100), 4)
                : $precioFinal;

            $costo       = (float) $prod->costo;
            $stock       = $prod->stock !== null ? (float) $prod->stock : 0;
            $stockMin    = $prod->stock_minimo !== null ? (float) $prod->stock_minimo : 0;
            $estado      = strtoupper(trim($prod->estado ?: 'A'));

            $data = [
                'codigo'          => $codigo ?: ('LEG-' . $prod->id_producto),
                'descripcion'     => $descripcion,
                'rubro_id'        => $rubroId,
                'alicuota_iva'    => $iva,
                'precio_costo'    => $costo,
                'precio_unitario' => $precioNeto,
                'stock'           => $stock,
                'stock_minimo'    => $stockMin,
                'stock_inicial'   => $stock,
                'margen'          => (float) ($prod->margen ?? 0),
                'unidad_medida'   => 'unidad',
                'modifica_stock'  => true,
                'estado'          => in_array($estado, ['A', 'I']) ? $estado : 'A',
            ];

            if (! $this->dryRun) {
                try {
                    if ($exists) {
                        $exists->update($data);
                        // Actualizar precio en lista Minorista si existe tramo
                        ArticuloPrecio::where('articulo_id', $exists->id)
                            ->where('lista_precio_id', $listaMinorista->id)
                            ->update(['precio' => $precioNeto]);
                        $this->updated++;
                    } else {
                        $articulo = Articulo::create(array_merge($data, ['legacy_id' => $prod->id_producto]));

                        // Crear tramo único en lista Minorista
                        ArticuloPrecio::create([
                            'articulo_id'    => $articulo->id,
                            'lista_precio_id'=> $listaMinorista->id,
                            'cantidad_desde' => 1,
                            'cantidad_hasta' => null,
                            'precio'         => $precioNeto,
                            'unidad_tramo'   => 'unidad',
                            'activo'         => true,
                            'orden'          => 0,
                        ]);

                        $this->created++;
                    }
                } catch (\Throwable $e) {
                    $this->errors[] = "Producto #{$prod->id_producto} ({$codigo}): " . $e->getMessage();
                }
            } else {
                $this->line("  [DRY] Artículo: {$codigo} — {$descripcion} | precio neto: {$precioNeto} | IVA: {$iva}%");
                $this->created++;
            }
        }

        $bar->finish();
        $this->newLine();
    }

    // ─────────────────────────────────────────────────────────────────
    //  CLIENTES → CLIENTES
    // ─────────────────────────────────────────────────────────────────

    private function importarClientes(): void
    {
        $this->info('Importando clientes...');

        $rows = DB::connection('centro_goma_legacy')
            ->table('cliente')
            ->orderBy('id_cliente')
            ->get();

        $bar = $this->output->createProgressBar($rows->count());
        $bar->start();

        foreach ($rows as $cli) {
            $bar->advance();

            $exists = Cliente::where('legacy_id', $cli->id_cliente)->first();

            if ($exists && ! $this->force) {
                $this->skipped++;
                continue;
            }

            $tipoivaId = $this->tipoivaMap[$cli->id_tipoiva ?? 5] ?? $this->tipoivaMap[5];
            $localidad = $this->localidadMap[$cli->id_localidad ?? 0] ?? null;

            $cuit = trim($cli->cuit_dni ?? '');
            // Sanear CUITs inválidos del legacy:
            // - vacío o muy corto
            // - todos ceros (placeholder CF, en cualquier longitud)
            // - todos el mismo caracter (ej: IIIIIII)
            // - longitud > 13 (CUIT máximo con guiones: XX-XXXXXXXX-X)
            if (
                strlen($cuit) < 7 ||
                strlen($cuit) > 13 ||
                preg_match('/^(.)\1+$/', $cuit)  // todos el mismo char
            ) {
                $cuit = null;
            }

            $data = [
                'razon_social'  => trim($cli->razon_social ?? $cli->nombre ?? 'Sin nombre'),
                'cuit'          => $cuit,
                'telefono'      => trim($cli->telefono ?? ''),
                'celular'       => trim($cli->movil ?? ''),
                'email'         => trim($cli->mail ?? ''),
                'domicilio'     => trim($cli->domicilio ?? ''),
                'localidad'     => $localidad,
                'tipoiva_id'    => $tipoivaId,
                'estado'        => strtoupper(trim($cli->estado ?: 'A')),
                'saldo'         => 0,
            ];

            if (! $this->dryRun) {
                try {
                    if ($exists) {
                        $exists->update($data);
                        $this->updated++;
                    } else {
                        Cliente::create(array_merge($data, ['legacy_id' => $cli->id_cliente]));
                        $this->created++;
                    }
                } catch (\Throwable $e) {
                    $this->errors[] = "Cliente #{$cli->id_cliente}: " . $e->getMessage();
                }
            } else {
                $this->line("  [DRY] Cliente: {$data['razon_social']} | CUIT: {$cuit}");
                $this->created++;
            }
        }

        $bar->finish();
        $this->newLine();
    }

    // ─────────────────────────────────────────────────────────────────
    //  PROVEEDORES → PROVEEDORES
    // ─────────────────────────────────────────────────────────────────

    private function importarProveedores(): void
    {
        $this->info('Importando proveedores...');

        $rows = DB::connection('centro_goma_legacy')
            ->table('proveedor')
            ->orderBy('id_proveedor')
            ->get();

        foreach ($rows as $prov) {
            $exists = Proveedor::where('legacy_id', $prov->id_proveedor)->first();

            if ($exists && ! $this->force) {
                $this->skipped++;
                continue;
            }

            $cuit = trim($prov->cuit ?? '');
            if (empty($cuit) || strlen($cuit) < 7) {
                $cuit = null;
            }

            $data = [
                'razon_social'  => trim($prov->razon_social),
                'cuit'          => $cuit,
                'telefono'      => trim($prov->tel_fijo ?? ''),
                'celular'       => trim($prov->tel_movil ?? ''),
                'email'         => trim($prov->mail ?? ''),
                'domicilio'     => trim($prov->domicilio ?? ''),
                'estado'        => strtoupper(trim($prov->estado ?: 'A')),
                'saldo'         => 0,
            ];

            if (! $this->dryRun) {
                try {
                    if ($exists) {
                        $exists->update($data);
                        $this->updated++;
                    } else {
                        Proveedor::create(array_merge($data, ['legacy_id' => $prov->id_proveedor]));
                        $this->created++;
                    }
                } catch (\Illuminate\Database\QueryException $e) {
                    // CUIT duplicado en legacy → guardar sin CUIT para no perder el registro
                    if ($e->errorInfo[1] === 1062 && str_contains($e->getMessage(), 'cuit')) {
                        try {
                            $dataFix = array_merge($data, ['cuit' => null]);
                            if ($exists) {
                                $exists->update($dataFix);
                                $this->updated++;
                            } else {
                                Proveedor::create(array_merge($dataFix, ['legacy_id' => $prov->id_proveedor]));
                                $this->created++;
                            }
                            $this->warn("  Proveedor #{$prov->id_proveedor} ({$data['razon_social']}): CUIT duplicado → guardado sin CUIT.");
                        } catch (\Throwable $e2) {
                            $this->errors[] = "Proveedor #{$prov->id_proveedor}: " . $e2->getMessage();
                        }
                    } else {
                        $this->errors[] = "Proveedor #{$prov->id_proveedor}: " . $e->getMessage();
                    }
                } catch (\Throwable $e) {
                    $this->errors[] = "Proveedor #{$prov->id_proveedor}: " . $e->getMessage();
                }
            } else {
                $this->line("  [DRY] Proveedor: {$data['razon_social']} | CUIT: {$cuit}");
                $this->created++;
            }
        }
    }

    // ─────────────────────────────────────────────────────────────────
    //  HELPERS
    // ─────────────────────────────────────────────────────────────────

    private function construirMaps(): void
    {
        $this->construirTipoivaMap();
        $this->construirRubroMap();
        $this->construirLocalidadMap();
    }

    private function construirTipoivaMap(): void
    {
        // Mapeo: legacy id_tipoiva → id en tabla tipoiva de la nueva BD
        // La tabla nueva tiene los mismos IDs por coincidencia (1=RI, 5=CF, etc.)
        // Se reconstruye dinámicamente por codigo_afip para ser robusto.
        $legacy = [
            1 => '01', // RI
            2 => '02', // RNI
            4 => '04', // Exento (legacy usa 4, skipping 3)
            5 => '05', // CF
            6 => '06', // Monotributo
        ];

        foreach ($legacy as $legacyId => $codigoAfip) {
            $tipoiva = Tipoiva::where('codigo_afip', $codigoAfip)->first();
            if ($tipoiva) {
                $this->tipoivaMap[$legacyId] = $tipoiva->id;
            }
        }

        // Fallback: si no existe mapeo, usar Consumidor Final
        $cf = Tipoiva::where('codigo_afip', '05')->first();
        if ($cf && ! isset($this->tipoivaMap[5])) {
            $this->tipoivaMap[5] = $cf->id;
        }
    }

    private function construirRubroMap(): void
    {
        $this->rubroMap = Rubro::whereNotNull('legacy_id')
            ->pluck('id', 'legacy_id')
            ->toArray();
    }

    private function construirLocalidadMap(): void
    {
        $this->localidadMap = DB::connection('centro_goma_legacy')
            ->table('localidades')
            ->pluck('localidad', 'id_localidad')
            ->map(fn ($v) => trim($v))
            ->toArray();
    }

    private function resetContadores(): void
    {
        $this->created = 0;
        $this->updated = 0;
        $this->skipped = 0;
    }

    private function resumen(string $tabla): void
    {
        $modo = $this->dryRun ? ' [DRY-RUN]' : '';
        $this->newLine();
        $this->line(sprintf(
            '<info>%s%s</info> — Creados: <comment>%d</comment> | Actualizados: <comment>%d</comment> | Omitidos: <comment>%d</comment>',
            strtoupper($tabla), $modo,
            $this->created, $this->updated, $this->skipped
        ));
    }
}
