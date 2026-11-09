@php
    $m = fn ($v) => '$' . number_format($v, 2, ',', '.');
    $cant = fn ($v) => rtrim(rtrim(number_format($v, 2, ',', '.'), '0'), ',');
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Lista de precios — {{ $lista->nombre }}</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: Arial, Helvetica, sans-serif; font-size: 11px; color: #222; margin: 0; background: #fff; }
        .hoja { max-width: 900px; margin: 0 auto; padding: 16px; }
        .barra { position: sticky; top: 0; background: #f3f4f6; border-bottom: 1px solid #ddd; padding: 8px 16px; display: flex; gap: 8px; align-items: center; font-size: 13px; }
        .barra button { background: #0f172a; color: #fff; border: 0; border-radius: 6px; padding: 6px 14px; cursor: pointer; font-size: 13px; }
        .barra span { color: #555; }
        header { display: flex; justify-content: space-between; border-bottom: 2px solid #1a2b47; padding-bottom: 6px; margin-bottom: 8px; }
        header h1 { font-size: 16px; margin: 0; color: #1a2b47; }
        header .info { text-align: right; color: #666; font-size: 10px; }
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; font-size: 9px; text-transform: uppercase; color: #555; border-bottom: 1px solid #999; padding: 3px 4px; }
        td { padding: 2px 4px; border-bottom: 1px solid #eee; vertical-align: top; }
        .r { text-align: right; white-space: nowrap; }
        .rubro td { background: #e8ecf2; font-weight: bold; color: #1a2b47; padding-top: 5px; border-bottom: 1px solid #c9d1dc; }
        .cod { color: #666; white-space: nowrap; width: 90px; }
        .tramos { color: #555; font-size: 10px; }
        @media print {
            .barra { display: none; }
            .hoja { max-width: none; padding: 0; }
            thead { display: table-header-group; }
            tr { page-break-inside: avoid; }
            @page { margin: 12mm 10mm; }
        }
    </style>
</head>
<body>
<div class="barra">
    <button type="button" onclick="window.print()">Imprimir / Guardar PDF</button>
    <span>{{ $cantidad }} artículos{{ $filtro ? ' · ' . $filtro : '' }}</span>
</div>
<div class="hoja">
    <header>
        <div>
            <h1>{{ $empresa }}</h1>
            <div>Lista de precios: <strong>{{ $lista->nombre }}</strong> · {{ $verNetos ? 'precios netos, sin IVA' : 'precios finales, con IVA' }}</div>
        </div>
        <div class="info">
            {{ now()->format('d/m/Y') }}<br>
            {{ $filtro ?: 'Todos los artículos' }}
        </div>
    </header>

    <table>
        <thead>
            <tr><th>Código</th><th>Descripción</th><th>Unidad</th><th class="r">Precio</th></tr>
        </thead>
        <tbody>
        @forelse($grupos as $rubro => $items)
            <tr class="rubro"><td colspan="4">{{ $rubro }}</td></tr>
            @foreach($items as $a)
                <tr>
                    <td class="cod">{{ $a['codigo'] }}</td>
                    <td>
                        {{ $a['descripcion'] }}
                        @if($a['tramos']->count() > 1)
                            <div class="tramos">
                                @foreach($a['tramos']->slice(1) as $t)
                                    desde {{ $cant($t['desde']) }}: {{ $m($t['precio']) }}@if(! $loop->last) · @endif
                                @endforeach
                            </div>
                        @endif
                    </td>
                    <td>{{ $a['unidad'] }}</td>
                    <td class="r">{{ $m($a['tramos']->first()['precio']) }}</td>
                </tr>
            @endforeach
        @empty
            <tr><td colspan="4" style="text-align:center; padding: 20px; color:#999">No hay artículos con esos filtros</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
</body>
</html>
