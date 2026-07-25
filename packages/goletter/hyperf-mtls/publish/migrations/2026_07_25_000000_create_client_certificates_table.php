<?php

declare(strict_types=1);

use Hyperf\Database\Migrations\Migration;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Schema\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('client_certificates', function (Blueprint $table): void {
            $table->comment('mTLS 客户端证书签发、下载与注销记录表');

            // 记录 mTLS 客户端证书的签发、下载文件路径和注销状态。
            $table->bigIncrements('id')->comment('主键 ID');
            $table->string('user', 191)->index()->comment('用户或客户端标识，对应生成证书时的 user 参数');
            $table->string('cn', 191)->default('')->comment('客户端证书 Subject Common Name');
            $table->string('serial', 191)->default('')->index()->comment('客户端证书序列号，来自 X-SSL-Client-Serial');
            $table->string('fingerprint', 191)->unique()->comment('客户端证书 SHA256 指纹，去掉冒号并转为小写');
            $table->string('status', 32)->default('active')->index()->comment('证书状态：active=启用，revoked=已注销');
            $table->string('cert_path')->default('')->comment('客户端 crt 证书文件路径');
            $table->string('key_path')->default('')->comment('客户端私钥文件路径，敏感文件，不应放到 public 目录');
            $table->string('p12_path')->default('')->comment('macOS 可安装的 p12 文件路径');
            $table->string('pfx_path')->default('')->comment('Windows 可安装的 pfx 文件路径');
            $table->timestamp('issued_at')->nullable()->comment('证书签发时间，来自证书 validFrom');
            $table->timestamp('expires_at')->nullable()->index()->comment('证书过期时间，来自证书 validTo');
            $table->timestamp('revoked_at')->nullable()->comment('证书注销时间');
            $table->string('revoked_reason')->default('')->comment('证书注销原因');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_certificates');
    }
};
