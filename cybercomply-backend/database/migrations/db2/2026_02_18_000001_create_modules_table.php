<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::connection('db2')->create('modules', function (Blueprint $table) {
            $table->increments('id');
            $table->string('client_id', 14);
            $table->string('code', 20);
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->integer('version')->default(1);
            $table->boolean('is_active')->default(true);
            $table->string('created_by', 11);
            $table->timestamp('created_at')->useCurrent();
            $table->unique(['client_id', 'code', 'version'], 'unique_module_client');
            $table->index('client_id', 'idx_client_id');
        });
    }
    public function down() {
        Schema::connection('db2')->dropIfExists('modules');
    }
};
