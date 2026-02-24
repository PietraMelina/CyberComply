<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::connection('db1')->create('sites', function (Blueprint $table) {
            $table->increments('id');
            $table->string('client_id', 14);
            $table->string('name', 255);
            $table->unsignedInteger('address_id');
            $table->boolean('is_active')->default(true);
            $table->timestamp('created_at')->useCurrent();
            $table->foreign('client_id')->references('id')->on('clients');
            $table->foreign('address_id')->references('id')->on('client_addresses');
        });
    }
    public function down() {
        Schema::connection('db1')->dropIfExists('sites');
    }
};
