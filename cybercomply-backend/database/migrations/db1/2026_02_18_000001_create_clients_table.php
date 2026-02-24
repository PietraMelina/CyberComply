<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::connection('db1')->create('clients', function (Blueprint $table) {
            $table->string('id', 14)->primary(); // PRIV-2026-X8KF
            $table->enum('type', ['PRIV', 'PUBL']);
            $table->string('name', 255);
            $table->string('vat_number', 50)->nullable();
            $table->unsignedInteger('billing_address_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->timestamp('deactivated_at')->nullable();
            $table->string('deactivated_by', 11)->nullable();
            $table->index('is_active', 'idx_clients_active');
        });
    }
    public function down() {
        Schema::connection('db1')->dropIfExists('clients');
    }
};
