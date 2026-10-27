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
        Schema::create('docs_platform_configs', function (Blueprint $table) {
            $table->comment('文档配置表');

            $table->bigIncrements('id');
            $table->string('platform', 32)->default('')->comment('平台');
            $table->string('tenant_id', 100)->default('')->comment('机构');
            $table->string('client_id', 100)->default('')->nullable()->comment('应用Id');
            $table->string('client_secret', 100)->default('')->nullable()->comment('应用Secret');
            $table->string('redirect_uri', 100)->default('')->nullable()->comment('应用回调地址');
            $table->string('remark', 200)->nullable()->comment('备注');
            $table->datetimes();

            $table->index(['platform', 'tenant_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('docs_platform_configs');
    }
};
