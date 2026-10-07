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
        Schema::create('tenants', function (Blueprint $table) {
            $table->comment('租户表');
            $table->bigIncrements('id');
            $table->string('name', 100)->default('')->comment('租户名称')->index();
            $table->string('code', 50)->default('')->unique()->comment('租户编码（唯一）')->index();
            $table->tinyInteger('status')->default(1)->comment('1启用 0禁用');
            $table->datetimes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};
