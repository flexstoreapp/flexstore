@php
    $bg = $themeColor ?? '#111827';
    $align = ($isRtl ?? false) ? 'right' : 'left';
@endphp
<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="margin: 16px 0 20px;">
    <tr>
        <td align="{{ $align }}" valign="middle">
            <!--[if mso]>
            <v:roundrect xmlns:v="urn:schemas-microsoft-com:vml" xmlns:w="urn:schemas-microsoft-com:office:word" href="{{ $url }}" style="height:42px;v-text-anchor:middle;width:200px;" arcsize="20%" stroke="f" fillcolor="{{ $bg }}">
                <w:anchorlock/>
                <center style="color:#ffffff;font-family:Arial,sans-serif;font-size:14px;font-weight:600;">{{ $label }}</center>
            </v:roundrect>
            <![endif]-->
            <!--[if !mso]><!-- -->
            <table role="presentation" cellpadding="0" cellspacing="0" border="0">
                <tr>
                    <td bgcolor="{{ $bg }}" style="border-radius: 8px; background-color: {{ $bg }};">
                        <a href="{{ $url }}" target="_blank" style="display:inline-block; padding:12px 22px; background-color:{{ $bg }}; color:#ffffff; font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,'Helvetica Neue',Arial,sans-serif; font-size:14px; font-weight:600; text-decoration:none; border-radius:8px; line-height:1; mso-hide:all;">{{ $label }}</a>
                    </td>
                </tr>
            </table>
            <!--<![endif]-->
        </td>
    </tr>
</table>
