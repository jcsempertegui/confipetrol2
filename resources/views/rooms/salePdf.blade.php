<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>RECIBO DE VENTAS</title>
    <style>
        @page {
            margin: 20px 40px 40px 40px;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 12px;
            margin: 0;
            padding: 0;
            color: #111;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            border: none;
        }

        .header-table {
            margin-bottom: 10px;
        }

        .header-table td {
            vertical-align: top;
        }

        .logo-cell {
            width: 1%;
            white-space: nowrap;
            text-align: left;
            padding-right: 10px;
        }

        .logotd {
            display: block;
            max-width: 150px;
            max-height: 80px;
            width: auto;
            height: auto;
            object-fit: contain;
            border-radius: 6px;
        }

        .company-cell {
            padding-right: 10px;
        }

        .company-name {
            font-size: 15px;
            font-weight: bold;
            text-transform: uppercase;
            margin: 0 0 4px 0;
            line-height: 1.2;
            word-break: break-word;
            overflow-wrap: break-word;
            white-space: normal;
        }

        .company-info {
            margin: 2px 0;
            font-size: 11px;
            color: #333;
        }

        .doc-cell {
            width: 35%;
            min-width: 180px;
            text-align: right;
        }

        .doc-title {
            font-size: 18px;
            font-weight: bold;
            margin: 0 0 6px 0;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .doc-info {
            margin: 3px 0;
            font-size: 11px;
        }

        .client-table {
            margin-top: 5px;
            border-top: 1.5px solid #7e7e7e;
        }

        .client-table td {
            padding: 6px 0;
            font-size: 12px;
            vertical-align: middle;
        }

        .table-items {
            margin-bottom: -5px;
        }

        .table-items th {
            padding: 2px 2px;
            font-size: 11px;
            text-align: center;
            border-bottom: 1.5px solid #222;
            border-top: 1.5px solid #222;
        }

        .table-items td {
            padding: 6px 5px;
            text-align: center;
            border-bottom: 1px solid #ddd;
            font-size: 12px;
        }

        .table-items td.text-left {
            text-align: left;
        }

        .sku-small {
            color: #555;
            font-size: 10px;
            font-weight: bold;
        }

        .totals-container {
            display: table;
            width: 100%;
            margin-top: 10px;
        }

        .literal-cell {
            display: table-cell;
            width: 60%;
            vertical-align: top;
            padding-top: 5px;
        }

        .literal-text {
            font-size: 11px;
            font-weight: bold;
            line-height: 1.4;
            padding-right: 15px;
        }

        .totals-cell {
            display: table-cell;
            width: 40%;
            vertical-align: top;
        }

        .totals-table td {
            padding: 2px 0;
            text-align: right;
            font-size: 11px;
        }

        .payments-wrapper {
            margin-top: 10px;
            width: 100%;
        }

        .payments-table {
            width: 45%;
            float: right;
        }

        .payments-table td {
            padding: 3px 0;
            text-align: right;
            font-size: 11px;
        }

        .clear {
            clear: both;
        }

        .obs-container {
            margin-top: 20px;
            text-align: left;
        }

        .obs-label {
            font-size: 11px;
            font-weight: bold;
            margin: 0 0 4px 0;
            color: #333;
        }

        .obs-text {
            font-size: 11px;
            margin: 0;
            line-height: 1.4;
            color: #333;
            white-space: pre-wrap;
            word-wrap: break-word;
        }

        .footer-msg {
            text-align: center;
            margin-top: 20px;
            font-size: 11px;
        }

        .footer-msg p {
            margin: 0;
        }

        footer {
            position: fixed;
            bottom: -30px;
            left: 0;
            right: 0;
            height: 30px;
            border-top: 1px solid #ccc;
            padding-top: 6px;
        }

        .footer-table td {
            padding: 0;
            font-size: 10px;
            color: #555;
        }

        .text-left {
            text-align: left;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .page-number:before {
            content: "Página " counter(page) " de " counter(pages);
        }

        .watermark {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            opacity: 0.08;
            z-index: -1;
        }

        .watermark img {
            width: 400px;
            height: auto;
        }
    </style>
</head>

<body>
    <div class="watermark">
        <img src="{{ public_path('assets/images/logopdf.png') }}" alt="watermark">
    </div>
    <footer>
        <table class="footer-table">
            <tr>
                <td class="text-left" style="width: 33%;">{{ $sale->user }}</td>
                <td class="text-center" style="width: 33%;">Generado por Mastec Digital</td>
                <td class="text-right" style="width: 34%;"><span class="page-number"></span></td>
            </tr>
        </table>
    </footer>
    <main>
        <table class="header-table">
            <tr>
                <td class="logo-cell">
                    @if (!empty($settings->image) && file_exists(storage_path('app/public/' . $settings->image)))
                        <img class="logotd" src="{{ storage_path('app/public/' . $settings->image) }}" alt="logo">
                    @else
                        <img class="logotd" src="{{ public_path('assets/images/logo.png') }}" alt="logo">
                    @endif
                </td>
                <td class="company-cell">
                    <p class="company-name">{{ $settings->business ?? 'EMPRESA' }}</p>
                    <p class="company-info">{{ $branch->address ?? 'S/D' }}</p>
                    <p class="company-info">Tel: {{ $branch->phone ?? 'S/N' }}</p>
                </td>
                <td class="doc-cell">
                    <p class="doc-title">RECIBO DE VENTA</p>
                    <p class="doc-info"><strong>N° Venta:</strong> {{ $sale->sale_number }}</p>
                    <p class="doc-info"><strong>Fecha y Hora:</strong> {{ $sale->created_at }}</p>
                    <p class="doc-info"><strong>Usuario:</strong> {{ $sale->user }}</p>
                </td>
            </tr>
        </table>
        <table class="client-table">
            <tr>
                <td style="width: 65%;">
                    <strong>Nombre/Razón Social:</strong> {{ $sale->name }}
                </td>
                <td style="width: 35%; text-align: right;">
                    @if ($printer && $printer->show_nit)
                        <strong>NIT/CI:</strong> {{ $sale->document ?? '000' }}
                    @endif
                </td>
            </tr>
        </table>
        <table class="table-items">
            <thead>
                <tr>
                    <th style="width: 5%;">N°</th>
                    <th style="width: 15%;">CÓDIGO</th>
                    <th style="width: 40%; text-align: left;">PRODUCTO</th>
                    <th style="width: 10%;">CANT.</th>
                    <th style="width: 15%;">P.UNIT</th>
                    <th style="width: 15%;">SUBTOTAL</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($sale_details as $index => $detail)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $detail->product_code ?? 'N/A' }}</td>
                        <td class="text-left">
                            {{ $detail->product_name ?? 'Producto sin nombre' }}
                            @if($detail->detailSkus && $detail->detailSkus->count() > 0)
                                @foreach($detail->detailSkus as $ds)
                                    @if($ds->productSku)
                                        <br><span class="sku-small">{{ $ds->productSku->color->name ?? 'S/C' }} -
                                            {{ $ds->productSku->size->name ?? 'S/T' }}</span>
                                    @endif
                                @endforeach
                            @endif
                        </td>
                        <td>{{ number_format($detail->quantity, 0) }}</td>
                        <td>{{ number_format($detail->sale_price, 2) }}</td>
                        <td>{{ number_format($detail->subtotal, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <div class="totals-container">
            <div class="literal-cell">
                @php
                    $fmt = new \NumberFormatter('es_ES', \NumberFormatter::SPELLOUT);
                    $entero = floor($sale->total);
                    $decimales = round(($sale->total - $entero) * 100);
                    $literal = strtoupper($fmt->format($entero)) . ' Y ' . str_pad($decimales, 2, '0', STR_PAD_LEFT) . '/100 BOLIVIANOS';
                @endphp
                <div class="literal-text">SON: {{ $literal }}</div>
            </div>
            <div class="totals-cell">
                <table class="totals-table">
                    <tr>
                        <td><strong>SUBTOTAL:</strong></td>
                        <td style="width: 45%;">{{ number_format($sale->total + $sale->discount, 2) }}</td>
                    </tr>
                    <tr>
                        <td><strong>DESCUENTO:</strong></td>
                        <td>{{ number_format($sale->discount, 2) }}</td>
                    </tr>
                    <tr >
                        <td><strong>TOTAL:</strong></td>
                        <td>{{ number_format($sale->total, 2) }}</td>
                    </tr>
                    @if(isset($payments) && count($payments) > 0)
                        @foreach($payments as $pay)
                            <tr>
                                <td><strong>{{ $pay->description }}:</strong></td>
                                <td>Bs. {{ number_format($pay->amount, 2) }}</td>
                            </tr>
                        @endforeach
                    @endif
                </table>
            </div>
        </div>
        @if (!empty($sale->observations))
            <div class="obs-container">
                <p class="obs-label">Observaciones:</p>
                <p class="obs-text">{{ $sale->observations }}</p>
            </div>
        @endif
        <div class="footer-msg">
            <p>
                @if (!empty($settings->message))
                    {!! $settings->message !!}
                @endif
            </p>
        </div>
    </main>
</body>

</html>