<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Factura No: {{ $invoice->number }}</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #333;
            font-size: 13px;
            line-height: 1.4;
            margin: 0;
            padding: 0;
        }
        .invoice-box {
            max-width: 100%;
            margin: auto;
            padding: 10px;
        }
        .header-table, .details-table, .items-table, .totals-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .header-table td {
            vertical-align: top;
        }
        .title {
            font-size: 24px;
            font-weight: bold;
            color: #2c3e50;
        }
        .invoice-number {
            font-size: 16px;
            font-weight: bold;
            color: #e74c3c;
            text-align: right;
        }
        .company-info {
            font-size: 11px;
            color: #7f8c8d;
        }
        .section-title {
            background: #f2f4f4;
            padding: 5px;
            font-weight: bold;
            color: #2c3e50;
            border-bottom: 2px solid #bdc3c7;
            margin-bottom: 10px;
        }
        .items-table th {
            background: #2c3e50;
            color: #fff;
            text-align: left;
            padding: 8px;
            font-size: 11px;
        }
        .items-table td {
            padding: 8px;
            border-bottom: 1px solid #eeeeee;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-center: center;
        }
        .totals-table td {
            padding: 6px;
        }
        .grand-total {
            font-weight: bold;
            font-size: 15px;
            color: #2c3e50;
            border-top: 2px solid #2c3e50;
        }
        .notes {
            margin-top: 30px;
            font-size: 11px;
            color: #7f8c8d;
            border-top: 1px solid #bdc3c7;
            padding-top: 5px;
        }
    </style>
</head>
<body>

<div class="invoice-box">
    <table class="header-table">
        <tr>
            <td>
                <span class="title">MARGEEN API</span><br>
                <span class="company-info">
                    <strong>Vendedor:</strong> {{ $invoice->seller->name ?? 'Sistema' }}<br>
                    <strong>Fecha Emisión:</strong> {{ \Carbon\Carbon::parse($invoice->issued_at)->format('d/m/Y H:i') }}
                </span>
            </td>
            <td class="invoice-number">
                FACTURE No.<br>
                {{ $invoice->number }}
            </td>
        </tr>
    </table>

    <div class="section-title">DATOS DEL CLIENTE</div>
    <table class="details-table">
        <tr>
            <td style="width: 50%;">
                <strong>Nombre:</strong> {{ $invoice->client->name }}<br>
                <strong>Documento:</strong> {{ $invoice->client->document ?? 'N/A' }}
            </td>
            <td style="width: 50%;">
                <strong>Teléfono:</strong> {{ $invoice->client->phone ?? 'N/A' }}<br>
                <strong>Dirección:</strong> {{ $invoice->client->address ?? 'N/A' }}
            </td>
        </tr>
    </table>

    <div class="section-title">DETALLE DE LA VENTA</div>
    <table class="items-table">
        <thead>
            <tr>
                <th>Descripción</th>
                <th class="text-center" style="width: 12%;">Cant.</th>
                <th class="text-center" style="width: 12%;">Unidad</th>
                <th class="text-right" style="width: 18%;">Precio U.</th>
                <th class="text-right" style="width: 20%;">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoice->items as $item)
                <tr>
                    <td>{{ $item->description }}</td>
                    <td class="text-center">{{ number_format($item->quantity, 0) }}</td>
                    <td class="text-center">{{ $item->unit }}</td>
                    <td class="text-right">${{ number_format($item->unit_price, 2) }}</td>
                    <td class="text-right">${{ number_format($item->line_total, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals-table" style="width: 40%; margin-left: auto;">
        <tr>
            <td>Subtotal:</td>
            <td class="text-right">${{ number_format($invoice->subtotal, 2) }}</td>
        </tr>
        @if($invoice->discount > 0)
        <tr>
            <td>Descuento:</td>
            <td class="text-right">-${{ number_format($invoice->discount, 2) }}</td>
        </tr>
        @endif
        <tr class="grand-total">
            <td>TOTAL:</td>
            <td class="text-right">${{ number_format($invoice->total, 2) }}</td>
        </tr>
    </table>

    @if($invoice->notes)
        <div class="notes">
            <strong>Notas / Observaciones:</strong><br>
            {{ $invoice->notes }}
        </div>
    @endif
</div>

</body>
</html>