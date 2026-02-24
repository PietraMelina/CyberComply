<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::connection('db2')->create('responses', function (Blueprint $table) {
            $table->increments('id');
            $table->string('client_id', 14);
            $table->unsignedInteger('question_id');
            $table->unsignedInteger('site_id')->nullable();
            $table->integer('version')->default(1);
            $table->enum('status', ['CONFORME', 'PARCIAL', 'NAO_CONFORME', 'NAO_APLICA']);
            $table->text('comment')->nullable();
            $table->string('answered_by', 11);
            $table->timestamp('answered_at')->useCurrent();
            $table->unsignedInteger('previous_version_id')->nullable();
            $table->boolean('is_current')->default(true);
            $table->foreign('question_id')->references('id')->on('questions');
            $table->foreign('previous_version_id')->references('id')->on('responses');
            $table->unique(['question_id', 'site_id', 'is_current'], 'unique_current')->where('is_current', true);
            $table->index('client_id', 'idx_client_id');
        });
    }
    public function down() {
        Schema::connection('db2')->dropIfExists('responses');
    }
};
