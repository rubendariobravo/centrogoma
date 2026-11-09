@php
    $m = fn ($v) => '$' . number_format($v, 2, ',', '.');
@endphp
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Cuenta corriente — {{ $cuenta->razon_social }}</title>
    @include('cuenta-corriente._estilos')
</head>
<body>
<div class="container">
    <table class="header">
        <tr>
            <td>
                <div class="empresa">{{ $empresa['nombre'] }}</div>
                <div class="muted">
                    @if($empresa['cuit']) CUIT {{ $empresa['cuit'] }} @endif
                    @if($empresa['domicilio']) · {{ $empresa['domicilio'] }} @endif
                    @if($empresa['telefono']) · Tel. {{ $empresa['telefono'] }} @endif
                </div>
            </td>
            <td>
                <div class="titulo">Resumen de cuenta corriente</div>
                <div class="muted" style="text-align:right">Emitido el {{ now()->format('d/m/Y H:i') }}</div>
            </td>
        </tr>
    </table>

    <div class="datos">
        <strong>{{ $esClientes ? 'Cliente' : 'Proveedor' }}:</strong> {{ $cuenta->razon_social }}
        @if($cuenta->cuit) · CUIT {{ $cuenta->cuit }} @endif
        @if($cuenta->domicilio ?? null) · {{ $cuenta->domicilio }} @endif
        <br>
        <strong>Período:</strong> {{ $periodo }}
        · <strong>Saldo al cierre:</strong>
        <span class="{{ $mov['saldo'] > 0 ? 'deuda' : 'favor' }}">{{ $m($mov['saldo']) }}</span>
        @if($mov['saldo'] < 0) ({{ $esClientes ? 'a favor del cliente' : 'a nuestro favor' }}) @endif
    </div>

    <table class="t">
        <thead>
            <tr>
                <th style="width:60px">Fecha</th>
                <th>Concepto</th>
                <th class="r" style="width:80px">Debe</th>
                <th class="r" style="width:80px">Haber</th>
                <th class="r" style="width:85px">Saldo</th>
            </tr>
        </thead>
        <tbody>
            @if($desde)
                <tr class="sub">
                    <td></td><td>Saldo anterior</td><td></td><td></td>
                    <td class="r">{{ $m($mov['saldo_anterior']) }}</td>
                </tr>
            @endif
            @forelse($mov['movimientos'] as $r)
                <tr>
                    <td>{{ $r['fecha']->format('d/m/Y') }}</td>
                    <td>{{ $r['concepto'] }}</td>
                    <td class="r">{{ $r['debe'] ? $m($r['debe']) : '' }}</td>
                    <td class="r">{{ $r['haber'] ? $m($r['haber']) : '' }}</td>
                    <td class="r">{{ $m($r['saldo']) }}</td>
                </tr>
            @empty
                <tr><td colspan="5" style="text-align:center; color:#999; padding:10px">Sin movimientos en el período</td></tr>
            @endforelse
            <tr class="tot">
                <td></td><td>Totales del período</td>
                <td class="r">{{ $m($mov['debe']) }}</td>
                <td class="r">{{ $m($mov['haber']) }}</td>
                <td class="r">{{ $m($mov['saldo']) }}</td>
            </tr>
        </tbody>
    </table>

    @if($pendientes->isNotEmpty())
        <h3>Comprobantes pendientes</h3>
        <table class="t">
            <thead>
                <tr>
                    <th style="width:60px">Fecha</th>
                    <th>Comprobante</th>
                    <th class="r" style="width:50px">Días</th>
                    <th class="r" style="width:85px">Total</th>
                    <th class="r" style="width:85px">Pendiente</th>
                </tr>
            </thead>
            <tbody>
                @foreach($pendientes as $p)
                    <tr>
                        <td>{{ $p['fecha']->format('d/m/Y') }}</td>
                        <td>{{ $p['comprobante'] }}</td>
                        <td class="r">{{ $p['dias'] }}</td>
                        <td class="r">{{ $m($p['total']) }}</td>
                        <td class="r">{{ $m($p['saldo']) }}</td>
                    </tr>
                @endforeach
                <tr class="tot">
                    <td></td><td>Total pendiente</td><td></td><td></td>
                    <td class="r">{{ $m($pendientes->sum('saldo')) }}</td>
                </tr>
            </tbody>
        </table>
    @endif

    <div class="pie">{{ $empresa['nombre'] }} · {{ $cuenta->razon_social }}</div>
</div>
</body>
</html>
