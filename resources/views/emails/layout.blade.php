<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html lang="{{ $locale }}" dir="{{ $isRtl ? 'rtl' : 'ltr' }}" xmlns="http://www.w3.org/1999/xhtml" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="color-scheme" content="light dark">
    <meta name="supported-color-schemes" content="light dark">
    <meta name="x-apple-disable-message-reformatting">
    <meta name="format-detection" content="telephone=no, date=no, address=no, email=no">
    <title>{{ $title ?? $storeName }}</title>
    @hasSection('json_ld')
        <script type="application/ld+json">@yield('json_ld')</script>
    @endif
    <!--[if gte mso 9]>
    <xml>
        <o:OfficeDocumentSettings>
            <o:AllowPNG/>
            <o:PixelsPerInch>96</o:PixelsPerInch>
        </o:OfficeDocumentSettings>
    </xml>
    <![endif]-->
    <!--[if mso]>
    <style type="text/css">
        body, table, td, a { font-family: 'Segoe UI', Arial, sans-serif !important; }
    </style>
    <![endif]-->
    <style>
        :root { color-scheme: light dark; supported-color-schemes: light dark; }
        body { margin: 0 !important; padding: 0 !important; background-color: #f3f4f6; color: #1f2937; -webkit-font-smoothing: antialiased; word-wrap: break-word; mso-line-height-rule: exactly; }
        table { border-collapse: collapse; mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
        td { word-wrap: break-word; mso-line-height-rule: exactly; }
        img { border: 0; text-decoration: none; -ms-interpolation-mode: bicubic; max-width: 100%; height: auto; display: block; }
        a { color: {{ $themeColorDark }}; text-decoration: underline; }
        .es-wrap { width: 100%; background-color: #f3f4f6; }
        .es-card { background-color: #ffffff; border-radius: 12px; }
        .es-body h1 { font-size: 22px; font-weight: 700; margin: 0 0 12px; color: #0f172a; line-height: 1.3; }
        .es-body h2 { font-size: 16px; font-weight: 600; margin: 24px 0 12px; color: #0f172a; line-height: 1.3; }
        .es-body p { margin: 0 0 12px; color: #1f2937; }
        .es-muted { color: #6b7280; font-size: 13px; }
        .es-footer a { color: #6b7280; }
        .es-divider { height: 1px; background-color: #e5e7eb; line-height: 1px; font-size: 1px; }
        .es-logo-light { display: block; }
        .es-logo-dark { display: none; mso-hide: all; }
        @media only screen and (max-width: 480px) {
            .es-wrap-pad { padding: 0 !important; }
            .es-card { border-radius: 0 !important; }
            .es-pad { padding-left: 20px !important; padding-right: 20px !important; }
            .es-stack { display: block !important; width: 100% !important; padding: 0 !important; }
            .es-stack + .es-stack { padding-top: 16px !important; }
            .es-mono { word-break: break-all !important; }
            .es-body h1 { font-size: 20px !important; }
        }

        /* Apple Mail / iOS Mail / modern dark-mode-aware clients */
        @media (prefers-color-scheme: dark) {
            body, .es-wrap,
            body[bgcolor="#f3f4f6"], .es-wrap[bgcolor="#f3f4f6"] {
                background-color: #0b0f17 !important;
            }
            .es-card,
            [bgcolor="#ffffff"] {
                background-color: #18181b !important;
            }
            .es-body, .es-body p { color: #e5e7eb !important; }
            .es-body h1, .es-body h2 { color: #f8fafc !important; }
            .es-muted, .es-footer, .es-footer a { color: #a1a1aa !important; }
            /* Inline-style overrides via attribute selectors */
            [style*="background-color:#f9fafb"], [style*="background-color: #f9fafb"] { background-color: #27272a !important; }
            [style*="border-bottom: 1px solid #e5e7eb"], [style*="border-bottom:1px solid #e5e7eb"],
            [style*="border-top: 1px solid #e5e7eb"], [style*="border-top:1px solid #e5e7eb"] {
                border-color: #3f3f46 !important;
            }
            [style*="color:#0f172a"], [style*="color: #0f172a"],
            [style*="color:#374151"], [style*="color: #374151"] { color: #f8fafc !important; }
            [style*="color:#1f2937"], [style*="color: #1f2937"] { color: #e5e7eb !important; }
            [style*="color:#6b7280"], [style*="color: #6b7280"] { color: #a1a1aa !important; }
            [style*="color:#9ca3af"], [style*="color: #9ca3af"] { color: #71717a !important; }
            [style*="color:#b91c1c"], [style*="color: #b91c1c"] { color: #f87171 !important; }
            [style*="color:#2563eb"], [style*="color: #2563eb"] { color: #60a5fa !important; }
            [style*="color:#b45309"], [style*="color: #b45309"] { color: #fbbf24 !important; }
            /* Logo swap */
            .es-logo-light { display: none !important; mso-hide: all !important; }
            .es-logo-dark { display: block !important; }
            /* Theme color (button + tracking link) — swap 900 shade for 600 so it stays visible on the dark card */
            [bgcolor="{{ $themeColor }}"],
            [style*="background-color:{{ $themeColor }}"],
            [style*="background-color: {{ $themeColor }}"] { background-color: {{ $themeColorDark }} !important; }
            /* Force button text to stay white — some dark-mode clients (Apple Mail) re-color link text on dark backgrounds */
            a[style*="background-color:{{ $themeColor }}"],
            a[style*="background-color: {{ $themeColor }}"] { color: #ffffff !important; }
        }

        /* Outlook.com / Outlook iOS / Outlook Android dark mode */
        [data-ogsc] body, [data-ogsc] .es-wrap { background-color: #0b0f17 !important; }
        [data-ogsc] .es-card, [data-ogsb] .es-card { background-color: #18181b !important; }
        [data-ogsc] .es-body, [data-ogsc] .es-body p { color: #e5e7eb !important; }
        [data-ogsc] .es-body h1, [data-ogsc] .es-body h2 { color: #f8fafc !important; }
        [data-ogsc] .es-muted, [data-ogsc] .es-footer, [data-ogsc] .es-footer a { color: #a1a1aa !important; }
        [data-ogsc] [style*="background-color:#f9fafb"], [data-ogsc] [style*="background-color: #f9fafb"] { background-color: #27272a !important; }
        [data-ogsc] [style*="border-bottom: 1px solid #e5e7eb"], [data-ogsc] [style*="border-bottom:1px solid #e5e7eb"],
        [data-ogsc] [style*="border-top: 1px solid #e5e7eb"], [data-ogsc] [style*="border-top:1px solid #e5e7eb"] {
            border-color: #3f3f46 !important;
        }
        [data-ogsc] [style*="color:#0f172a"], [data-ogsc] [style*="color: #0f172a"],
        [data-ogsc] [style*="color:#374151"], [data-ogsc] [style*="color: #374151"] { color: #f8fafc !important; }
        [data-ogsc] [style*="color:#1f2937"], [data-ogsc] [style*="color: #1f2937"] { color: #e5e7eb !important; }
        [data-ogsc] [style*="color:#6b7280"], [data-ogsc] [style*="color: #6b7280"] { color: #a1a1aa !important; }
        [data-ogsc] [style*="color:#9ca3af"], [data-ogsc] [style*="color: #9ca3af"] { color: #71717a !important; }
        [data-ogsc] [style*="color:#b91c1c"], [data-ogsc] [style*="color: #b91c1c"] { color: #f87171 !important; }
        [data-ogsc] [style*="color:#2563eb"], [data-ogsc] [style*="color: #2563eb"] { color: #60a5fa !important; }
        [data-ogsc] [style*="color:#b45309"], [data-ogsc] [style*="color: #b45309"] { color: #fbbf24 !important; }
        [data-ogsc] .es-logo-light { display: none !important; mso-hide: all !important; }
        [data-ogsc] .es-logo-dark { display: block !important; }
        [data-ogsc] [bgcolor="{{ $themeColor }}"],
        [data-ogsc] [style*="background-color:{{ $themeColor }}"],
        [data-ogsc] [style*="background-color: {{ $themeColor }}"] { background-color: {{ $themeColorDark }} !important; }
        [data-ogsc] a[style*="background-color:{{ $themeColor }}"],
        [data-ogsc] a[style*="background-color: {{ $themeColor }}"] { color: #ffffff !important; }
    </style>
</head>
<body style="margin:0; padding:0; background-color:#f3f4f6; word-wrap:break-word;" bgcolor="#f3f4f6">
    @hasSection('preheader')
        <div style="display:none; max-height:0; overflow:hidden; opacity:0; visibility:hidden; mso-hide:all; font-size:1px; line-height:1px; color:#f3f4f6;">@yield('preheader')</div>
    @endif

    <table role="presentation" class="es-wrap" cellpadding="0" cellspacing="0" border="0" width="100%" bgcolor="#f3f4f6" style="background-color:#f3f4f6;">
        <tr>
            <td align="center" valign="top" class="es-wrap-pad" style="padding: 24px 12px;">
                <!--[if mso | IE]>
                <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="600" align="center"><tr><td>
                <![endif]-->
                <table role="presentation" class="es-card" cellpadding="0" cellspacing="0" border="0" width="100%" bgcolor="#ffffff" style="max-width: 600px; background-color:#ffffff; border-radius:12px;">
                    <tr>
                        <td class="es-pad" align="{{ $isRtl ? 'right' : 'left' }}" valign="top" style="padding: 24px 32px; border-bottom: 1px solid #e5e7eb; background-color:#ffffff;" bgcolor="#ffffff">
                            <a href="{{ $storefrontUrl }}" style="text-decoration:none; color:#0f172a;">
                                @if ($storeLogo && ($storeLogo['url'] ?? null))
                                    <img src="{{ $storeLogo['url'] }}" alt="{{ $storeName }}" height="36" class="es-logo-light" style="display:block; max-height:36px; width:auto; border:0;">
                                    @if ($storeLogoDark && ($storeLogoDark['url'] ?? null))
                                        <img src="{{ $storeLogoDark['url'] }}" alt="{{ $storeName }}" height="36" class="es-logo-dark" style="display:none; max-height:36px; width:auto; border:0;">
                                    @endif
                                @else
                                    <span style="font-size:20px; font-weight:700; color:#0f172a; font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,'Helvetica Neue',Arial,sans-serif;">{{ $storeName }}</span>
                                @endif
                            </a>
                        </td>
                    </tr>
                    <tr>
                        <td class="es-pad es-body" valign="top" style="padding: 28px 32px 8px; color:#1f2937; background-color:#ffffff; font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,'Helvetica Neue',Arial,sans-serif; font-size:15px; line-height:1.6;" bgcolor="#ffffff">
                            @yield('content')
                        </td>
                    </tr>
                    <tr>
                        <td class="es-pad es-footer" align="center" valign="top" style="padding: 24px 32px 28px; border-top: 1px solid #e5e7eb; background-color:#ffffff; font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,'Helvetica Neue',Arial,sans-serif; font-size:12px; color:#6b7280;" bgcolor="#ffffff">
                            <div style="font-weight:600; color:#374151; margin-bottom:6px;">{{ $storeName }}</div>
                            @foreach ($storeAddressLines as $line)
                                <div style="color:#6b7280;">{{ $line }}</div>
                            @endforeach
                            @if ($storeEmail || $storePhone)
                                <div style="margin-top:8px; color:#6b7280;">
                                    @if ($storeEmail)
                                        <a href="mailto:{{ $storeEmail }}" style="color:#6b7280; text-decoration:underline;">{{ $storeEmail }}</a>
                                    @endif
                                    @if ($storeEmail && $storePhone) &middot; @endif
                                    @if ($storePhone)
                                        <a href="tel:{{ preg_replace('/[^+\d]/', '', (string) $storePhone) }}" style="color:#6b7280; text-decoration:underline;"><span dir="ltr">{{ $storePhone }}</span></a>
                                    @endif
                                </div>
                            @endif
                            @if (! empty($socialLinks))
                                <div style="margin-top:12px;">
                                    @foreach ($socialLinks as $name => $url)
                                        <a href="{{ $url }}" style="margin:0 6px; color:#6b7280; text-decoration:underline;">{{ $name }}</a>
                                    @endforeach
                                </div>
                            @endif
                            <div style="margin-top:14px; color:#6b7280;">{{ __('© :year :store. All rights reserved.', ['year' => date('Y'), 'store' => $storeName]) }}</div>
                        </td>
                    </tr>
                </table>
                <!--[if mso | IE]>
                </td></tr></table>
                <![endif]-->
            </td>
        </tr>
    </table>
</body>
</html>
