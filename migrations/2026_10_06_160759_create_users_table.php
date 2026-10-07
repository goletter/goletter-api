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
        Schema::create('users', function (Blueprint $table) {
            $table->comment('用户表');
            $table->bigIncrements('id');
            $table->unsignedBigInteger('parent_id')->default(0)->comment('上级');
            $table->unsignedBigInteger('company_id')->default(0)->comment('所属公司');
            $table->unsignedBigInteger('role_id')->default(0)->comment('所属角色');
            $table->string('name', 50)->unique()->comment('用户名');
            $table->tinyInteger('sex')->default(0)->comment('性别；0：男；1：女');
            $table->string('email', 50)->nullable()->comment('邮箱');
            $table->string('nickname', 50)->default('')->comment('昵称');
            $table->string('password', 100)->default('')->nullable()->comment('密码');
            $table->tinyInteger('status')->default(1)->comment('状态；0：关闭；1：启用');
            $table->datetimes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
