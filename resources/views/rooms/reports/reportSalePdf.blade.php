<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <link rel="icon" href="assets/images/favicon.ico" type="image/png" />
    <link rel="stylesheet" href="assets/css/reports.css">
    <title>REPORTE DE VENTAS</title>
</head>

<body>
    <!--HEADER-->
    <div class="header">
        <!--<div>
            <img class="logotd" src="{{ 'assets/images/logo.png' }}" alt="logo">
        </div>-->

        <div class="datos-grales" style="float: left; text-align: left;">
            <p class="titulos" style="font-weight: bold;">{{ $settings->business }}</p>
            <p>Tel: {{ $settings->phone }}</p>
            <p>{{ $settings->address }}</p>
        </div>

    </div>

    <div class="datos-grales" style=" text-align: right; ">
        <div class="datos-user">
            <p><strong>Usuario:</strong> <span>{{ $users->login }}</span></p>
            <p><strong>Fecha:</strong> <span>{{ \Carbon\Carbon::now()->format('d/m/Y H:i:s') }}</span></p>
        </div>
    </div>

    <!-- Título del Reporte -->
    <div style="text-align: left; margin-top: 30px; margin-left: 335px;">
        <h2 style="font-size: 1.9em; margin: 10;">REPORTE DE VENTAS</h2>
    </div>

    <!--PRODUCTO-->
    <table class="table-container" style="margin-top: 20px;">
        <thead>
            <tr>
                <th>N°</th>
                <th>N° VENTA</th>
                <th>USUARIO</th>
                <th>CLIENTE</th>
                <th>CODIGO</th>
                <th>PRODUCTO</th>
                <th>CANTIDAD</th>
                <th>PRECIO VENTA</th>
                <th>FECHA</th>
            </tr>
        </thead>
        <tbody>
            @if ($sales->isEmpty())
                <tr>
                    <td colspan="9" class="text-center">No se encontraron registros.</td>
                </tr>
            @else
                @foreach ($sales as $index => $sale)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $sale->sale->sale_number ?: 'S/N' }}</td>
                        <td>{{ $sale->sale->user->login ?? 'S/N' }}</td>
                        <td>{{ $sale->sale->customer->name ?? 'S/N' }}</td>
                        <td>{{ $sale->product->code ?? 'N/A' }}</td>
                        <td>{{ $sale->product->name ?? 'N/A' }}</td>
                        <td>{{ $sale->quantity ?? '0' }}</td>
                        <td>{{ $sale->sale_price ?: '0.00' }}</td>
                        <td>{{ $sale->created_at ?: 'S/N' }}</td>
                    </tr>
                @endforeach
            @endif
        </tbody>
        <tfoot>
            <tr>
                <td colspan="8" style="text-align: right; font-weight: bold;">TOTAL PRODUCTOS:</td>
                <td style="text-align: left;">{{ $totalProducts }} (items)</td>
            </tr>
            <tr>
                <td colspan="8" style="text-align: right; font-weight: bold; border: none;">TOTAL CANTIDAD VENTA:</td>
                <td style="text-align: left;">{{ $totalQuantitySold }}</td>
            </tr>
            <tr>
                <td colspan="8" style="text-align: right; font-weight: bold; border: none;">TOTAL VENTA:</td>
                <td style="text-align: left; border-bottom: 1px solid #ddd;">BS. {{ number_format($totalSalesAmount, 2) }}</td>
            </tr>
            <tr>
                <td colspan="8" style="text-align: right; font-weight: bold; border: none;">TOTAL UTILIDAD:</td>
                <td style="text-align: left; border-bottom: 1px solid #ddd;">BS. {{ number_format($totalGrossProfit, 2) }}</td>
            </tr>

            <!-- TOTALES DE PAGOS -->
            <tr style="background-color: #f8f9fa;">
                <td colspan="8" style="text-align: right; font-weight: bold; color: #6c757d; border: none;">TOTAL PAGOS:</td>
                <td style="text-align: left; font-weight: bold; color: #6c757d;">BS. {{ number_format($totalPayments, 2) }}</td>
            </tr>
            <tr>
                <td colspan="8" style="text-align: right; font-weight: bold; border: none;">EFECTIVO:</td>
                <td style="text-align: left;">BS. {{ number_format($totalEffective, 2) }}</td>
            </tr>
            <tr>
                <td colspan="8" style="text-align: right; font-weight: bold; border: none;">QR:</td>
                <td style="text-align: left;">BS. {{ number_format($totalQR, 2) }}</td>
            </tr>
            <tr>
                <td colspan="8" style="text-align: right; font-weight: bold; border: none;">TARJETA:</td>
                <td style="text-align: left;">BS. {{ number_format($totalCard, 2) }}</td>
            </tr>
        </tfoot>
    </table>

    <!--FOOTER-->
    <div>
        <p class="footer">Generada a través de Mastec Digital</p>
    </div>
</body>

</html>