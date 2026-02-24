<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::connection('db1')->create('tokens', function (Blueprint $table) {
            $table->increments('id');
            $table->string('user_id', 11);
            $table->string('token', 64)->unique();
            $table->enum('type', ['MFA', 'EMAIL_VERIFY', 'PASSWORD_RESET', 'DATA_CHANGE']);
            $table->json('payload')->nullable();
            $table->timestamp('expires_at');
            $table->timestamp('used_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->string('cancelled_by', 11)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->foreign('user_id')->references('id')->on('users');
            $table->foreign('cancelled_by')->references('id')->on('users');
            $table->index('expires_at', 'idx_tokens_expires');
        });
    }
    public function down() {
        Schema::connection('db1')->dropIfExists('tokens');
    }
};
