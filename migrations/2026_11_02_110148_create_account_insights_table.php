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
        Schema::create('account_insights', function (Blueprint $table) {
            $table->comment('广告账户消耗表');

            $table->bigIncrements('id');
            $table->string('tenant_id', 100)->default(0)->comment('机构');
            $table->string('busine_code', 100)->comment('Bm ID');
            $table->string('code', 100)->comment('账户ID');
            $table->decimal('spend')->default(0)->comment('原消耗');
            $table->date('start_at')->nullable()->index()->comment('开始时间');
            $table->date('end_at')->nullable()->index()->comment('结束时间');
            $table->datetimes();

            $table->unique(['tenant_id', 'code', 'start_at', 'end_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('account_insights');
    }
};
