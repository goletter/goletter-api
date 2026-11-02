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
        Schema::create('account_adv_reports', function (Blueprint $table) {
            $table->comment('广告账户报告表');

            $table->bigIncrements('id');
            $table->string('tenant_id', 100)->default(0)->index()->comment('机构');
            $table->string('code', 100)->index()->comment('账户ID');
            $table->string('spend', 100)->default('')->nullable()->comment('源花费金额');
            $table->string('dollar', 100)->default('')->nullable()->comment('货币转后花费金额');
            $table->string('campaign_name')->default('')->nullable()->comment('广告系列名称');
            $table->string('campaign_id')->default('')->nullable()->comment('广告系列ID');
            $table->string('reach')->default('')->nullable()->comment('覆盖人数');
            $table->string('impressions')->default('')->nullable()->comment('展示次数');
            $table->string('frequency')->default('')->nullable()->comment('频次');
            $table->string('clicks')->default('')->nullable()->comment('链接点击量');
            $table->string('cpc')->default('')->nullable()->comment('CPC');
            $table->string('purchase')->default('')->nullable()->comment('购物次数');
            $table->string('cost_per_purchase')->default('')->nullable()->comment('CPP');
            $table->string('roas')->default('')->nullable()->comment('ROAS');
            $table->string('reg')->default('')->nullable()->comment('完成注册次数');
            $table->string('cost_per_reg')->default('')->nullable()->comment('注册成本');
            $table->date('start_at')->nullable()->comment('开始日期');
            $table->date('end_at')->nullable()->comment('结束日期');
            $table->datetimes();

            $table->unique(['code', 'campaign_id', 'start_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('account_adv_reports');
    }
};
