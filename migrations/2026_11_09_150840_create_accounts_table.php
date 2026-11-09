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
            $table->comment('广告账户表');

            $table->bigIncrements('id');
            $table->string('tenant_id', 100)->default(0)->comment('机构');
            $table->string('platform', 32)->default('')->index()->comment('平台');
            $table->string('order_no', 100)->unique()->index()->comment('需求单号');
            $table->string('name', 100)->default('')->comment('账户名称');
            $table->string('code', 100)->default('')->comment('账户ID');
            $table->string('busine_code', 100)->default('')->comment('BMID');
            $table->tinyInteger('step')->default(0)->comment('步骤');
            $table->string('currency', 50)->default('')->nullable()->comment('货币');
            $table->string('timezone', 20)->default('')->nullable()->comment('时区');
            $table->string('transaction_id', 100)->default('')->nullable()->index()->comment('第三方单号');
            $table->json('extend')->nullable()->comment('扩展');
            $table->string('remark')->default('')->nullable()->comment('备注');
            $table->tinyInteger('status')->default(0)->comment('状态；0：待处理；1：处理中; 2：处理成功；3；处理失败');
            $table->datetimes();

            $table->index(['platform', 'tenant_id']);
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
