<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Venta</title>
    <style>
    @page {
        size: 80mm auto;
        margin: 0;
    }

    body {
        font-family: 'Arial', sans-serif;
        font-size: 12px;
        margin: 0;
        padding: 0 15px;
        line-height: 1.2;
        color: #1f1f1f;
    }

    .container {
        width: 100%;
        text-align: center;
        display: block;
    }

    .header {
        margin-bottom: 5px;
        text-align: center;
    }

    .logo {
        width: 50px;
        margin-bottom: 5px;
    }

    .titulo {
        font-size: 18px;
        font-weight: bold;
        margin-top: 15px;
        margin-bottom: -3px;
    }

    .linea-dashed {
        border-top: 1px dashed #000;
    }

    .linea-solid {
        border-top: 1px solid #000;
    }

    .datos {
        text-align: left;
        font-size: 10px;
        margin-bottom: 3px;
    }

    .datos p {
        margin: 2px 0;
        font-size: 10px;
    }

    .titulo-detail {
        text-transform: uppercase;
        font-size: 12px;
        font-weight: bold;
        text-align: left;
        margin-top: 5px;
        margin-bottom: 5px;
    }

    .footer {
        text-align: center;
        font-size: 10px;
        margin-top: 10px;
    }

    .mastecdigital {
        font-size: 10px;
        color: rgb(0, 0, 0);
        font-weight: bold;
        margin-top: -9px;
    }

    table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 18px;
    }

    thead th {
        text-align: left;
        font-size: 12px;
        border-top: 1px solid #000;
        border-bottom: 1px solid #000;
        padding-bottom: 3px;
    }

    tbody td {
        padding: 4px 0;
        font-size: 13px;
        vertical-align: top;
    }

    tfoot td {
        font-size: 12px;
        font-weight: bold;
        border-top: 1px dashed #000;
        padding-top: 5px;
    }

    .table-container {
        width: 100%;
        text-align: left;
        margin-bottom: 5px;
        margin-top: -20px;
    }

    .pagado {
        color: green;
        font-weight: bold;
    }

    .pendiente {
        color: red;
        font-weight: bold;
    }

    .additionals {
        font-size: 11px;
        margin-left: 5px;
    }

    .additional-item {
        display: flex;
        justify-content: space-between;
    }

    .additional-item span {
        white-space: nowrap;
    }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <h3 class="titulo">Venta #</h3>
        </div>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th style="width: 15%;">CANT.</th>
                        <th style="width: 50%;">DETALLE</th>
                        <th style="width: 15%;">SUBTOTAL</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($order->details as $item)
                    <tr>
                        <td>{{ $item->quantity }}</td>
                        <td>
                            {{ $item->product->name ?? 'Producto' }}
                            @if ($item->variant_id)
                            <div class="additionals">
                                <div class="additional-item">
                                    • {{ $item->variant->name }}
                                </div>
                            </div>
                            @endif
                            @if ($item->additionals->count() > 0)
                            <div class="additionals">
                                @foreach ($item->additionals as $detailAdditional)
                                <div class="additional-item">
                                    * x{{ $detailAdditional->quantity }} {{ $detailAdditional->additional->name }}
                                    <span>+Bs.{{ number_format($detailAdditional->price, 2) }}</span>
                                </div>

                                @endforeach
                            </div>
                            @endif
                        </td>
                        <td>{{ number_format($item->subtotal, 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="2" style="text-align: right;  font-weight: bold; font-size: 10px;">
                            <div>TOTAL VENTA:</div>
                        </td>
                        <td colspan="1">
                            <div>{{ number_format($order->total, 2) }}</div>

                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <div class="linea-dashed"></div>

        <div class="datos">
            <p><strong>Tipo de orden:</strong> {{ $order->order_type }}
            </p>
            @if (!empty($order->observations))
            <p><strong>Observación:</strong> {{ $order->observations }}</p>
            @endif
        </div>

        <div class="linea-solid"></div>
        <div class="footer">
            <p class="mastecdigital">Generada a través de Mastec Digital.</p>
        </div>
    </div>
</body>

</html>