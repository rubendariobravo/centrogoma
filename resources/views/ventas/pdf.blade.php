<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Comprobante #{{ $venta->id }}</title>
    <style>
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 10px;
            line-height: 1.3;
            color: #333;
            margin: 0;
            padding: 0;
        }
        .container { padding: 15px; }

        /* Encabezado con 3 columnas via table */
        .header-table {
            width: 100%;
            border-collapse: collapse;
            border-bottom: 2px solid #1a2b47;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }
        .header-table td { vertical-align: top; padding: 4px; }
        .logo { max-width: 130px; height: auto; margin-bottom: 4px; }
        .company-info { font-size: 9px; color: #555; line-height: 1.4; }
        .company-name { font-size: 12px; font-weight: bold; color: #1a2b47; }

        .doc-box {
            border: 2px solid #1a2b47;
            text-align: center;
            padding: 6px 10px;
            background: #f8f9fa;
        }
        .doc-tipo { font-size: 18px; font-weight: bold; }
        .doc-letra {
            font-size: 28px;
            font-weight: bold;
            width: 40px;
            height: 40px;
            line-height: 40px;
            border: 2px solid #1a2b47;
            display: inline-block;
            text-align: center;
            margin: 4px auto;
        }
        .doc-numero { font-size: 11px; font-weight: bold; margin-top: 4px; }
        .doc-fecha { font-size: 10px; color: #555; margin-top: 2px; }

        /* Sección cliente */
        .section-box {
            border: 1px solid #ddd;
            border-radius: 3px;
            padding: 8px 10px;
            margin-bottom: 12px;
            background: #fafafa;
        }
        .section-title {
            font-size: 10px;
            font-weight: bold;
            color: #1a2b47;
            border-bottom: 1px solid #1a2b47;
            padding-bottom: 3px;
            margin: 0 0 6px 0;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .info-table { width: 100%; border-collapse: collapse; }
        .info-table td { padding: 2px 4px; font-size: 10px; vertical-align: top; }

        /* Tabla de ítems */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
            table-layout: fixed;
        }
        .items-table th {
            background: #1a2b47;
            color: white;
            padding: 5px 4px;
            font-size: 9px;
            text-align: left;
            font-weight: bold;
        }
        .items-table td {
            border: 1px solid #ddd;
            padding: 4px;
            font-size: 10px;
            word-wrap: break-word;
        }
        .items-table tr:nth-child(even) td { background: #f5f5f5; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }

        /* Totales */
        .totales-table {
            width: 45%;
            float: right;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        .totales-table td { padding: 3px 5px; font-size: 10px; }
        .totales-table .linea-total {
            font-weight: bold;
            font-size: 12px;
            border-top: 2px solid #1a2b47;
            color: #1a2b47;
        }
        .totales-table .linea-saldo-anterior {
            color: #c0392b;
            font-size: 10px;
        }
        .totales-table .linea-nuevo-saldo {
            font-weight: bold;
            font-size: 12px;
            border-top: 1px solid #c0392b;
            color: #c0392b;
        }

        /* Pagos */
        .pagos-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            clear: both;
        }
        .pagos-table th {
            background: #e9ecf0;
            padding: 4px;
            font-size: 9px;
            text-align: left;
            border: 1px solid #ccc;
        }
        .pagos-table td { border: 1px solid #ddd; padding: 4px; font-size: 10px; }

        /* AFIP */
        .afip-section { clear: both; margin-top: 12px; padding-top: 8px; border-top: 1px solid #ddd; }
        .afip-table { width: 60%; border-collapse: collapse; float: left; }
        .afip-table td { padding: 3px 5px; font-size: 10px; }
        .qr-wrap { float: right; text-align: center; width: 35%; }
        .qr-wrap img { width: 100px; height: 100px; }

        /* Observaciones */
        .obs-box { clear: both; margin-top: 10px; padding: 8px; border: 1px dashed #ccc; font-size: 10px; }

        /* Footer */
        .footer { clear: both; margin-top: 15px; border-top: 1px solid #ddd; padding-top: 5px; font-size: 8px; text-align: center; color: #888; }

        .clearfix::after { content: ''; display: block; clear: both; }
    </style>
</head>
<body>
<div class="container">

    {{-- ENCABEZADO --}}
    <table class="header-table">
        <tr>
            {{-- Columna izquierda: logo + empresa --}}
            <td width="38%">
                @php $logoPath = public_path('images/logo-rdb-azul.png'); @endphp
                @if(file_exists($logoPath))
                    <img src="{{ $logoPath }}" alt="{{ env('APP_NAME') }}" class="logo">
                @endif
                <div class="company-name">{{ env('EMPRESA_RAZON_SOCIAL', env('APP_NAME')) }}</div>
                <div class="company-info">
                    CUIT: {{ env('EMPRESA_CUIT') }}<br>
                    {{ env('EMPRESA_DIRECCION') }} – {{ env('EMPRESA_LOCALIDAD') }}, {{ env('EMPRESA_PROVINCIA') }}<br>
                    Tel: {{ env('EMPRESA_TELEFONO') }}<br>
                    {{ env('EMPRESA_EMAIL') }}
                </div>
            </td>

            {{-- Columna central: tipo y número --}}
            <td width="24%" style="text-align: center; vertical-align: middle;">
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
                <div class="doc-box">
                    <div class="doc-tipo">{{ $tipoLabel }}</div>
                    @if($venta->cae && $venta->letra_comprobante)
                        <div class="doc-letra">{{ $venta->letra_comprobante }}</div>
                    @endif
                    <div class="doc-numero">
                        @if($venta->cae)
                            N° {{ str_pad($venta->pto_vta, 4, '0', STR_PAD_LEFT) }}-{{ str_pad($venta->nro_factura, 8, '0', STR_PAD_LEFT) }}
                        @else
                            N° {{ str_pad($venta->nro_venta ?? $venta->id, 8, '0', STR_PAD_LEFT) }}
                        @endif
                    </div>
                </div>
            </td>

            {{-- Columna derecha: fecha y datos fiscales --}}
            <td width="38%" style="text-align: right; vertical-align: top;">
                <div style="font-size: 11px;"><strong>Fecha:</strong> {{ $venta->fecha->format('d/m/Y') }}</div>
                @if($venta->hora)
                    <div style="font-size: 10px; color: #666;">Hora: {{ $venta->hora }}</div>
                @endif
                <div style="font-size: 9px; color: #888; margin-top: 5px;">
                    Cond. Venta: {{ env('EMPRESA_IVA') }}<br>
                    IIBB: {{ env('EMPRESA_IIBB') }}
                </div>
            </td>
        </tr>
    </table>

    {{-- DATOS DEL CLIENTE --}}
    <div class="section-box">
        <p class="section-title">Datos del Cliente</p>
        <table class="info-table">
            <tr>
                <td width="15%"><strong>Razón Social:</strong></td>
                <td width="45%">
                    @if($venta->cliente)
                        {{ $venta->cliente->razon_social }}
                        @if($venta->cliente->nombre_fantasia && $venta->cliente->nombre_fantasia !== $venta->cliente->razon_social)
                            <span style="color:#888;"> ({{ $venta->cliente->nombre_fantasia }})</span>
                        @endif
                    @else
                        {{ $venta->cliente_vtadiaria ?? 'Consumidor Final' }}
                    @endif
                </td>
                <td width="15%"><strong>CUIT / DNI:</strong></td>
                <td width="25%">
                    @if($venta->cliente)
                        {{ $venta->cliente->cuit ?? $venta->cliente->dni ?? '—' }}
                    @else
                        {{ $venta->dni_vtadiaria ?? '—' }}
                    @endif
                </td>
            </tr>
            <tr>
                <td><strong>Cond. IVA:</strong></td>
                <td>
                    @if($venta->cliente)
                        {{ optional($venta->cliente->tipoIva)->descripcion ?? '—' }}
                    @else
                        Consumidor Final
                    @endif
                </td>
                <td><strong>Dirección:</strong></td>
                <td>
                    @if($venta->cliente)
                        {{ $venta->cliente->domicilio }}
                        @if($venta->cliente->localidad)
                            , {{ $venta->cliente->localidad }}
                        @endif
                    @else
                        {{ $venta->domicilio_vtadiaria ?? '—' }}
                    @endif
                </td>
            </tr>
        </table>
    </div>

    {{-- DETALLE --}}
    <p class="section-title" style="margin-bottom:4px;">Detalle</p>
    <table class="items-table">
        <thead>
            <tr>
                <th width="8%">Código</th>
                <th width="40%">Descripción</th>
                <th width="10%" class="text-right">Cant.</th>
                <th width="13%" class="text-right">P. Unit.</th>
                @if($venta->letra_comprobante === 'A')
                    <th width="8%" class="text-center">IVA%</th>
                @endif
                <th width="{{ $venta->letra_comprobante === 'A' ? '21%' : '29%' }}" class="text-right">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @foreach($venta->detalles as $det)
            <tr>
                <td>{{ optional($det->articulo)->codigo ?? '' }}</td>
                <td>{{ $det->descripcion ?: optional($det->articulo)->descripcion }}</td>
                <td class="text-right">{{ number_format($det->cantidad, 2) }}</td>
                <td class="text-right">
                    @if($venta->letra_comprobante === 'A')
                        ${{ number_format($det->precio_unitario, 2) }}
                    @else
                        ${{ number_format($det->precio_unitario * (1 + ($det->alicuota_iva / 100)), 2) }}
                    @endif
                </td>
                @if($venta->letra_comprobante === 'A')
                    <td class="text-center">{{ number_format($det->alicuota_iva, 1) }}%</td>
                @endif
                <td class="text-right">
                    @if($venta->letra_comprobante === 'A')
                        ${{ number_format($det->subtotal, 2) }}
                    @else
                        ${{ number_format($det->subtotal_iva ?? ($det->subtotal * (1 + ($det->alicuota_iva / 100))), 2) }}
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    {{-- TOTALES --}}
    <div class="clearfix">
        <table class="totales-table">
            @if($venta->cae && $venta->letra_comprobante === 'A')
                {{-- Factura A: desglose de IVA --}}
                <tr>
                    <td>Neto Gravado:</td>
                    <td class="text-right">${{ number_format($venta->neto, 2) }}</td>
                </tr>
                @if($venta->monto_iva21 > 0)
                <tr>
                    <td>IVA 21%:</td>
                    <td class="text-right">${{ number_format($venta->monto_iva21, 2) }}</td>
                </tr>
                @endif
                @if($venta->monto_iva10_5 > 0)
                <tr>
                    <td>IVA 10.5%:</td>
                    <td class="text-right">${{ number_format($venta->monto_iva10_5, 2) }}</td>
                </tr>
                @endif
            @elseif($venta->cae)
                <tr>
                    <td colspan="2" style="font-size:9px; color:#888; text-align:right;">IVA incluido en el precio</td>
                </tr>
            @endif

            @if($venta->descuento > 0)
            <tr>
                <td>Descuento:</td>
                <td class="text-right" style="color:#c0392b;">-${{ number_format($venta->descuento, 2) }}</td>
            </tr>
            @endif

            @if($venta->percepcion > 0)
            <tr>
                <td>Percep. IIBB:</td>
                <td class="text-right">${{ number_format($venta->percepcion, 2) }}</td>
            </tr>
            @endif

            <tr class="linea-total">
                <td>TOTAL:</td>
                <td class="text-right">${{ number_format($venta->total, 2) }}</td>
            </tr>

            @if($saldoAnterior > 0.01)
            <tr style="height:6px;"><td colspan="2"></td></tr>
            <tr class="linea-saldo-anterior">
                <td>Saldo Anterior:</td>
                <td class="text-right">${{ number_format($saldoAnterior, 2) }}</td>
            </tr>
            <tr class="linea-nuevo-saldo">
                <td>Nuevo Saldo Deudor:</td>
                <td class="text-right">${{ number_format($saldoAnterior + $venta->total, 2) }}</td>
            </tr>
            @endif
        </table>
    </div>

    {{-- FORMAS DE PAGO --}}
    @if($venta->pagos->count() > 0)
    <div style="clear:both; margin-top:12px;">
        <p class="section-title" style="margin-bottom:4px;">Formas de Pago</p>
        <table class="pagos-table">
            <thead>
                <tr>
                    <th width="40%">Forma de Pago</th>
                    <th width="35%">Referencia</th>
                    <th width="25%" style="text-align:right;">Monto</th>
                </tr>
            </thead>
            <tbody>
                @foreach($venta->pagos as $pago)
                <tr>
                    <td>{{ ucfirst(str_replace('_', ' ', $pago->forma_pago)) }}</td>
                    <td>{{ $pago->referencia ?? '—' }}</td>
                    <td class="text-right">${{ number_format($pago->monto, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="2" class="text-right"><strong>Total pagado:</strong></td>
                    <td class="text-right"><strong>${{ number_format($venta->pagos->sum('monto'), 2) }}</strong></td>
                </tr>
                @php $saldoPendiente = $venta->total - $venta->pagos->sum('monto'); @endphp
                @if($saldoPendiente > 0.01)
                <tr>
                    <td colspan="2" class="text-right" style="color:#c0392b;"><strong>Saldo pendiente:</strong></td>
                    <td class="text-right" style="color:#c0392b;"><strong>${{ number_format($saldoPendiente, 2) }}</strong></td>
                </tr>
                @endif
            </tfoot>
        </table>
    </div>
    @endif

    {{-- OBSERVACIONES --}}
    @if($venta->observacion)
    <div class="obs-box" style="clear:both; margin-top:10px;">
        <strong>Observaciones:</strong> {{ $venta->observacion }}
    </div>
    @endif

    {{-- AFIP / CAE --}}
    @if($venta->cae)
    <div class="afip-section">
        <table class="afip-table">
            <tr>
                <td width="40%"><strong>CAE:</strong></td>
                <td>{{ $venta->cae }}</td>
            </tr>
            <tr>
                <td><strong>Vto. CAE:</strong></td>
                <td>
                    @php
                        try {
                            echo \Carbon\Carbon::parse($venta->vto_cae)->format('d/m/Y');
                        } catch (\Exception $e) {
                            echo $venta->vto_cae;
                        }
                    @endphp
                </td>
            </tr>
            <tr>
                <td><strong>Tipo Cbte:</strong></td>
                <td>{{ $venta->tipo_cbte }}</td>
            </tr>
        </table>
    </div>
    @endif

    {{-- FOOTER --}}
    <div class="footer">
        {{ env('EMPRESA_RAZON_SOCIAL', env('APP_NAME')) }} – {{ env('EMPRESA_WEB') }} – {{ env('EMPRESA_EMAIL') }}
        &nbsp;&nbsp;|&nbsp;&nbsp; Comprobante generado el {{ now()->format('d/m/Y H:i') }}
    </div>

</div>
</body>
</html>
