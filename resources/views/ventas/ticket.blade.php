<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Ticket #{{ $venta->id }}</title>
    <style>
        @page {
            margin: 0;
            size: 72mm auto;
        }
        body {
            font-family: 'Courier New', monospace;
            font-size: 13px;
            line-height: 1.3;
            color: #000;
            margin: 0;
            padding: 4px 6px;
            width: 68mm;
            max-width: 68mm;
        }
        .center { text-align: center; }
        .right  { text-align: right; }
        .bold   { font-weight: bold; }

        /* Header */
        .header {
            text-align: center;
            padding-bottom: 4px;
            margin-bottom: 4px;
            border-bottom: 1px dashed #000;
        }
        .logo { max-width: 80px; height: auto; margin-bottom: 3px; }
        .empresa-nombre { font-size: 14px; font-weight: bold; margin: 1px 0; }
        .empresa-info   { font-size: 11px; margin: 0; }

        /* Tipo documento */
        .doc-tipo {
            border: 2px solid #000;
            font-size: 16px;
            font-weight: bold;
            text-align: center;
            padding: 3px;
            margin: 4px 0 2px 0;
        }
        .doc-letra {
            border: 2px solid #000;
            font-size: 22px;
            font-weight: bold;
            width: 36px;
            height: 36px;
            line-height: 36px;
            text-align: center;
            margin: 0 auto 2px auto;
        }
        .doc-numero { font-size: 13px; font-weight: bold; text-align: center; margin: 1px 0; }
        .doc-fecha  { font-size: 11px; text-align: center; margin: 1px 0; }

        /* Secciones */
        .seccion {
            border-bottom: 1px dashed #000;
            padding: 3px 0;
            margin: 3px 0;
        }
        .seccion-titulo {
            font-size: 12px;
            font-weight: bold;
            text-align: center;
            margin-bottom: 2px;
        }
        .fila { font-size: 12px; margin: 1px 0; word-wrap: break-word; }
        .label { font-weight: bold; }

        /* Items */
        .item {
            padding: 2px 0;
            border-bottom: 1px dotted #000;
            margin: 1px 0;
            font-size: 12px;
        }
        .item-desc { font-weight: bold; word-wrap: break-word; }
        .item-calc {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
        }
        .item-calc td { padding: 0 1px; }

        /* Totales */
        .totales {
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
            padding: 3px 0;
            margin: 3px 0;
        }
        .total-fila {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
            font-weight: bold;
        }
        .total-fila td { padding: 1px 0; }
        .total-final {
            font-size: 16px;
            font-weight: bold;
            border-top: 2px solid #000;
            margin-top: 2px;
            padding-top: 2px;
        }
        .saldo-anterior { color: #000; font-size: 12px; }
        .nuevo-saldo {
            font-size: 14px;
            font-weight: bold;
            border-top: 2px solid #000;
            margin-top: 2px;
            padding-top: 2px;
        }

        /* Pagos */
        .pago-fila {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
            font-weight: bold;
        }
        .pago-fila td { padding: 1px 0; }

        /* AFIP */
        .qr-img { max-width: 72px; height: auto; margin: 3px auto; display: block; }
        .cae-info { font-size: 10px; font-weight: bold; }

        /* Footer */
        .footer {
            text-align: center;
            font-size: 11px;
            font-weight: bold;
            border-top: 1px dashed #000;
            margin-top: 4px;
            padding-top: 4px;
            padding-bottom: 4px;
        }
    </style>
</head>
<body>

    {{-- HEADER --}}
    <div class="header">
        @php $logoPath = public_path('images/logo-rdb-azul.png'); @endphp
        @if(file_exists($logoPath))
            <img src="{{ $logoPath }}" alt="" class="logo">
        @endif
        <div class="empresa-nombre">{{ env('EMPRESA_RAZON_SOCIAL', env('APP_NAME')) }}</div>
        <div class="empresa-info">CUIT: {{ env('EMPRESA_CUIT') }}</div>
        <div class="empresa-info">{{ env('EMPRESA_DIRECCION') }}</div>
        <div class="empresa-info">{{ env('EMPRESA_LOCALIDAD') }}, {{ env('EMPRESA_PROVINCIA') }}</div>
        <div class="empresa-info">Tel: {{ env('EMPRESA_TELEFONO') }}</div>
    </div>

    {{-- TIPO DOCUMENTO --}}
    @php
        if ($venta->cae) {
            $tipoLabel = match($venta->tipo_comprobante) {
                'nota_credito' => 'NOTA DE CRÉDITO',
                'nota_debito'  => 'NOTA DE DÉBITO',
                default        => 'FACTURA',
            };
        } else {
            $tipoLabel = 'RECIBO';
        }
    @endphp

    <div class="doc-tipo">{{ $tipoLabel }}</div>

    @if($venta->cae && $venta->letra_comprobante)
        <div class="doc-letra center">{{ $venta->letra_comprobante }}</div>
    @endif

    <div class="doc-numero">
        @if($venta->cae)
            N° {{ str_pad($venta->pto_vta, 4, '0', STR_PAD_LEFT) }}-{{ str_pad($venta->nro_factura, 8, '0', STR_PAD_LEFT) }}
        @else
            N° {{ str_pad($venta->nro_venta ?? $venta->id, 8, '0', STR_PAD_LEFT) }}
        @endif
    </div>

    <div class="doc-fecha">{{ $venta->fecha->format('d/m/Y') }}{{ $venta->hora ? ' ' . $venta->hora : '' }}</div>

    {{-- CLIENTE --}}
    <div class="seccion">
        <div class="seccion-titulo">CLIENTE</div>
        @if($venta->cliente)
            <div class="fila"><span class="label">Nombre:</span> {{ $venta->cliente->razon_social }}</div>
            @if($venta->cliente->cuit || $venta->cliente->dni)
            <div class="fila"><span class="label">CUIT/DNI:</span> {{ $venta->cliente->cuit ?? $venta->cliente->dni }}</div>
            @endif
            @if($venta->cliente->tipoIva)
            <div class="fila"><span class="label">IVA:</span> {{ $venta->cliente->tipoIva->descripcion }}</div>
            @endif
        @elseif($venta->cliente_vtadiaria)
            <div class="fila">{{ $venta->cliente_vtadiaria }}</div>
            @if($venta->dni_vtadiaria)
            <div class="fila"><span class="label">DNI:</span> {{ $venta->dni_vtadiaria }}</div>
            @endif
        @else
            <div class="fila">Consumidor Final</div>
        @endif
    </div>

    {{-- DETALLE --}}
    <div class="seccion-titulo" style="margin-top:4px;">DETALLE</div>
    @foreach($venta->detalles as $det)
    <div class="item">
        <div class="item-desc">{{ $det->descripcion ?: optional($det->articulo)->descripcion }}</div>
        <table class="item-calc">
            <tr>
                <td>{{ number_format($det->cantidad, 2) }} x
                    @if($venta->cae && $venta->letra_comprobante === 'A')
                        ${{ number_format($det->precio_unitario, 2) }}
                    @else
                        ${{ number_format($det->precio_unitario * (1 + ($det->alicuota_iva / 100)), 2) }}
                    @endif
                </td>
                <td style="text-align:right;" class="bold">
                    @if($venta->cae && $venta->letra_comprobante === 'A')
                        ${{ number_format($det->subtotal, 2) }}
                    @else
                        ${{ number_format($det->subtotal_iva ?? ($det->subtotal * (1 + ($det->alicuota_iva / 100))), 2) }}
                    @endif
                </td>
            </tr>
            @if($det->descuento > 0)
            <tr>
                <td style="font-size:11px;">Desc. {{ number_format($det->descuento, 1) }}%</td>
                <td style="text-align:right; font-size:11px;">
                    -${{ number_format($det->subtotal * ($det->descuento / 100), 2) }}
                </td>
            </tr>
            @endif
        </table>
    </div>
    @endforeach

    {{-- TOTALES --}}
    <div class="totales">
        @if($venta->cae && $venta->letra_comprobante === 'A')
            <table class="total-fila">
                <tr><td>Neto:</td><td class="right">${{ number_format($venta->neto, 2) }}</td></tr>
                @if($venta->monto_iva21 > 0)
                <tr><td>IVA 21%:</td><td class="right">${{ number_format($venta->monto_iva21, 2) }}</td></tr>
                @endif
                @if($venta->monto_iva10_5 > 0)
                <tr><td>IVA 10.5%:</td><td class="right">${{ number_format($venta->monto_iva10_5, 2) }}</td></tr>
                @endif
            </table>
        @elseif($venta->cae)
            <div class="fila center" style="font-size:11px;">IVA incluido</div>
        @endif

        @if($venta->descuento > 0)
            <table class="total-fila"><tr>
                <td>Descuento:</td>
                <td class="right">-${{ number_format($venta->descuento, 2) }}</td>
            </tr></table>
        @endif
        @if($venta->percepcion > 0)
            <table class="total-fila"><tr>
                <td>Percep. IIBB:</td>
                <td class="right">${{ number_format($venta->percepcion, 2) }}</td>
            </tr></table>
        @endif

        <table class="total-fila total-final">
            <tr>
                <td>TOTAL:</td>
                <td class="right">${{ number_format($venta->total, 2) }}</td>
            </tr>
        </table>

        @if($saldoAnterior > 0.01)
        <div style="height:4px;"></div>
        <table class="total-fila saldo-anterior">
            <tr>
                <td>Saldo anterior:</td>
                <td class="right">${{ number_format($saldoAnterior, 2) }}</td>
            </tr>
        </table>
        <table class="total-fila nuevo-saldo">
            <tr>
                <td>Saldo deudor:</td>
                <td class="right">${{ number_format($saldoAnterior + $venta->total, 2) }}</td>
            </tr>
        </table>
        @endif
    </div>

    {{-- FORMAS DE PAGO --}}
    @if($venta->pagos->count() > 0)
    <div class="seccion">
        <div class="seccion-titulo">FORMAS DE PAGO</div>
        @foreach($venta->pagos as $pago)
        <table class="pago-fila">
            <tr>
                <td>{{ ucfirst(str_replace('_', ' ', $pago->forma_pago)) }}
                    @if($pago->referencia) ({{ $pago->referencia }}) @endif
                </td>
                <td class="right">${{ number_format($pago->monto, 2) }}</td>
            </tr>
        </table>
        @endforeach
        @php $saldoPendiente = $venta->total - $venta->pagos->sum('monto'); @endphp
        @if($saldoPendiente > 0.01)
        <table class="pago-fila" style="border-top:1px solid #000; margin-top:2px; padding-top:2px;">
            <tr>
                <td>Saldo pend.:</td>
                <td class="right">${{ number_format($saldoPendiente, 2) }}</td>
            </tr>
        </table>
        @endif
    </div>
    @endif

    {{-- OBSERVACIONES --}}
    @if($venta->observacion)
    <div class="seccion">
        <div class="seccion-titulo">OBS.</div>
        <div class="fila" style="font-size:11px;">{{ $venta->observacion }}</div>
    </div>
    @endif

    {{-- AFIP / CAE --}}
    @if($venta->cae)
    <div class="seccion center">
        <div class="cae-info">CAE: {{ $venta->cae }}</div>
        <div class="cae-info">
            Vto.:
            @php
                try { echo \Carbon\Carbon::parse($venta->vto_cae)->format('d/m/Y'); }
                catch (\Exception $e) { echo $venta->vto_cae; }
            @endphp
        </div>
    </div>
    @endif

    {{-- FOOTER --}}
    <div class="footer">
        ¡Gracias por su compra!<br>
        {{ env('EMPRESA_WEB') }}<br>
        {{ env('EMPRESA_EMAIL') }}
    </div>

</body>
</html>
