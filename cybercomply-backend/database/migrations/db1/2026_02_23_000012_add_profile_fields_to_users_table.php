<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::connection('db1')->table('users', function (Blueprint $table): void {
            if (!Schema::connection('db1')->hasColumn('users', 'display_name')) {
                $table->string('display_name', 120)->nullable()->after('email');
            }

            if (!Schema::connection('db1')->hasColumn('users', 'avatar_asset_id')) {
                $table->unsignedBigInteger('avatar_asset_id')->nullable()->after('mfa_backup_codes');
            }
        });
    }

    public function down(): void
    {
        Schema::connection('db1')->table('users', function (Blueprint $table): void {
            if (Schema::connection('db1')->hasColumn('users', 'avatar_asset_id')) {
                $table->dropColumn('avatar_asset_id');
            }
            if (Schema::connection('db1')->hasColumn('users', 'display_name')) {
                $table->dropColumn('display_name');
            }
        });
    }
};
