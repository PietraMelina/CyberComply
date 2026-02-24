<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::connection('db1')->table('clients', function (Blueprint $table): void {
            $table->string('representative_nif', 9)->nullable()->unique()->after('vat_number');
            $table->string('representative_name', 255)->nullable()->after('representative_nif');
            $table->timestamp('cmd_validated_at')->nullable()->after('representative_name');
        });

        Schema::connection('db1')->table('users', function (Blueprint $table): void {
            $table->string('nif', 9)->nullable()->unique()->after('client_id');
            $table->index('nif', 'idx_users_nif');
        });
    }

    public function down(): void
    {
        Schema::connection('db1')->table('users', function (Blueprint $table): void {
            $table->dropIndex('idx_users_nif');
            $table->dropUnique(['nif']);
            $table->dropColumn('nif');
        });

        Schema::connection('db1')->table('clients', function (Blueprint $table): void {
            $table->dropUnique(['representative_nif']);
            $table->dropColumn(['representative_nif', 'representative_name', 'cmd_validated_at']);
        });
    }
};

