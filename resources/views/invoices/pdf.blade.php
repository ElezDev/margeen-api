<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Factura {{ $invoice->number }}</title>
    <style>
        @page { margin: 28px 36px; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            color: #1e293b;
            line-height: 1.45;
            padding: 28px 36px;
        }
        table { border-collapse: collapse; }
        .brand { color: #0269e4; }
        .muted { color: #64748b; }
        .top { width: 100%; margin-bottom: 22px; }
        .top td { vertical-align: top; }
        .company-name {
            font-size: 20px;
            font-weight: bold;
            color: #0269e4;
            margin-bottom: 6px;
        }
        .company-meta { font-size: 10px; color: #475569; line-height: 1.6; }
        .logo-wrap {
            width: 120px;
            height: 50px;
            overflow: hidden;
            margin-bottom: 10px;
        }
        .logo {
            max-height: 50px;
            max-width: 120px;
            width: auto;
            height: auto;
        }
        .invoice-box {
            border: 2px solid #0269e4;
            border-radius: 8px;
            overflow: hidden;
            width: 240px;
            margin-left: auto;
        }
        .invoice-box-head {
            background: #0269e4;
            color: #fff;
            text-align: center;
            font-size: 13px;
            font-weight: bold;
            letter-spacing: 0.5px;
            padding: 10px 12px;
        }
        .invoice-box-body { padding: 12px 14px; background: #f8fafc; }
        .invoice-box-body table { width: 100%; }
        .invoice-box-body td { padding: 3px 0; font-size: 10px; }
        .invoice-box-body .label { color: #64748b; width: 72px; }
        .invoice-box-body .value { font-weight: bold; text-align: right; }
        .parties { width: 100%; margin-bottom: 18px; }
        .party-box {
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            padding: 12px 14px;
            background: #fff;
        }
        .party-title {
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            color: #0269e4;
            margin-bottom: 8px;
            padding-bottom: 6px;
            border-bottom: 1px solid #e2e8f0;
        }
        .party-name { font-size: 13px; font-weight: bold; margin-bottom: 4px; }
        .party-line { font-size: 10px; color: #475569; margin-bottom: 2px; }
        .section-title {
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #0269e4;
            margin: 18px 0 8px;
        }
        table.items {
            width: 100%;
            border: 1px solid #cbd5e1;
        }
        table.items th {
            background: #0269e4;
            color: #fff;
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            padding: 9px 8px;
            border: 1px solid #0269e4;
        }
        table.items td {
            padding: 9px 8px;
            border: 1px solid #e2e8f0;
            font-size: 10px;
        }
        table.items tr:nth-child(even) td { background: #f8fafc; }
        table.items .right { text-align: right; white-space: nowrap; }
        table.items .desc { font-weight: bold; color: #0f172a; }
        .summary-wrap { width: 100%; margin-top: 16px; }
        .summary-wrap td { vertical-align: top; }
        .notes-box {
            border: 1px dashed #cbd5e1;
            border-radius: 8px;
            padding: 10px 12px;
            font-size: 10px;
            color: #475569;
            min-height: 70px;
        }
        table.totals {
            width: 260px;
            margin-left: auto;
            border: 1px solid #cbd5e1;
        }
        table.totals td { padding: 8px 12px; font-size: 11px; border: 1px solid #e2e8f0; }
        table.totals .right { text-align: right; white-space: nowrap; }
        table.totals .grand td {
            background: #0269e4;
            color: #fff;
            font-size: 13px;
            font-weight: bold;
        }
        .footer {
            margin-top: 28px;
            padding-top: 10px;
            border-top: 1px solid #e2e8f0;
            text-align: center;
            font-size: 9px;
            color: #94a3b8;
        }
    </style>
</head>
<body>
    <table class="top">
        <tr>
            <td width="58%">
                @if(!empty($logoDataUri))
                    <div class="logo-wrap">
                        <img src="{{ $logoDataUri }}" alt="{{ $company->name }}" class="logo" width="120" height="50">
                    </div>
                @endif
                <div class="company-name">{{ $company->name }}</div>
                <div class="company-meta">
                    @if($company->document)<strong>NIT/CC:</strong> {{ $company->document }}<br>@endif
                    @if($company->phone)<strong>Tel:</strong> {{ $company->phone }}<br>@endif
                    @if($company->address)<strong>Dir:</strong> {{ $company->address }}@endif
                </div>
            </td>
            <td width="42%">
                <div class="invoice-box">
                    <div class="invoice-box-head">FACTURA DE VENTA</div>
                    <div class="invoice-box-body">
                        <table>
                            <tr>
                                <td class="label">Número</td>
                                <td class="value">{{ $invoice->number }}</td>
                            </tr>
                            <tr>
                                <td class="label">Fecha</td>
                                <td class="value">{{ $invoice->issued_at?->format('d/m/Y') }}</td>
                            </tr>
                            <tr>
                                <td class="label">Hora</td>
                                <td class="value">{{ $invoice->issued_at?->format('H:i') }}</td>
                            </tr>
                            <tr>
                                <td class="label">Vendedor</td>
                                <td class="value">{{ $seller->name }}</td>
                            </tr>
                        </table>
                    </div>
                </div>
            </td>
        </tr>
    </table>

    <table class="parties">
        <tr>
            <td width="50%" style="padding-right: 8px;">
                <div class="party-box">
                    <div class="party-title">Datos del cliente</div>
                    <div class="party-name">{{ $client->name }}</div>
                    @if($client->document)<div class="party-line"><strong>Documento:</strong> {{ $client->document }}</div>@endif
                    @if($client->phone)<div class="party-line"><strong>Teléfono:</strong> {{ $client->phone }}</div>@endif
                    @if($client->address)<div class="party-line"><strong>Dirección:</strong> {{ $client->address }}</div>@endif
                </div>
            </td>
            <td width="50%" style="padding-left: 8px;">
                <div class="party-box">
                    <div class="party-title">Información del documento</div>
                    <div class="party-line"><strong>Moneda:</strong> Peso colombiano (COP)</div>
                    <div class="party-line"><strong>Ítems:</strong> {{ $items->count() }}</div>
                    <div class="party-line"><strong>Estado:</strong> {{ $invoice->status->value === 'issued' ? 'Emitida' : ucfirst($invoice->status->value) }}</div>
                </div>
            </td>
        </tr>
    </table>

    <div class="section-title">Detalle de productos / servicios</div>
    <table class="items">
        <thead>
            <tr>
                <th style="width: 38%;">Descripción</th>
                <th class="right" style="width: 10%;">Cant.</th>
                <th style="width: 12%;">Unidad</th>
                <th class="right" style="width: 18%;">V. unitario</th>
                <th class="right" style="width: 22%;">V. total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($items as $item)
            <tr>
                <td class="desc">{{ $item->description }}</td>
                <td class="right">{{ number_format($item->quantity, 2, ',', '.') }}</td>
                <td>{{ $item->unit }}</td>
                <td class="right">{{ \App\Support\MoneyFormatter::cop($item->unit_price) }}</td>
                <td class="right">{{ \App\Support\MoneyFormatter::cop($item->line_total) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <table class="summary-wrap">
        <tr>
            <td width="55%" style="padding-right: 10px;">
                @if($invoice->notes)
                    <div class="section-title" style="margin-top: 0;">Observaciones</div>
                    <div class="notes-box">{{ $invoice->notes }}</div>
                @endif
            </td>
            <td width="45%">
                <table class="totals">
                    <tr>
                        <td>Subtotal</td>
                        <td class="right">{{ \App\Support\MoneyFormatter::cop($invoice->subtotal) }}</td>
                    </tr>
                    @if($invoice->discount > 0)
                    <tr>
                        <td>Descuento</td>
                        <td class="right">- {{ \App\Support\MoneyFormatter::cop($invoice->discount) }}</td>
                    </tr>
                    @endif
                    <tr class="grand">
                        <td>TOTAL A PAGAR</td>
                        <td class="right">{{ \App\Support\MoneyFormatter::cop($invoice->total) }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <div class="footer">
        Documento generado electrónicamente por Margeen · {{ now()->format('d/m/Y H:i') }}
    </div>
</body>
</html>