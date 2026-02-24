<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::connection('db1')->create('client_addresses', function (Blueprint $table) {
            $table->increments('id');
            $table->string('client_id', 14);
            $table->enum('type', ['HEADQUARTERS', 'BILLING', 'OPERATIONAL']);
            $table->string('address_line1', 255);
            $table->string('address_line2', 255)->nullable();
            $table->string('city', 100);
            $table->string('postal_code', 20);
            $table->string('country', 2)->default('PT');
            $table->boolean('is_primary')->default(false);
            $table->timestamp('created_at')->useCurrent();
            $table->foreign('client_id')->references('id')->on('clients');
        });
    }
    public function down() {
        Schema::connection('db1')->dropIfExists('client_addresses');
    }
};
