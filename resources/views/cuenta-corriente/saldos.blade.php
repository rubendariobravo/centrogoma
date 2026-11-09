@php
    $m = fn ($v) => '$' . number_format($v, 2, ',', '.');
    $etiquetaFiltro = ['deudores' => $esClientes ? 'clientes que deben' : 'proveedores a los que les debemos',
                       'a_favor' => 'saldos a favor', 'todos' => 'todas las cuentas con saldo'][$filtro];
@endphp
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $titulo }}</title>
    @include('cuenta-corriente._estilos')
</head>
<body>
<div class="container">
    <table class="header">
        <tr>
            <td>
                <div class="empresa">{{ $empresa['nombre'] }}</div>
                <div class="muted">@if($empresa['cuit']) CUIT {{ $empresa['cuit'] }} @endif</div>
            </td>
            <td>
                <div class="titulo">{{ $titulo }}</div>
                <div class="muted" style="text-align:right">Al {{ now()->format('d/m/Y H:i') }} · {{ $etiquetaFiltro }}</div>
            </td>
        </tr>
    </table>

    <table class="t">
        <thead>
            <tr>
                <th>{{ $esClientes ? 'Cliente' : 'Proveedor' }} / comprobante</th>
                <th style="width:60px">Fecha</th>
                <th class="r" style="width:40px">Días</th>
                <th class="r" style="width:80px">Total</th>
                <th class="r" style="width:80px">Pendiente</th>
                <th class="r" style="width:85px">Saldo</th>
            </tr>
        </thead>
        <tbody>
            @forelse($cuentas as $c)
                <tr class="sub">
                    <td colspan="5">
                        {{ $c['nombre'] }}
                        <span class="muted" style="font-weight:normal">
                            @if($c['cuit']) · {{ $c['cuit'] }} @endif
                            @if($c['telefono']) · Tel. {{ $c['telefono'] }} @endif
                        </span>
                    </td>
                    <td class="r {{ $c['saldo'] > 0 ? 'deuda' : 'favor' }}">{{ $m($c['saldo']) }}</td>
                </tr>
                @foreach($c['pendientes'] as $p)
                    <tr>
                        <td style="padding-left:14px">{{ $p['comprobante'] }}</td>
                        <td>{{ $p['fecha']->format('d/m/Y') }}</td>
                        <td class="r">{{ $p['dias'] }}</td>
                        <td class="r">{{ $m($p['total']) }}</td>
                        <td class="r">{{ $m($p['saldo']) }}</td>
                        <td></td>
                    </tr>
                @endforeach
            @empty
                <tr><td colspan="6" style="text-align:center; color:#999; padding:10px">No hay cuentas con saldo</td></tr>
            @endforelse
            <tr class="tot">
                <td colspan="5">Total ({{ $cuentas->count() }} cuentas)</td>
                <td class="r">{{ $m($cuentas->sum('saldo')) }}</td>
            </tr>
        </tbody>
    </table>
</div>
</body>
</html>
