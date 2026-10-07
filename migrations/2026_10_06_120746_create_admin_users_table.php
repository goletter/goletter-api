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
        Schema::create('admin_users', function (Blueprint $table) {
            $table->comment('管理员表');
            $table->bigIncrements('id');
            $table->integer('department_id')->default(0)->comment('部门Id');
            $table->integer('role_id')->default(0)->comment('角色Id');
            $table->string('name', 50)->comment('用户名');
            $table->string('realname', 50)->comment('真实姓名');
            $table->string('password')->comment('密码');
            $table->tinyInteger('sex')->default(0)->comment('性别；0：男；1：女');
            $table->tinyInteger('status')->default(1)->comment('状态；0：关闭；1：启用');
            $table->datetimes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admin_users');
    }
};
