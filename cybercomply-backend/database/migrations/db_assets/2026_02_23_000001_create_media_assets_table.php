<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::connection('db_assets')->create('media_assets', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->string('owner_type', 20); // user | client
            $table->string('owner_id', 32);
            $table->string('asset_type', 20); // avatar | logo
            $table->string('file_name', 255);
            $table->string('mime_type', 100);
            $table->unsignedInteger('file_size_bytes');
            $table->string('storage_path', 500);
            $table->string('checksum_sha256', 64);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['owner_type', 'owner_id'], 'idx_owner');
            $table->index(['asset_type', 'is_active'], 'idx_type_active');
        });
    }

    public function down(): void
    {
        Schema::connection('db_assets')->dropIfExists('media_assets');
    }
};
