<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Delivery label — {{ $orderReference }}</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; background: #e7edf3; color: #111827; font-family: Arial, sans-serif; }
        .toolbar { display: flex; justify-content: center; gap: 10px; padding: 16px; }
        .toolbar a, .toolbar button { border: 0; background: #173f88; color: #fff; padding: 10px 18px; font-weight: 700; text-decoration: none; cursor: pointer; }
        .label { display: flex; width: 100mm; min-height: 150mm; flex-direction: column; margin: 0 auto 24px; background: #fff; padding: 8mm; box-shadow: 0 12px 32px rgba(15, 23, 42, .15); }
        .header { padding-bottom: 13px; border-bottom: 1px solid #cbd5e1; text-align: center; }
        .brand { font-size: 20px; font-weight: 900; letter-spacing: 2px; }
        .reference { display: flex; align-items: baseline; justify-content: center; gap: 5px; margin-top: 7px; }
        .reference strong { font-size: 14px; letter-spacing: 1px; }
        .muted { color: #64748b; font-size: 10px; }
        .section { margin-top: 14px; }
        .section-title { margin-bottom: 6px; color: #475569; font-size: 9px; font-weight: 800; letter-spacing: 1.4px; text-transform: uppercase; }
        .address { font-size: 12px; line-height: 1.5; }
        .address strong { display: block; font-size: 14px; }
        table { width: 100%; border-collapse: collapse; font-size: 10px; }
        th { padding: 7px 0; border-bottom: 1px solid #94a3b8; color: #475569; text-align: left; }
        td { padding: 8px 0; border-bottom: 1px solid #e2e8f0; text-align: left; vertical-align: top; }
        th:nth-child(n+2), td:nth-child(n+2) { text-align: right; }
        .summary { width: 100%; margin-top: 12px; font-size: 11px; }
        .row { display: flex; justify-content: space-between; gap: 12px; padding: 2px 0; }
        .total { margin-top: 4px; padding-top: 6px; border-top: 1px solid #94a3b8; font-size: 15px; font-weight: 900; }
        .payment-state { margin-top: 9px; text-align: right; color: #334155; font-size: 10px; font-weight: 800; text-transform: uppercase; }
        .note { margin-top: 14px; padding-top: 10px; border-top: 1px solid #e2e8f0; font-size: 10px; line-height: 1.4; }
        .label-footer { display: flex; justify-content: space-between; gap: 18px; margin-top: auto; padding-top: 12px; border-top: 1px solid #cbd5e1; font-size: 10px; }
        .label-footer > div:last-child { text-align: right; }
        .label-footer strong { display: block; margin-top: 3px; color: #1e293b; font-size: 11px; }
        @page { size: 100mm 150mm; margin: 0; }
        @media print { body { background: #fff; } .toolbar { display: none; } .label { margin: 0; box-shadow: none; } }
    </style>
</head>
<body>
    <div class="toolbar"><a href="{{ route('admin.orders.show', $order) }}">Back to order</a><button type="button" onclick="window.print()">Print label</button></div>
    {{-- Print only fulfillment details needed on the 100 × 150 mm delivery label. --}}
    <main class="label">
        <header class="header">
            <div class="brand">TRENDSHOP</div>
            <div class="muted">DELIVERY LABEL</div>
            <div class="reference"><span class="muted">REFERENCE</span><strong>#{{ $orderReference }}</strong></div>
        </header>

        <section class="section">
            <div class="section-title">Deliver to</div>
            <div class="address"><strong>{{ $order->recipient_name }}</strong>{{ $order->recipient_phone }}<br>{{ collect([$order->delivery_address_line_1, $order->delivery_address_line_2, $order->delivery_commune, $order->delivery_district, $order->delivery_city_province, $order->delivery_postal_code])->filter()->join(', ') }}</div>
        </section>

        <section class="section">
            <div class="section-title">Items</div>
            <table>
                <thead><tr><th>Description</th><th>Qty</th><th>Total</th></tr></thead>
                <tbody>@foreach($order->items as $item)<tr><td>{{ $item->product_name }}<br><span class="muted">{{ $item->product_sku }}</span></td><td>{{ $item->quantity }}</td><td>&#36;{{ $item->line_total }}</td></tr>@endforeach</tbody>
            </table>
        </section>

        <div class="summary">
            <div class="row"><span>Subtotal</span><strong>&#36;{{ $order->subtotal }}</strong></div>
            <div class="row"><span>Delivery</span><strong>&#36;{{ $order->delivery_fee }}</strong></div>
            <div class="row total"><span>Total</span><span>&#36;{{ $order->grand_total }} {{ $order->currency }}</span></div>
            <div class="payment-state">Payment: {{ $order->payment_status === 'paid' ? 'Paid' : 'Cash on delivery' }}</div>
        </div>

        @if($order->customer_note)<div class="note"><strong>Delivery note:</strong> {{ $order->customer_note }}</div>@endif

        {{-- Balance essential dispatch details across the bottom edge of the label. --}}
        <footer class="label-footer">
            <div><span class="muted">ORDER DATE</span><strong>{{ ($order->placed_at ?? $order->created_at)->timezone(config('app.display_timezone'))->format('d M Y, H:i') }}</strong></div>
            <div><span class="muted">SHOP PHONE</span><strong>{{ $storeSettings['support_phone'] ?? 'TrendShop' }}</strong></div>
        </footer>
    </main>
    @if(request()->boolean('updated'))<script>window.addEventListener('load', () => window.print());</script>@endif
</body>
</html>
