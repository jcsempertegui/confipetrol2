<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <link rel="icon" href="assets/images/favicon.ico" type="image/png" />
    <link rel="stylesheet" href="assets/css/reports.css">
    <title>REPORTE DE REMITOS</title>
</head>

<body>
    <!--HEADER-->
    <div class="header">
        <div class="datos-grales" style="float: left; text-align: left;">
            <p class="titulos" style="font-weight: bold;">{{ $settings->business }}</p>
            <p>Tel: {{ $settings->phone }}</p>
            <p>{{ $settings->address }}</p>
        </div>
    </div>

    <div class="datos-grales" style="text-align: right;">
        <div class="datos-user">
            <p><strong>Usuario:</strong> <span>{{ $users->login }}</span></p>
            <p><strong>Fecha:</strong> <span>{{ \Carbon\Carbon::now()->format('d/m/Y H:i:s') }}</span></p>
        </div>
    </div>

    <!-- Título del Reporte -->
    <div style="text-align: left; margin-top: 30px; margin-left: 335px;">
        <h2 style="font-size: 1.9em; margin: 10;">REPORTE DE REMITOS
            @if($filter_tipo)
                — {{ $filter_tipo }}
            @endif
        </h2>
    </div>

    <!--TABLA-->
    <table class="table-container" style="margin-top: 20px;">
        <thead>
            <tr>
                <th>N°</th>
                <th>N° REMITO</th>
                <th>TIPO</th>
                <th>CONTRATO</th>
                <th>CAMPO</th>
                <th>USUARIO</th>
                <th>CODIGO</th>
                <th>PRODUCTO</th>
                <th>TALLA / COLOR</th>
                <th>CANTIDAD</th>
                <th>ALMACÉN</th>
                <th>FECHA</th>
            </tr>
        </thead>
        <tbody>
            @if ($remitos->isEmpty())
                <tr>
                    <td colspan="12" class="text-center">No se encontraron registros.</td>
                </tr>
            @else
                @foreach ($remitos as $index => $detail)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $detail->remito->remito_number ?? 'S/N' }}</td>
                        <td>{{ $detail->remito->tipo ?? '—' }}</td>
                        <td>{{ $detail->remito->contrato ?? '—' }}</td>
                        <td>{{ $detail->remito->campo ?? '—' }}</td>
                        <td>{{ $detail->remito->user->login ?? $detail->remito->user->name ?? 'S/N' }}</td>
                        <td>{{ $detail->product->code ?? 'N/A' }}</td>
                        <td>{{ $detail->product->name ?? 'N/A' }}</td>
                        <td>
                            @if($detail->sku)
                                {{ $detail->sku->color->name ?? 'S/C' }} / {{ $detail->sku->size->name ?? 'S/T' }}
                            @else
                                —
                            @endif
                        </td>
                        <td>{{ $detail->quantity ?? '0' }}</td>
                        <td>{{ $detail->warehouse->name ?? 'S/N' }}</td>
                        <td>{{ $detail->created_at ?? 'S/N' }}</td>
                    </tr>
                @endforeach
            @endif
        </tbody>
        <tfoot>
            <tr>
                <td colspan="11" style="text-align: right; font-weight: bold;">TOTAL ITEMS:</td>
                <td style="text-align: left;">{{ $totalItems }} (items)</td>
            </tr>
            <tr>
                <td colspan="11" style="text-align: right; font-weight: bold; border: none;">TOTAL CANTIDAD:</td>
                <td style="text-align: left;">{{ $totalQuantityRemito }}</td>
            </tr>
        </tfoot>
    </table>

    <!--FOOTER-->
    <div>
        <p class="footer">Generada a través de Mastec Digital</p>
    </div>
</body>

</html>
