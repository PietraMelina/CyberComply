<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::connection('db1')->create('roles', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name', 50)->unique();
            $table->enum('type', ['INTERNAL', 'CLIENT']);
            $table->json('permissions');
            $table->boolean('is_system_role')->default(false);
        });
    }
    public function down() {
        Schema::connection('db1')->dropIfExists('roles');
    }
};
