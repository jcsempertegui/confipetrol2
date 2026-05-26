<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nota de Venta #{{ substr($sale->sale_number, 0) }}</title>
    <style>
        @page { size: 80mm auto; margin: 0; }
        body { font-family: Arial, Helvetica, sans-serif; font-size: 15px; margin: 0; padding: 0 16px; line-height: 1.2; color: #000; }
        .container { width: 100%; text-align: center; display: block; }
        .header { margin-bottom: 3px; }
        .titulo { font-size: 24px; font-weight: bold; margin-top: 1px; margin-bottom: 1px; }
        .numero-venta { font-size: 16px; font-weight: bold; margin-bottom: 5px; }
        .linea-dashed { border-top: 2px dashed #000; margin: 8px 0; }
        .linea-solid { border-top: 2px solid #000; margin: 8px 0; }
        .datos { text-align: left; font-size: 14px; margin-bottom: 5px; }
        .datos p { margin: 3px 0; }
        .footer { text-align: center; font-size: 14px; margin-top: -5px; margin-bottom: 0px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 5px;}
        thead th { text-align: left; font-size: 14px; font-weight: bold; border-top: 2px solid #000; border-bottom: 2px solid #000; padding: 4px 2px; }
        tbody td { padding: 4px 2px; font-size: 14px; vertical-align: top; }
        tfoot td { font-size: 14px; font-weight: bold; padding: 2px 2px; }
        tfoot tr.sep td { border-top: 2px dashed #000; padding-top: 5px; }
        .son-literal { text-align: left; font-size: 12px; font-weight: bold; margin: 6px 0 4px 0; padding: 5px 0 4px 0; border-bottom: 2px dashed #000; word-break: break-word; }
        .cajero-info { text-align: left; font-size: 13px; margin-bottom: 5px; margin-top: 5px; }
        .pie-info { text-align: left; font-size: 13px;}
        .pie-info p { margin: 0px 0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            @if(isset($docSettingsNota) && $docSettingsNota->show_logo == 1)
                <div style="margin-bottom: 5px;">
                    @if(isset($settings) && $settings->image)
                        <img src="{{ public_path('storage/'.$settings->image) }}" alt="LOGO" style="max-width: 100px; max-height: 100px;">
                    @else
                        <div style="border: 1px solid #000; display: inline-block; padding: 10px; font-size: 10px;">LOGO</div>
                    @endif
                </div>
            @endif

            @if(!isset($docSettingsNota) || $docSettingsNota->show_business_name == 1)
                <div style="font-weight: bold; font-size: 18px; margin-bottom: 2px;">{{ strtoupper($branchInfo->name ?? 'EMPRESA') }}</div>
            @endif

            @if(!isset($docSettingsNota) || $docSettingsNota->show_address == 1)
                <div style="font-size: 14px; margin-bottom: 2px;">{{ strtoupper($branchInfo->address ?? 'DIRECCION') }}</div>
            @endif

            @if(!isset($docSettingsNota) || $docSettingsNota->show_phone == 1)
                <div style="font-size: 14px; margin-bottom: 5px;">TEL: {{ $branchInfo->phone ?? 'S/N' }}</div>
            @endif

            <h3 class="titulo">{{ isset($docSettingsNota) && $docSettingsNota->custom_title ? strtoupper($docSettingsNota->custom_title) : 'NOTA DE VENTA' }}</h3>
            <div class="numero-venta">N° {{ $sale->sale_number }}</div>
        </div>
        
        <div class="datos">
            @if(!isset($docSettingsNota) || $docSettingsNota->show_client == 1)
                <p><strong>Cliente:</strong> {{ strtoupper($sale->name ?? 'PUBLICO GENERAL') }}</p>
                @if ($printer && $printer->show_nit)
                <p><strong>NIT/CI:</strong> {{ $sale->document ?? '0000' }}</p>
                @endif
            @endif
            <p><strong>Fecha:</strong> {{ \Carbon\Carbon::parse($sale->created_at)->format('d/m/Y - H:i:s') }}</p>
        </div>

        <table>
            <thead>
                <tr>
                    <th style="width:15%;">CANT</th>
                    <th style="width:45%;">DETALLE</th>
                    <th style="width:20%; text-align:right;">P.UNIT</th>
                    <th style="width:20%; text-align:right;">SUBT.</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($sale_details as $item)
                <tr>
                    <td>{{ number_format($item->quantity, 0) }}</td>
                    <td>
                        {{ strtoupper($item->product_name ?? 'PRODUCTO') }}
                        @if($item->detailSkus && $item->detailSkus->count() > 0)
                            @foreach($item->detailSkus as $ds)
                                @if($ds->productSku)
                                    <br><span style="font-size: 12px; color: #555;">- {{ strtoupper($ds->productSku->color->name ?? '') }} / {{ strtoupper($ds->productSku->size->name ?? '') }}</span>
                                @endif
                            @endforeach
                        @endif
                    </td>
                    <td style="text-align:right;">{{ number_format($item->sale_price, 2) }}</td>
                    <td style="text-align:right;">{{ number_format($item->subtotal, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="sep">
                    <td colspan="3" style="text-align:right;">SUBTOTAL:</td>
                    <td style="text-align:right;">{{ number_format($sale->total + $sale->discount, 2) }}</td>
                </tr>
                <tr>
                    <td colspan="3" style="text-align:right;">DESCUENTO:</td>
                    <td style="text-align:right;">{{ number_format($sale->discount, 2) }}</td>
                </tr>
                <tr>
                    <td colspan="3" style="text-align:right;"><strong>TOTAL A PAGAR:</strong></td>
                    <td style="text-align:right;"><strong>{{ number_format($sale->total, 2) }}</strong></td>
                </tr>
                @if(isset($payments) && $payments->count() > 0)
                    @foreach($payments as $pago)
                    <tr>
                        <td colspan="3" style="text-align:right; font-weight: normal; font-size: 13px;">
                            {{ strtoupper($pago->description) }}:</td>
                        <td style="text-align:right; font-weight: normal; font-size: 13px;">
                            {{ number_format($pago->amount, 2) }}</td>
                    </tr>
                    @endforeach
                @endif
            </tfoot>
        </table>

        <div class="son-literal">
            @php
            $fmt = new \NumberFormatter('es_ES', \NumberFormatter::SPELLOUT);
            $entero = floor($sale->total);
            $decimales = round(($sale->total - $entero) * 100);
            $literal = strtoupper($fmt->format($entero)) . ' Y ' . str_pad($decimales, 2, '0', STR_PAD_LEFT) . '/100 BOLIVIANOS';
            @endphp
            SON: {{ $literal }}
        </div>

        <div class="cajero-info">
            @if(!isset($docSettingsNota) || $docSettingsNota->show_cashier == 1)
            <strong>Cajero:</strong> {{ strtoupper($sale->user ?? '') }}
            @endif
        </div>

        @if (!empty($sale->observations))
        <div class="pie-info">
            <p><strong>Obs:</strong> <span style="font-weight: normal;">{{ strtoupper($sale->observations) }}</span></p>
        </div>
        @endif

        <div class="linea-solid"></div>
        <div class="footer">
            <p>{{ isset($docSettingsNota) && $docSettingsNota->footer_text ? $docSettingsNota->footer_text : 'GENERADO POR MASTEC DIGITAL.' }}</p>
        </div>
    </div>
</body>
</html>