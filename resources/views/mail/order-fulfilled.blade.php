<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>
<body style="margin:0;padding:0;background:#f4f4f5;font-family:'Helvetica Neue',Arial,sans-serif">
<table width="100%" cellpadding="0" cellspacing="0" style="padding:40px 0">
  <tr><td align="center">
    <table width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%">

      {{-- Header --}}
      <tr><td style="background:linear-gradient(135deg,#050505 0%,#0d0d0f 100%);border-radius:16px 16px 0 0;padding:32px;text-align:center">
        <p style="margin:0 0 8px;font-size:11px;letter-spacing:.12em;text-transform:uppercase;color:#d4af37;font-weight:700">Lensmania Labs</p>
        <h1 style="margin:0;font-size:26px;color:#fff;font-weight:800;letter-spacing:-.03em">
          @if(count($licenses) > 1)
            CineCut licenses activated.
          @elseif(count($licenses) === 1)
            {{ $licenses[0]['product_name'] }} activated.
          @else
            {{ $order->product_name }} activated.
          @endif
        </h1>
        <p style="margin:12px 0 0;font-size:14px;color:#a2a2ad">Your license {{ count($licenses) > 1 ? 'keys are' : 'key is' }} ready to use.</p>
      </td></tr>

      {{-- Body --}}
      <tr><td style="background:#ffffff;padding:32px;border:1px solid #e5e5e7;border-top:none">
        <p style="margin:0 0 20px;font-size:15px;color:#333">Hi {{ $order->user->name ?? 'there' }},</p>
        <p style="margin:0 0 28px;font-size:15px;color:#333;line-height:1.6">
          Thank you for your purchase. Below {{ count($licenses) > 1 ? 'are your license keys' : 'is your license key' }} for
          <strong>{{ $order->product_name }}</strong>.
          @if($order->amount_usd == 0) <span style="color:#059669">(Applied promo: {{ $order->promo_code }})</span>@endif
        </p>

        {{-- License cards --}}
        @foreach($licenses as $lic)
        <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:20px;background:#f9f9fb;border:1px solid #e5e5e7;border-left:4px solid #d4af37;border-radius:10px">
          <tr><td style="padding:20px">
            <p style="margin:0 0 6px;font-size:11px;letter-spacing:.1em;text-transform:uppercase;color:#888;font-weight:700">{{ $lic['product_name'] }} — License Key</p>
            <p style="margin:0 0 12px;font-size:17px;font-weight:700;font-family:'Courier New',monospace;letter-spacing:.12em;color:#111;word-break:break-all">{{ $lic['license_key'] }}</p>
            <p style="margin:0;font-size:12px;color:#888">Enter this key in the panel on first launch to activate. Keep it safe.</p>
          </td></tr>
        </table>
        @endforeach

        {{-- Next steps --}}
        <h3 style="margin:28px 0 12px;font-size:15px;color:#111;font-weight:700">Next steps</h3>
        <ol style="margin:0;padding-left:20px;color:#555;line-height:2;font-size:14px">
          <li>Download the installer from your <a href="{{ $dashboardUrl }}" style="color:#7c3aed;text-decoration:none;font-weight:700">dashboard</a></li>
          <li>Run the installer on your Mac</li>
          <li>Open Adobe Premiere Pro → Window → Extensions</li>
          <li>Paste your license key when prompted</li>
        </ol>

        {{-- Dashboard CTA --}}
        <div style="margin:28px 0;text-align:center">
          <a href="{{ $dashboardUrl }}" style="display:inline-block;background:#d4af37;color:#090805;padding:14px 28px;text-decoration:none;border-radius:999px;font-weight:800;font-size:14px">
            Open Dashboard
          </a>
        </div>

        <p style="margin:28px 0 0;font-size:13px;color:#888;border-top:1px solid #e5e5e7;padding-top:20px">
          Questions? Reply to this email or write to
          <a href="mailto:hello@lensmania.ae" style="color:#7c3aed;text-decoration:none">hello@lensmania.ae</a>
          — 14-day refund policy applies.
        </p>
      </td></tr>

      {{-- Footer --}}
      <tr><td style="padding:20px;text-align:center;font-size:12px;color:#aaa">
        &copy; 2026 Lensmania Labs &mdash; CineCut for Premiere Pro
      </td></tr>

    </table>
  </td></tr>
</table>
</body>
</html>
