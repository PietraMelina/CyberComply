<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::connection('db2')->create('evidences', function (Blueprint $table) {
            $table->increments('id');
            $table->string('client_id', 14);
            $table->unsignedInteger('response_id');
            $table->string('internal_token', 64)->unique();
            $table->string('original_filename', 255);
            $table->integer('file_size_bytes');
            $table->string('mime_type', 100);
            $table->string('storage_path', 500);
            $table->string('checksum_sha256', 64);
            $table->string('uploaded_by', 11);
            $table->timestamp('uploaded_at')->useCurrent();
            $table->foreign('response_id')->references('id')->on('responses');
            $table->index('client_id', 'idx_client_id');
        });
    }
    public function down() {
        Schema::connection('db2')->dropIfExists('evidences');
    }
};
