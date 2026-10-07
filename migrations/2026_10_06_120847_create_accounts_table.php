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
        Schema::create('accounts', function (Blueprint $table) {
            $table->comment('账号表');
            $table->bigIncrements('id');
            $table->bigInteger('tenant_id')->default(0)->comment('租户ID');
            $table->bigInteger('business_id')->default(0)->comment('业务线/BM等，串行队列用');
            $table->string('name', 100)->default('')->comment('账户名称');
            $table->string('code', 100)->default('')->unique()->comment('账户ID');
            $table->tinyInteger('status')->default(1)->comment('状态');
            $table->datetimes();

            $table->index('tenant_id', 'idx_accounts_tenant');
            $table->index(['tenant_id', 'business_id'], 'idx_accounts_tenant_business');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('accounts');
    }
};
