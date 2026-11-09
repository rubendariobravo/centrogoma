<?php

namespace App\Http\Controllers;

use App\Exports\TablaExport;
use App\Models\VariableEntorno;
use App\Services\CuentaCorriente\ResumenCuenta;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Resumen de cuenta corriente de un cliente/proveedor y listado de saldos con comprobantes pendientes,
 * en PDF (para imprimir o mandar) o Excel. `{tipo}` = clientes | proveedores.
 */
class CuentaCorrienteController extends Controller
{
    public function resumen(Request $request, string $tipo, int $id)
    {
        $data = $request->validate([
            'desde'   => 'nullable|date',
            'hasta'   => 'nullable|date|after_or_equal:desde',
            'formato' => 'nullable|in:pdf,xlsx',
        ]);

        $rc       = new ResumenCuenta($tipo);
        $cuenta   = $rc->cuenta($id);
        $desde    = $data['desde'] ?? null;
        $hasta    = $data['hasta'] ?? null;
        $mov      = $rc->movimientos($id, $desde, $hasta);
        $pend     = $rc->pendientes([$id]);
        $archivo  = 'cuenta-corriente-' . Str::slug($cuenta->razon_social) . '-' . now()->format('Ymd');

        if (($data['formato'] ?? 'pdf') === 'xlsx') {
            $filas = [[$cuenta->razon_social . ($cuenta->cuit ? " — CUIT {$cuenta->cuit}" : '')],
                ['Período: ' . $this->periodo($desde, $hasta)], [],
                ['Fecha', 'Concepto', 'Debe', 'Haber', 'Saldo']];
            $negritas = [1, 4];
            if ($desde) {
                $filas[] = [null, 'Saldo anterior', null, null, $mov['saldo_anterior']];
            }
            foreach ($mov['movimientos'] as $m) {
                $filas[] = [$m['fecha']->format('d/m/Y'), $m['concepto'], $m['debe'] ?: null, $m['haber'] ?: null, $m['saldo']];
            }
            $filas[] = [null, 'Totales', $mov['debe'], $mov['haber'], $mov['saldo']];
            $negritas[] = count($filas);

            if ($pend->isNotEmpty()) {
                $filas[] = [];
                $filas[] = ['Comprobantes pendientes'];
                $filas[] = ['Fecha', 'Comprobante', 'Total', 'Saldo', 'Días'];
                array_push($negritas, count($filas) - 1, count($filas));
                foreach ($pend as $p) {
                    $filas[] = [$p['fecha']->format('d/m/Y'), $p['comprobante'], $p['total'], $p['saldo'], $p['dias']];
                }
            }

            return Excel::download(new TablaExport($filas, 'Cuenta corriente', $negritas), "{$archivo}.xlsx");
        }

        return Pdf::loadView('cuenta-corriente.resumen', [
            'esClientes' => $rc->esClientes(),
            'cuenta'     => $cuenta,
            'periodo'    => $this->periodo($desde, $hasta),
            'desde'      => $desde,
            'mov'        => $mov,
            'pendientes' => $pend,
            'empresa'    => $this->empresa(),
        ])->setPaper('a4')->stream("{$archivo}.pdf");
    }

    public function saldos(Request $request, string $tipo)
    {
        $data = $request->validate([
            'filtro'  => 'nullable|in:deudores,a_favor,todos',
            'formato' => 'nullable|in:pdf,xlsx',
        ]);

        $rc      = new ResumenCuenta($tipo);
        $filtro  = $data['filtro'] ?? 'deudores';
        $cuentas = $rc->saldos($filtro);
        $titulo  = $rc->esClientes() ? 'Saldos de clientes' : 'Saldos de proveedores';
        $archivo = Str::slug($titulo) . '-' . now()->format('Ymd');

        if (($data['formato'] ?? 'pdf') === 'xlsx') {
            $filas = [[$titulo . ' al ' . now()->format('d/m/Y')], [],
                [$rc->esClientes() ? 'Cliente' : 'Proveedor', 'CUIT', 'Teléfono', 'Saldo', 'Comprobante', 'Fecha', 'Total', 'Pendiente', 'Días']];
            foreach ($cuentas as $c) {
                if ($c['pendientes']->isEmpty()) {
                    $filas[] = [$c['nombre'], $c['cuit'], $c['telefono'], $c['saldo']];
                }
                foreach ($c['pendientes'] as $i => $p) {
                    $filas[] = $i === 0
                        ? [$c['nombre'], $c['cuit'], $c['telefono'], $c['saldo'], $p['comprobante'], $p['fecha']->format('d/m/Y'), $p['total'], $p['saldo'], $p['dias']]
                        : [null, null, null, null, $p['comprobante'], $p['fecha']->format('d/m/Y'), $p['total'], $p['saldo'], $p['dias']];
                }
            }
            $filas[] = ['Total', null, null, round($cuentas->sum('saldo'), 2)];

            return Excel::download(new TablaExport($filas, 'Saldos', [1, 3, count($filas)]), "{$archivo}.xlsx");
        }

        return Pdf::loadView('cuenta-corriente.saldos', [
            'esClientes' => $rc->esClientes(),
            'titulo'     => $titulo,
            'filtro'     => $filtro,
            'cuentas'    => $cuentas,
            'empresa'    => $this->empresa(),
        ])->setPaper('a4')->stream("{$archivo}.pdf");
    }

    private function periodo(?string $desde, ?string $hasta): string
    {
        $f = fn ($d) => \Carbon\Carbon::parse($d)->format('d/m/Y');

        return match (true) {
            $desde && $hasta => "{$f($desde)} al {$f($hasta)}",
            (bool) $desde    => "desde el {$f($desde)}",
            (bool) $hasta    => "hasta el {$f($hasta)}",
            default          => 'todos los movimientos',
        };
    }

    private function empresa(): array
    {
        return [
            'nombre'   => VariableEntorno::get('empresa_nombre', config('app.name')),
            'cuit'     => VariableEntorno::get('empresa_cuit', ''),
            'domicilio'=> VariableEntorno::get('empresa_domicilio', ''),
            'telefono' => VariableEntorno::get('empresa_telefono', ''),
        ];
    }
}
