<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::connection('db1')->create('users', function (Blueprint $table) {
            $table->string('id', 11)->primary(); // CYBR-A9K3XZ
            $table->string('client_id', 14)->nullable();
            $table->string('email', 255)->unique();
            $table->string('password_hash', 255);
            $table->unsignedInteger('role_id');
            $table->boolean('is_active')->default(true);
            $table->timestamp('email_verified_at')->nullable();
            $table->boolean('mfa_enabled')->default(false);
            $table->string('mfa_secret', 32)->nullable();
            $table->timestamp('accepted_terms_at')->nullable();
            $table->string('accepted_terms_version', 10)->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->timestamps();
            $table->foreign('client_id')->references('id')->on('clients');
            $table->foreign('role_id')->references('id')->on('roles');
        });
    }
    public function down() {
        Schema::connection('db1')->dropIfExists('users');
    }
};
