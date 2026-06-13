<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Factura {{ $invoice->number }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #1a1a1a; }
        .header { margin-bottom: 24px; border-bottom: 2px solid #2563eb; padding-bottom: 16px; }
        .header h1 { font-size: 22px; color: #2563eb; margin-bottom: 4px; }
        .header p { color: #555; line-height: 1.5; }
        .meta { width: 100%; margin-bottom: 20px; }
        .meta td { vertical-align: top; padding: 4px 0; }
        .meta .label { color: #666; width: 120px; }
        .section-title { font-size: 13px; font-weight: bold; color: #2563eb; margin: 16px 0 8px; }
        table.items { width: 100%; border-collapse: collapse; margin-top: 8px; }
        table.items th { background: #f1f5f9; text-align: left; padding: 8px; font-size: 11px; }
        table.items td { padding: 8px; border-bottom: 1px solid #e2e8f0; }
        table.items .right { text-align: right; }
        .totals { width: 280px; margin-left: auto; margin-top: 16px; }
        .totals td { padding: 5px 8px; }
        .totals .right { text-align: right; }
        .totals .profit { font-weight: bold; color: #16a34a; font-size: 14px; }
        .totals .grand { font-weight: bold; font-size: 14px; border-top: 2px solid #2563eb; }
        .footer { margin-top: 32px; padding-top: 12px; border-top: 1px solid #e2e8f0; color: #888; font-size: 10px; text-align: center; }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $company->name }}</h1>
        <p>
            @if($company->document) NIT/CC: {{ $company->document }}<br>@endif
            @if($company->phone) Tel: {{ $company->phone }}<br>@endif
            @if($company->address) {{ $company->address }}@endif
        </p>
    </div>

    <table class="meta">
        <tr>
            <td>
                <div class="section-title">Factura</div>
                <table>
                    <tr><td class="label">Número:</td><td><strong>{{ $invoice->number }}</strong></td></tr>
                    <tr><td class="label">Fecha:</td><td>{{ $invoice->issued_at?->format('d/m/Y H:i') }}</td></tr>
                    <tr><td class="label">Vendedor:</td><td>{{ $seller->name }}</td></tr>
                </table>
            </td>
            <td style="text-align: right;">
                <div class="section-title">Cliente</div>
                <table style="margin-left: auto;">
                    <tr><td class="label">Nombre:</td><td><strong>{{ $client->name }}</strong></td></tr>
                    @if($client->document)<tr><td class="label">Documento:</td><td>{{ $client->document }}</td></tr>@endif
                    @if($client->phone)<tr><td class="label">Teléfono:</td><td>{{ $client->phone }}</td></tr>@endif
                    @if($client->address)<tr><td class="label">Dirección:</td><td>{{ $client->address }}</td></tr>@endif
                </table>
            </td>
        </tr>
    </table>

    <div class="section-title">Detalle</div>
    <table class="items">
        <thead>
            <tr>
                <th>Descripción</th>
                <th class="right">Cant.</th>
                <th>Unidad</th>
                <th class="right">Precio</th>
                <th class="right">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($items as $item)
            <tr>
                <td>{{ $item->description }}</td>
                <td class="right">{{ number_format($item->quantity, 2) }}</td>
                <td>{{ $item->unit }}</td>
                <td class="right">${{ number_format($item->unit_price, 0, ',', '.') }}</td>
                <td class="right">${{ number_format($item->line_total, 0, ',', '.') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals">
        <tr><td>Subtotal</td><td class="right">${{ number_format($invoice->subtotal, 0, ',', '.') }}</td></tr>
        @if($invoice->discount > 0)
        <tr><td>Descuento</td><td class="right">-${{ number_format($invoice->discount, 0, ',', '.') }}</td></tr>
        @endif
        <tr class="grand"><td>Total</td><td class="right">${{ number_format($invoice->total, 0, ',', '.') }}</td></tr>
        <tr class="profit"><td>Ganancia</td><td class="right">${{ number_format($invoice->total_profit, 0, ',', '.') }}</td></tr>
    </table>

    @if($invoice->notes)
    <div class="section-title">Notas</div>
    <p>{{ $invoice->notes }}</p>
    @endif

    <div class="footer">
        Documento generado por Margeen — {{ now()->format('d/m/Y H:i') }}
    </div>
</body>
</html>
