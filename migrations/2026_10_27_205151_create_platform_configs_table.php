<?php

use Hyperf\Database\Schema\Schema;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('platform_configs', function (Blueprint $table) {
            $table->comment('文档配置表');

            $table->bigIncrements('id');
            $table->tinyInteger('type')->default(0)->comment('类型');
            $table->string('tenant_id', 100)->default('')->comment('机构Id');
            $table->string('client_id', 100)->default('')->nullable()->comment('应用Id');
            $table->string('client_secret', 100)->default('')->nullable()->comment('应用Secret');
            $table->string('redirect_uri', 100)->default('')->nullable()->comment('应用回调地址');
            $table->string('token', 200)->default('')->comment('token');
            $table->string('refresh_token', 200)->default('')->comment('refresh_token');
            $table->string('remark', 200)->nullable()->comment('备注');
            $table->tinyInteger('status')->default(1)->comment('状态');
            $table->dateTime('expire_at')->nullable()->comment('过期时间');
            $table->datetimes();

            $table->index(['platform', 'tenant_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('platform_configs');
    }
};
