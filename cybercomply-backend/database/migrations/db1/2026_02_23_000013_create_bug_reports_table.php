<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::connection('db1')->create('bug_reports', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->string('reporter_user_id', 11);
            $table->string('reporter_email', 255)->nullable();
            $table->string('client_id', 14)->nullable();
            $table->string('title', 150);
            $table->text('description');
            $table->enum('severity', ['LOW', 'MEDIUM', 'HIGH', 'CRITICAL'])->default('MEDIUM');
            $table->string('page_url', 500)->nullable();
            $table->text('user_agent')->nullable();
            $table->enum('status', ['OPEN', 'IN_PROGRESS', 'RESOLVED'])->default('OPEN');
            $table->timestamps();

            $table->index(['status', 'severity'], 'idx_bug_status_severity');
            $table->index(['client_id', 'created_at'], 'idx_bug_client_created');
            $table->index(['reporter_user_id', 'created_at'], 'idx_bug_reporter_created');
            $table->foreign('reporter_user_id')->references('id')->on('users');
            $table->foreign('client_id')->references('id')->on('clients');
        });
    }

    public function down(): void
    {
        Schema::connection('db1')->dropIfExists('bug_reports');
    }
};
