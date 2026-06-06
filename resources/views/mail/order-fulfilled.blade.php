<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; color: #333;">
  <div style="background: linear-gradient(135deg, #7c3aed 0%, #a78bfa 100%); color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0;">
    <h1 style="margin: 0; font-size: 24px;">{{ $productName }} License Activated</h1>
    <p style="margin: 10px 0 0; font-size: 14px;">Your download and activation key are ready</p>
  </div>

  <div style="background: white; padding: 30px; border: 1px solid #ddd; border-top: none;">
    <p>Hi {{ $order->user->name }},</p>

    <p>Thank you for your purchase! Your <strong>{{ $productName }}</strong> is ready to use.</p>

    <div style="background: #f5f5f5; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #7c3aed;">
      <p style="margin: 0 0 10px; font-size: 12px; color: #666;">LICENSE KEY</p>
      <p style="margin: 0; font-size: 18px; font-weight: bold; font-family: 'Courier New', monospace; letter-spacing: 2px;">
        {{ $order->license_key }}
      </p>
      <p style="margin: 10px 0 0; font-size: 12px; color: #666;">Save this key - you'll need it to activate the panel in Adobe Premiere Pro</p>
    </div>

    <div style="margin: 25px 0;">
      <a href="{{ $downloadUrl }}" style="display: inline-block; background: #7c3aed; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; font-weight: bold;">
        Download {{ $productName }}
      </a>
      <p style="font-size: 12px; color: #666; margin: 10px 0 0;">Or copy this link: <code style="background: #f5f5f5; padding: 4px 8px; border-radius: 4px;">{{ $downloadUrl }}</code></p>
    </div>

    <h3 style="font-size: 16px; margin: 25px 0 10px;">Next Steps:</h3>
    <ol style="line-height: 1.8;">
      <li>Download the installer from the link above</li>
      <li>Run the installer on your Mac or Windows machine</li>
      <li>Open Adobe Premiere Pro</li>
      <li>Go to Window &gt; Extensions &gt; {{ explode(' ', $productName)[0] }}</li>
      <li>Paste your license key when prompted</li>
      <li>Start using the panel!</li>
    </ol>

    <p style="margin-top: 25px; color: #666; font-size: 13px;">
      Questions? Reply to this email or contact <a href="mailto:hello@lensmania.ae" style="color: #7c3aed; text-decoration: none;">hello@lensmania.ae</a>
    </p>

    <div style="border-top: 1px solid #ddd; margin-top: 30px; padding-top: 20px; text-align: center; font-size: 12px; color: #999;">
      <p style="margin: 0;">&copy; 2026 Lensmania Labs. All rights reserved.</p>
      <p style="margin: 5px 0 0;">This is an automated email. Your account is secure.</p>
    </div>
  </div>
</div>
