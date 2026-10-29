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
        Schema::create('account_demands', function (Blueprint $table) {
            $table->comment('广告账户需求表');

            $table->bigIncrements('id');
            $table->string('tenant_id', 100)->default(0)->comment('机构');
            $table->string('platform', 32)->default('')->index()->comment('平台');
            $table->string('order_no', 100)->unique()->index()->comment('需求单号');
            $table->string('name', 100)->default('')->comment('账户名称');
            $table->string('code', 100)->default('')->comment('账户ID');
            $table->tinyInteger('type')->default(0)->comment('类型；1：入户，2：充值，3：减款；4：清零；6：绑定,  7：解绑');
            $table->tinyInteger('step')->default(0)->comment('步骤');
            $table->bigInteger('money')->default(0)->comment('金额');
            $table->string('email', 500)->default('')->nullable()->comment('邮箱/Bm/Bc');
            $table->string('transaction_id', 100)->default('')->nullable()->index()->comment('第三方单号');
            $table->json('extend')->nullable()->comment('扩展');
            $table->string('remark')->default('')->nullable()->comment('备注');
            $table->tinyInteger('status')->default(0)->comment('状态；0：待处理；1：处理中; 2：处理成功；3；处理失败');
            $table->datetimes();

            $table->index(['platform', 'tenant_id', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('account_demands');
    }
};
