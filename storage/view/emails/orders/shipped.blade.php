<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>订单已发货</title>
</head>
<body style="margin:0;padding:0;background:#f5f5f5;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;">
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f5f5f5;padding:24px 0;">
    <tr>
        <td align="center">
            <table role="presentation" width="560" cellspacing="0" cellpadding="0" style="background:#ffffff;border-radius:8px;overflow:hidden;">
                <tr>
                    <td style="padding:24px 28px;background:#111827;color:#ffffff;font-size:18px;font-weight:600;">
                        订单已发货
                    </td>
                </tr>
                <tr>
                    <td style="padding:28px;color:#111827;font-size:14px;line-height:1.7;">
                        <p style="margin:0 0 12px;">你好，{{ $userName ?? '用户' }}：</p>
                        <p style="margin:0 0 12px;">你的订单已发货，请注意查收。</p>
                        @if (! empty($orderNo))
                            <p style="margin:0 0 12px;">订单号：<strong>{{ $orderNo }}</strong></p>
                        @endif
                        @if (! empty($trackingNo))
                            <p style="margin:0 0 12px;">物流单号：<strong>{{ $trackingNo }}</strong></p>
                        @endif
                        <p style="margin:24px 0 0;color:#6b7280;font-size:12px;">此邮件由系统发送，请勿直接回复。</p>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
