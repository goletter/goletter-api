<?php

declare(strict_types=1);
/**
 * This file is part of goletter/mail.
 *
 * @link     https://github.com/goletter/hyperf-mail
 * @contact  goletter@outlook.com
 * @license  https://github.com/goletter/hyperf-mail/blob/master/LICENSE
 */
use function Hyperf\Support\env;

return [
    /*
    |--------------------------------------------------------------------------
    | Default Mailer
    |--------------------------------------------------------------------------
    |
    | This option controls the default mailer that is used to send any email
    | messages sent by your application. Alternative mailers may be setup
    | and used as needed; however, this mailer will be used by default.
    |
    */

    'default' => env('MAIL_MAILER', 'smtp'),

    /*
    |--------------------------------------------------------------------------
    | Mailer Configurations
    |--------------------------------------------------------------------------
    |
    | Here you may configure all of the mailers used by your application plus
    | their respective settings. Several examples have been configured for
    | you and you are free to add your own as your application requires.
    |
    | Supports a variety of mail "transport" drivers to be used while
    | sending an e-mail. You will specify which one you are using for your
    | mailers below. You are free to add additional mailers as required.
    |
    */

    'mailers' => [
        'smtp' => [
            /*
            | 推荐：在 .env 中使用离散配置
            |
            | MAIL_MAILER=smtp
            | MAIL_SMTP_HOST=smtp.hostinger.com
            | MAIL_SMTP_PORT=587
            | MAIL_SMTP_ENCRYPTION=tls   # tls=587 STARTTLS，ssl=465 隐式 TLS
            | MAIL_SMTP_USERNAME=you@example.com
            | MAIL_SMTP_PASSWORD=secret
            | MAIL_FROM_ADDRESS=you@example.com
            | MAIL_FROM_NAME="Example"
            |
            | 可选：设置 MAIL_SMTP_DSN 后将优先生效，例如
            | MAIL_SMTP_DSN=smtp://user:pass@smtp.example.com:587
            */
            'host' => env('MAIL_SMTP_HOST'),
            'port' => (int) env('MAIL_SMTP_PORT', 587),
            'encryption' => env('MAIL_SMTP_ENCRYPTION', 'tls'), // tls | ssl | null
            'username' => env('MAIL_SMTP_USERNAME'),
            'password' => env('MAIL_SMTP_PASSWORD'),
            'dsn' => env('MAIL_SMTP_DSN'),
        ],

        'aws_ses' => [
            // ses+smtp://USERNAME:PASSWORD@default
            // ses+https://ACCESS_KEY:SECRET_KEY@default
            // ses+api://ACCESS_KEY:SECRET_KEY@default
            'dsn' => env('MAIL_AWS_SES_DSN'),
        ],

        'mandrill' => [
            // mandrill+smtp://USERNAME:PASSWORD@default
            // mandrill+https://KEY@default
            // mandrill+api://KEY@default
            'dsn' => env('MAIL_MANDRILL_DSN'),
        ],

        'mailgun' => [
            // mailgun+smtp://USERNAME:PASSWORD@default
            // mailgun+https://KEY:DOMAIN@default
            // mailgun+api://KEY:DOMAIN@default
            'dsn' => env('MAIL_MAILGUN_DSN'),
        ],

        'postmark' => [
            // postmark+smtp://ID@default
            // postmark+api://KEY@default
            'dsn' => env('MAIL_POSTMARK_DSN'),
        ],

        'gmail' => [
            /*
            | 需先安装：composer require symfony/google-mailer
            |
            | MAIL_MAILER=gmail
            | MAIL_GMAIL_DSN=gmail+smtp://USERNAME:APP-PASSWORD@default
            |
            | 说明：
            | - 账号需开启两步验证，并使用「应用专用密码」（App Password）
            | - 用户名、密码中的 @ 等特殊字符请 URL 编码（@ → %40）
            | - Symfony 官方建议仅用于开发/测试，生产环境请使用专业邮件服务
            */
            'dsn' => env('MAIL_GMAIL_DSN'),
        ],

        'sendmail' => [
            'dsn' => 'sendmail://default',
        ],

        'aliyun_dm' => [
            'transport' => \Goletter\Mail\Transport\AliyunDmTransport::class,
            'options' => [
                'access_key_id' => env('MAIL_ALIYUN_DM_ACCESS_KEY_ID'),
                'access_secret' => env('MAIL_ALIYUN_DM_ACCESS_SECRET'),
                'region_id' => env('MAIL_ALIYUN_DM_REGION_ID'),
                'click_trace' => env('MAIL_ALIYUN_DM_CLICK_TRACE', '0'),
            ],
        ],

        'resend' => [
            'transport' => \Goletter\Mail\Transport\ResendTransport::class,
            'options' => [
                'access_key_id' => env('MAIL_RESEND_ACCESS_KEY_ID'),
            ],
        ],

        'log' => [
            'transport' => \Goletter\Mail\Transport\LogTransport::class,
            'options' => [
                'name' => 'mail.local',
                'group' => 'default',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Global "From" Address
    |--------------------------------------------------------------------------
    |
    | You may wish for all e-mails sent by your application to be sent from
    | the same address. Here, you may specify a name and address that is
    | used globally for all e-mails that are sent by your application.
    |
    */

    'from' => [
        'address' => env('MAIL_FROM_ADDRESS', 'hello@example.com'),
        'name' => env('MAIL_FROM_NAME', 'Example'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Logger Options
    |--------------------------------------------------------------------------
    |
    | The `hyperf/logger` component is required if enabled.
    */

    'logger' => [
        'enabled' => false,
        'name' => 'mail',
        'group' => 'default',
    ],
];
