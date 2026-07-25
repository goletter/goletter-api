<?php

declare(strict_types=1);

use function Hyperf\Support\env;

return [
    // 是否启用 Hyperf 层的客户端证书校验；生产环境通过 Nginx mTLS 后建议开启。
    'verify_client' => filter_var(env('MTLS_VERIFY_CLIENT', false), FILTER_VALIDATE_BOOLEAN),

    // 是否查询 client_certificates 表确认客户端证书存在、未注销、未过期。
    'check_database' => filter_var(env('MTLS_CHECK_DATABASE', true), FILTER_VALIDATE_BOOLEAN),

    // Nginx 透传给 Hyperf 的客户端证书相关 Header 名称。
    'headers' => [
        'verify' => env('MTLS_HEADER_VERIFY', 'X-SSL-Client-Verify'),
        'cn' => env('MTLS_HEADER_CN', 'X-SSL-Client-CN'),
        'dn' => env('MTLS_HEADER_DN', 'X-SSL-Client-DN'),
        'serial' => env('MTLS_HEADER_SERIAL', 'X-SSL-Client-Serial'),
        'fingerprint' => env('MTLS_HEADER_FINGERPRINT', 'X-SSL-Client-Fingerprint'),
    ],

    // 可选白名单，留空表示只要求 Nginx 校验证书成功。
    'allowed_client_cns' => array_values(array_filter(array_map(
        static fn (string $value): string => trim($value),
        explode(',', (string) env('MTLS_ALLOWED_CLIENT_CNS', ''))
    ))),
    'allowed_client_fingerprints' => array_values(array_filter(array_map(
        static fn (string $value): string => strtolower(str_replace(':', '', trim($value))),
        explode(',', (string) env('MTLS_ALLOWED_CLIENT_FINGERPRINTS', ''))
    ))),

    // 证书默认根目录，生成文件会按 ca、clients/{user} 子目录分组。
    'output_dir' => env('MTLS_CERT_OUTPUT_DIR', BASE_PATH . '/cert/mtls'),

    // CA 和普通证书的有效期，单位：天。
    'ca_days' => (int) env('MTLS_CA_DAYS', 3650),
    'cert_days' => (int) env('MTLS_CERT_DAYS', 365),

    // RSA 私钥长度。CA 默认更长，服务端和客户端证书使用 key_bits。
    'key_bits' => (int) env('MTLS_KEY_BITS', 2048),
    'ca_key_bits' => (int) env('MTLS_CA_KEY_BITS', 4096),

    // 证书主体信息，会写入证书的 Distinguished Name。
    'country_name' => env('MTLS_COUNTRY_NAME', 'CN'),
    'state_or_province_name' => env('MTLS_STATE_OR_PROVINCE_NAME', ''),
    'locality_name' => env('MTLS_LOCALITY_NAME', ''),
    'organization_name' => env('MTLS_ORGANIZATION_NAME', 'Goletter'),
    'organizational_unit_name' => env('MTLS_ORGANIZATIONAL_UNIT_NAME', ''),

    // 根 CA 名称，用 mtls:ca 生成 ca.crt / ca.key。
    'ca_common_name' => env('MTLS_CA_COMMON_NAME', 'Goletter Root CA'),

    // 客户端证书默认信息；mtls:client --user 会覆盖输出文件名和默认 CN。
    'client_common_name' => env('MTLS_CLIENT_COMMON_NAME', 'goletter-client'),
    'client_friendly_name' => env('MTLS_CLIENT_FRIENDLY_NAME', 'Goletter Client'),

    // 导出 client.p12 / client.pfx 的密码，也可以通过 mtls:client --password 传入。
    'client_export_password' => env('MTLS_CLIENT_EXPORT_PASSWORD', ''),
];
