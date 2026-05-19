<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Producción #{{ $produccion->nro_produccion }}</title>
    <style>
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 10px;
            line-height: 1.4;
            color: #333;
            margin: 0;
            padding: 0;
        }
        .container { padding: 20px; }

        /* Encabezado */
        .header-table { width: 100%; border-collapse: collapse; margin-bottom: 16px; border-bottom: 2px solid #1a2b47; padding-bottom: 10px; }
        .header-table td { vertical-align: top; padding: 4px; }
        .company-name { font-size: 14px; font-weight: bold; color: #1a2b47; }
        .company-info { font-size: 9px; color: #555; }

        .doc-title { font-size: 16px; font-weight: bold; color: #1a2b47; text-align: right; }
        .doc-nro { font-size: 22px; font-weight: bold; color: #1a2b47; text-align: right; }

        /* Datos cabecera */
        .datos-table { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
        .datos-table td { padding: 4px 8px; font-size: 9.5px; }
        .datos-table .label { font-weight: bold; color: #555; width: 130px; }
        .datos-table tr:nth-child(even) td { background-color: #f5f6f8; }

        /* Estado badge */
        .estado { display: inline-block; padding: 2px 8px; border-radius: 10px; font-weight: bold; font-size: 9px; }
        .estado-abierta  { background: #dbeafe; color: #1d4ed8; }
        .estado-cerrada  { background: #d1fae5; color: #065f46; }
        .estado-anulada  { background: #fee2e2; color: #991b1b; }

        /* Sección insumos */
        .section-title {
            background-color: #1a2b47;
            color: white;
            font-weight: bold;
            padding: 5px 10px;
            font-size: 10px;
            margin-bottom: 0;
        }

        /* Tabla detalle */
        .det-table { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
        .det-table th {
            background-color: #e9ecf0;
            text-align: left;
            padding: 5px 8px;
            font-size: 9px;
            font-weight: bold;
            color: #555;
            text-transform: uppercase;
        }
        .det-table th.right { text-align: right; }
        .det-table td { padding: 5px 8px; border-bottom: 1px solid #e9ecf0; font-size: 9.5px; }
        .det-table td.right { text-align: right; }
        .det-table tr:last-child td { border-bottom: none; }

        /* Resumen */
        .resumen-table { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
        .resumen-table td { padding: 4px 8px; font-size: 9.5px; }
        .resumen-table .label { font-weight: bold; color: #555; }
        .resumen-table .value { text-align: right; font-weight: bold; }

        .box-resumen {
            background-color: #f5f6f8;
            border: 1px solid #dde1e7;
            border-radius: 6px;
            padding: 10px 14px;
            margin-bottom: 16px;
        }

        .highlight { background-color: #1a2b47; color: white; padding: 5px 8px; border-radius: 4px; }

        .merma-warn { color: #d97706; font-weight: bold; }

        .footer { margin-top: 20px; border-top: 1px solid #dde1e7; padding-top: 8px; font-size: 8.5px; color: #999; text-align: center; }
    </style>
</head>
<body>
<div class="container">

    {{-- Encabezado --}}
    <table class="header-table">
        <tr>
            <td style="width:60%">
                <div class="company-name">Sistema Avícola</div>
                <div class="company-info">Gestión de producción y stock</div>
            </td>
            <td style="width:40%; text-align:right;">
                <div class="doc-title">REPORTE DE PRODUCCIÓN</div>
                <div class="doc-nro">#{{ str_pad($produccion->nro_produccion, 6, '0', STR_PAD_LEFT) }}</div>
            </td>
        </tr>
    </table>

    {{-- Datos de la producción --}}
    <table class="datos-table">
        <tr>
            <td class="label">Fecha</td>
            <td>{{ $produccion->fecha->format('d/m/Y') }}</td>
            <td class="label">Tipo</td>
            <td style="text-transform:capitalize">{{ $produccion->tipo_produccion }}</td>
        </tr>
        <tr>
            <td class="label">Empleado</td>
            <td>{{ $produccion->empleado?->nombre ?? '—' }}</td>
            <td class="label">Estado</td>
            <td>
                <span class="estado estado-{{ $produccion->estado }}">
                    {{ ucfirst($produccion->estado) }}
                </span>
            </td>
        </tr>
        <tr>
            <td class="label">Registrado por</td>
            <td>{{ $produccion->user?->name ?? '—' }}</td>
            <td class="label">Observación</td>
            <td>{{ $produccion->observacion ?? '—' }}</td>
        </tr>
    </table>

    {{-- Insumos de entrada --}}
    <div class="section-title">INSUMOS DE ENTRADA (EGRESO DE STOCK)</div>
    <table class="det-table">
        <thead>
            <tr>
                <th>Artículo</th>
                <th class="right">Cantidad</th>
                <th class="right">Precio Costo</th>
                <th class="right">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @forelse($produccion->insumos as $det)
            <tr>
                <td>{{ $det->articulo?->descripcion ?? '—' }}</td>
                <td class="right">{{ number_format($det->cantidad, 3, ',', '.') }}</td>
                <td class="right">${{ number_format($det->precio_costo, 4, ',', '.') }}</td>
                <td class="right">${{ number_format($det->subtotal, 2, ',', '.') }}</td>
            </tr>
            @empty
            <tr><td colspan="4" style="text-align:center;color:#999;">Sin datos</td></tr>
            @endforelse
        </tbody>
    </table>

    {{-- Productos obtenidos --}}
    <div class="section-title">PRODUCTOS OBTENIDOS (INGRESO DE STOCK)</div>
    @php
        $totalSalida = $produccion->productos->sum('cantidad');
    @endphp
    <table class="det-table">
        <thead>
            <tr>
                <th>Artículo</th>
                <th class="right">Cantidad</th>
                <th class="right">Rendimiento %</th>
                <th class="right">Nuevo Precio Costo</th>
            </tr>
        </thead>
        <tbody>
            @forelse($produccion->productos as $det)
            @php
                $rend = $totalSalida > 0 ? round($det->cantidad / $totalSalida * 100, 2) : 0;
            @endphp
            <tr>
                <td>{{ $det->articulo?->descripcion ?? '—' }}</td>
                <td class="right">{{ number_format($det->cantidad, 3, ',', '.') }}</td>
                <td class="right">{{ $rend }}%</td>
                <td class="right">${{ number_format($produccion->costo_promedio, 4, ',', '.') }}</td>
            </tr>
            @empty
            <tr><td colspan="4" style="text-align:center;color:#999;">Sin datos</td></tr>
            @endforelse
        </tbody>
    </table>

    {{-- Resumen --}}
    <div class="box-resumen">
        <table style="width:100%; border-collapse:collapse;">
            <tr>
                <td style="width:25%; padding:4px 8px;">
                    <div style="font-size:9px;color:#555;font-weight:bold;">TOTAL ENTRADA</div>
                    <div style="font-size:13px;font-weight:bold;">
                        {{ number_format($produccion->insumos->sum('cantidad'), 3, ',', '.') }}
                    </div>
                </td>
                <td style="width:25%; padding:4px 8px;">
                    <div style="font-size:9px;color:#555;font-weight:bold;">TOTAL SALIDA</div>
                    <div style="font-size:13px;font-weight:bold;">
                        {{ number_format($totalSalida, 3, ',', '.') }}
                    </div>
                </td>
                <td style="width:25%; padding:4px 8px;">
                    @php
                        $totalEntrada = $produccion->insumos->sum('cantidad');
                        $merma = max(0, $totalEntrada - $totalSalida);
                        $mermaPct = $totalEntrada > 0 ? round($merma / $totalEntrada * 100, 2) : 0;
                    @endphp
                    <div style="font-size:9px;color:#555;font-weight:bold;">MERMA</div>
                    <div style="font-size:13px;font-weight:bold;" class="{{ $merma > 0 ? 'merma-warn' : '' }}">
                        {{ number_format($merma, 3, ',', '.') }}
                        @if($mermaPct > 0) <span style="font-size:10px;">({{ $mermaPct }}%)</span> @endif
                    </div>
                </td>
                <td style="width:25%; padding:4px 8px; background-color:#1a2b47; border-radius:4px; text-align:right;">
                    <div style="font-size:9px;color:#aab;font-weight:bold;">COSTO PROMEDIO / KG</div>
                    <div style="font-size:14px;font-weight:bold;color:white;">
                        ${{ number_format($produccion->costo_promedio, 4, ',', '.') }}
                    </div>
                    @if($produccion->costo_total > 0)
                    <div style="font-size:9px;color:#aab;margin-top:4px;">
                        Costo total: ${{ number_format($produccion->costo_total, 2, ',', '.') }}
                    </div>
                    @endif
                </td>
            </tr>
        </table>
    </div>

    <div class="footer">
        Generado el {{ now()->format('d/m/Y H:i') }}
        · Producción #{{ $produccion->nro_produccion }}
        · Sistema Avícola
    </div>

</div>
</body>
</html>
