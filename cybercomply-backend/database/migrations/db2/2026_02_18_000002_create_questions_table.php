<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::connection('db2')->create('questions', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('module_id');
            $table->string('client_id', 14);
            $table->text('question_text');
            $table->decimal('weight', 5, 2)->default(1.00);
            $table->integer('order_index');
            $table->boolean('is_active')->default(true);
            $table->timestamp('created_at')->useCurrent();
            $table->foreign('module_id')->references('id')->on('modules');
            $table->index('client_id', 'idx_client_id');
        });
    }
    public function down() {
        Schema::connection('db2')->dropIfExists('questions');
    }
};
