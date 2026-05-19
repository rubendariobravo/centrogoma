<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Presupuesto #{{ str_pad($presupuesto->nro_presupuesto, 6, '0', STR_PAD_LEFT) }}</title>
    <style>
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 10px;
            line-height: 1.3;
            color: #333;
            margin: 0;
            padding: 0;
        }
        .container { padding: 20px; }

        .header-table {
            width: 100%;
            border-collapse: collapse;
            border-bottom: 2px solid #1a2b47;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }
        .header-table td { vertical-align: top; padding: 4px; }
        .company-name { font-size: 14px; font-weight: bold; color: #1a2b47; }
        .company-info { font-size: 9px; color: #555; line-height: 1.5; }

        .doc-box {
            border: 2px solid #1a2b47;
            text-align: center;
            padding: 8px 12px;
            background: #f8f9fa;
            min-width: 130px;
        }
        .doc-tipo { font-size: 14px; font-weight: bold; color: #1a2b47; }
        .doc-numero { font-size: 12px; font-weight: bold; margin-top: 4px; }
        .doc-fecha { font-size: 9px; color: #555; margin-top: 2px; }
        .doc-validez { font-size: 9px; color: #888; margin-top: 2px; }

        .section-box {
            border: 1px solid #ddd;
            border-radius: 3px;
            padding: 8px 10px;
            margin-bottom: 12px;
            background: #fafafa;
        }
        .section-title {
            font-size: 9px;
            font-weight: bold;
            color: #1a2b47;
            border-bottom: 1px solid #ccd;
            padding-bottom: 3px;
            margin: 0 0 6px 0;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .info-table { width: 100%; border-collapse: collapse; }
        .info-table td { padding: 2px 4px; font-size: 10px; vertical-align: top; }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
            table-layout: fixed;
        }
        .items-table th {
            background: #1a2b47;
            color: #fff;
            padding: 5px 6px;
            text-align: left;
            font-size: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .items-table td {
            padding: 5px 6px;
            border-bottom: 1px solid #eee;
            font-size: 10px;
            vertical-align: top;
        }
        .items-table tr:nth-child(even) td { background: #f9f9f9; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }

        .totals-table {
            width: 50%;
            float: right;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        .totals-table td { padding: 4px 8px; font-size: 10px; }
        .totals-table .total-row td {
            border-top: 2px solid #1a2b47;
            font-size: 13px;
            font-weight: bold;
            color: #1a2b47;
        }
        .clearfix::after { content: ''; display: table; clear: both; }

        .footer {
            margin-top: 20px;
            padding-top: 10px;
            border-top: 1px solid #ddd;
            font-size: 8px;
            color: #888;
            text-align: center;
        }
        .conditions-box {
            background: #f0f4ff;
            border: 1px solid #c8d5f0;
            border-radius: 3px;
            padding: 8px 10px;
            margin-bottom: 12px;
        }
        .conditions-box .section-title { color: #2a3b6e; border-color: #c8d5f0; }
    </style>
</head>
<body>
<div class="container">

    {{-- Encabezado --}}
    <table class="header-table">
        <tr>
            <td style="width:55%">
                <div class="company-name">{{ $empresa['nombre'] ?? config('app.name') }}</div>
                @if(! empty($empresa['direccion']))
                    <div class="company-info">{{ $empresa['direccion'] }}</div>
                @endif
                @if(! empty($empresa['cuit']))
                    <div class="company-info">CUIT: {{ $empresa['cuit'] }}</div>
                @endif
                @if(! empty($empresa['telefono']))
                    <div class="company-info">Tel: {{ $empresa['telefono'] }}</div>
                @endif
                @if(! empty($empresa['email']))
                    <div class="company-info">{{ $empresa['email'] }}</div>
                @endif
            </td>
            <td style="width:45%; text-align:right">
                <div class="doc-box" style="display:inline-block">
                    <div class="doc-tipo">PRESUPUESTO</div>
                    <div class="doc-numero">#{{ str_pad($presupuesto->nro_presupuesto, 6, '0', STR_PAD_LEFT) }}</div>
                    <div class="doc-fecha">Fecha: {{ $presupuesto->fecha->format('d/m/Y') }}</div>
                    @if($presupuesto->fecha_vencimiento)
                    <div class="doc-validez">Válido hasta: {{ $presupuesto->fecha_vencimiento->format('d/m/Y') }}</div>
                    @endif
                </div>
            </td>
        </tr>
    </table>

    {{-- Cliente --}}
    <div class="section-box">
        <div class="section-title">Cliente</div>
        <table class="info-table">
            <tr>
                <td style="width:50%">
                    <strong>{{ $presupuesto->cliente?->razon_social ?? '—' }}</strong>
                    @if($presupuesto->cliente?->nombre_fantasia && $presupuesto->cliente->nombre_fantasia !== $presupuesto->cliente->razon_social)
                        <br>{{ $presupuesto->cliente->nombre_fantasia }}
                    @endif
                </td>
                <td style="width:50%">
                    @if($presupuesto->cliente?->cuit)
                        CUIT: {{ $presupuesto->cliente->cuit }}<br>
                    @endif
                    @if($presupuesto->cliente?->telefono)
                        Tel: {{ $presupuesto->cliente->telefono }}<br>
                    @endif
                    @if($presupuesto->cliente?->email)
                        {{ $presupuesto->cliente->email }}<br>
                    @endif
                    @if($presupuesto->cliente?->domicilio)
                        {{ $presupuesto->cliente->domicilio }}
                    @endif
                </td>
            </tr>
        </table>
    </div>

    {{-- Condiciones --}}
    @if($presupuesto->condicion_pago || $presupuesto->plazo_entrega)
    <div class="conditions-box">
        <div class="section-title">Condiciones</div>
        <table class="info-table">
            <tr>
                @if($presupuesto->condicion_pago)
                <td style="width:50%"><strong>Condición de pago:</strong> {{ $presupuesto->condicion_pago }}</td>
                @endif
                @if($presupuesto->plazo_entrega)
                <td style="width:50%"><strong>Plazo de entrega:</strong> {{ $presupuesto->plazo_entrega }}</td>
                @endif
            </tr>
        </table>
    </div>
    @endif

    {{-- Detalle --}}
    <table class="items-table">
        <thead>
            <tr>
                <th style="width:5%">#</th>
                <th style="width:45%">Descripción</th>
                <th style="width:8%; text-align:center">Cant.</th>
                <th style="width:8%; text-align:center">UM</th>
                <th style="width:13%; text-align:right">P. Unitario</th>
                <th style="width:8%; text-align:center">Desc.%</th>
                <th style="width:13%; text-align:right">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @foreach($presupuesto->detalles as $i => $det)
            @php
                $precioFinal = $det->precio_unitario * (1 + $det->alicuota_iva / 100);
                $subtotalFinal = $det->subtotal * (1 + $det->alicuota_iva / 100);
            @endphp
            <tr>
                <td class="text-center">{{ $i + 1 }}</td>
                <td>
                    {{ $det->descripcion ?: $det->articulo?->descripcion }}
                    @if($det->articulo?->codigo)
                        <br><span style="font-size:8px; color:#888">{{ $det->articulo->codigo }}</span>
                    @endif
                </td>
                <td class="text-center">{{ number_format($det->cantidad, 2, ',', '.') }}</td>
                <td class="text-center">{{ $det->articulo?->unidad_medida ?? 'unidad' }}</td>
                <td class="text-right">${{ number_format($precioFinal, 2, ',', '.') }}</td>
                <td class="text-center">{{ $det->descuento > 0 ? number_format($det->descuento, 1).'%' : '—' }}</td>
                <td class="text-right">${{ number_format($subtotalFinal, 2, ',', '.') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    {{-- Totales --}}
    <div class="clearfix">
        <table class="totals-table">
            @if($presupuesto->neto_21 > 0)
            <tr>
                <td style="color:#666">Neto gravado 21%</td>
                <td class="text-right">${{ number_format($presupuesto->neto_21, 2, ',', '.') }}</td>
            </tr>
            <tr>
                <td style="color:#666">IVA 21%</td>
                <td class="text-right">${{ number_format($presupuesto->monto_iva21, 2, ',', '.') }}</td>
            </tr>
            @endif
            @if($presupuesto->neto_10_5 > 0)
            <tr>
                <td style="color:#666">Neto gravado 10,5%</td>
                <td class="text-right">${{ number_format($presupuesto->neto_10_5, 2, ',', '.') }}</td>
            </tr>
            <tr>
                <td style="color:#666">IVA 10,5%</td>
                <td class="text-right">${{ number_format($presupuesto->monto_iva10_5, 2, ',', '.') }}</td>
            </tr>
            @endif
            <tr class="total-row">
                <td>TOTAL</td>
                <td class="text-right">${{ number_format($presupuesto->total, 2, ',', '.') }}</td>
            </tr>
        </table>
    </div>

    {{-- Observación --}}
    @if($presupuesto->observacion)
    <div class="section-box" style="margin-top:8px">
        <div class="section-title">Observaciones</div>
        <p style="margin:0; font-size:10px; color:#444">{{ $presupuesto->observacion }}</p>
    </div>
    @endif

    {{-- Pie --}}
    <div class="footer">
        <p>Presupuesto válido por {{ $presupuesto->dias_validez }} días desde la fecha de emisión.
        Los precios incluyen IVA. Este presupuesto no constituye una factura.</p>
        @if(! empty($empresa['nombre']))
            <p>{{ $empresa['nombre'] }}
            @if(! empty($empresa['cuit'])) — CUIT: {{ $empresa['cuit'] }} @endif
            </p>
        @endif
    </div>

</div>
</body>
</html>
