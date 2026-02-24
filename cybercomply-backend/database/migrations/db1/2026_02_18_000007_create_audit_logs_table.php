<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::connection('db1')->create('audit_logs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('log_id', 36)->unique();
            $table->string('user_id', 11);
            $table->string('client_id', 14)->nullable();
            $table->string('action', 50);
            $table->string('entity_type', 50);
            $table->string('entity_id', 255);
            $table->string('ip_address', 45);
            $table->text('user_agent')->nullable();
            $table->json('before_state')->nullable();
            $table->json('after_state')->nullable();
            $table->json('changes_summary')->nullable();
            $table->string('session_id', 128)->nullable();
            $table->string('request_id', 36)->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->foreign('user_id')->references('id')->on('users');
            $table->foreign('client_id')->references('id')->on('clients');
            $table->index(['user_id', 'created_at'], 'idx_audit_user');
            $table->index(['client_id', 'created_at'], 'idx_audit_client');
            $table->index(['entity_type', 'entity_id'], 'idx_audit_entity');
            $table->index(['action', 'created_at'], 'idx_audit_action');
        });
    }
    public function down() {
        Schema::connection('db1')->dropIfExists('audit_logs');
    }
};
