# Goletter Hyperf mTLS

Hyperf command package for generating local mTLS certificates.

## Commands

Generate a local root CA:

```bash
php bin/hyperf.php mtls:ca --cn "Goletter Root CA"
```

Generate a client certificate and PKCS#12 installer files:

```bash
php bin/hyperf.php mtls:client \
  --user user-001 \
  --password 123456
```

Generate different client certificates for different users by changing `--user`:

```bash
php bin/hyperf.php mtls:client --user alice --password 123456
php bin/hyperf.php mtls:client --user bob --password 123456
```

When a client certificate is generated, its metadata is stored in the `client_certificates` table.

## Output

By default, files are written under `runtime/certs` and grouped by certificate role.

```text
runtime/certs/
  ca/
    ca.crt
    ca.key
  clients/
    alice/
      alice.crt
      alice.key
      alice.p12
      alice.pfx
    bob/
      bob.crt
      bob.key
      bob.p12
      bob.pfx
```

Use the generated `.p12` file on macOS Keychain Access and the generated `.pfx` file on Windows Certificate Manager. Install `ca.crt` as a trusted root certificate when the client machine needs to trust certificates signed by this local CA.

Use a public CA certificate, such as Let's Encrypt or a cloud provider SSL certificate, for the server side of HTTPS. This package only generates the private CA and client certificates used for mTLS client authentication.

## Middleware

Register `Goletter\Mtls\Middleware\ClientCertificateMiddleware::class` in the Hyperf HTTP middleware stack, then enable verification:

```env
MTLS_VERIFY_CLIENT=true
MTLS_CHECK_DATABASE=true
```

The middleware expects Nginx to verify the client certificate first and pass the result to Hyperf:

```nginx
ssl_client_certificate /path/to/runtime/certs/ca/ca.crt;
ssl_verify_client on;

proxy_set_header X-SSL-Client-Verify $ssl_client_verify;
proxy_set_header X-SSL-Client-CN $ssl_client_s_dn_cn;
proxy_set_header X-SSL-Client-DN $ssl_client_s_dn;
proxy_set_header X-SSL-Client-Serial $ssl_client_serial;
proxy_set_header X-SSL-Client-Fingerprint $ssl_client_fingerprint;
```

Optional allow lists:

```env
MTLS_ALLOWED_CLIENT_CNS=alice,bob
MTLS_ALLOWED_CLIENT_FINGERPRINTS=8F2A...,A94B...
```

When `MTLS_CHECK_DATABASE=true`, the middleware also checks `client_certificates`:

```text
fingerprint or serial must exist
status must be active
expires_at must not be expired
```

Set `status=revoked` to revoke a client certificate. The next request with that certificate will return `403`.

When the certificate passes, the middleware adds these request attributes:

```text
mtls_client_cn
mtls_client_dn
mtls_client_serial
mtls_client_fingerprint
mtls_client_certificate_id
mtls_client_user
```

## Admin Download API

The project can expose an admin API that generates a client certificate and returns the installer file as a download:

```http
POST /api/admin/mtls/client-certificates
Content-Type: application/json

{
  "user": "alice",
  "password": "123456",
  "format": "p12"
}
```

Supported request fields:

```text
user           Required. User/client identifier and output folder name.
password       Required. Password for the p12/pfx installer file.
format         Optional. p12 or pfx, defaults to p12.
cn             Optional. Certificate CN, defaults to user.
friendly_name  Optional. PKCS#12 friendly name, defaults to user.
```

The generated files are stored under `runtime/certs/clients/{user}` and the selected `p12` or `pfx` file is returned as an attachment.

The generated certificate metadata is also stored in `client_certificates`, including:

```text
user
cn
serial
fingerprint
status
cert_path
key_path
p12_path
pfx_path
issued_at
expires_at
```

Run the migration before using the command or admin API:

```bash
php bin/hyperf.php migrate
```

## Configuration

Publish or copy `publish/mtls.php` to `config/autoload/mtls.php`.

Supported environment variables:

```env
MTLS_VERIFY_CLIENT=false
MTLS_CHECK_DATABASE=true
MTLS_ALLOWED_CLIENT_CNS=
MTLS_ALLOWED_CLIENT_FINGERPRINTS=
MTLS_CERT_OUTPUT_DIR=/path/to/certs
MTLS_CA_DAYS=3650
MTLS_CERT_DAYS=365
MTLS_KEY_BITS=2048
MTLS_CA_KEY_BITS=4096
MTLS_COUNTRY_NAME=CN
MTLS_ORGANIZATION_NAME=Goletter
MTLS_CA_COMMON_NAME="Goletter Root CA"
MTLS_CLIENT_COMMON_NAME=goletter-client
MTLS_CLIENT_FRIENDLY_NAME="Goletter Client"
MTLS_CLIENT_EXPORT_PASSWORD=changeit
```

Private keys are written with `0600` permissions. Keep generated private keys and PKCS#12 files out of version control.
