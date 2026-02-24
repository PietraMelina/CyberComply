<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::connection('db1')->statement('SET FOREIGN_KEY_CHECKS=0');
        DB::connection('db1')->statement("ALTER TABLE clients MODIFY id VARCHAR(14) NOT NULL");
        DB::connection('db1')->statement("ALTER TABLE client_addresses MODIFY client_id VARCHAR(14) NOT NULL");
        DB::connection('db1')->statement("ALTER TABLE sites MODIFY client_id VARCHAR(14) NOT NULL");
        DB::connection('db1')->statement("ALTER TABLE users MODIFY client_id VARCHAR(14) NULL");
        DB::connection('db1')->statement("ALTER TABLE audit_logs MODIFY client_id VARCHAR(14) NULL");
        DB::connection('db1')->statement('SET FOREIGN_KEY_CHECKS=1');
    }

    public function down(): void
    {
        DB::connection('db1')->statement('SET FOREIGN_KEY_CHECKS=0');
        DB::connection('db1')->statement("ALTER TABLE audit_logs MODIFY client_id VARCHAR(12) NULL");
        DB::connection('db1')->statement("ALTER TABLE users MODIFY client_id VARCHAR(12) NULL");
        DB::connection('db1')->statement("ALTER TABLE sites MODIFY client_id VARCHAR(12) NOT NULL");
        DB::connection('db1')->statement("ALTER TABLE client_addresses MODIFY client_id VARCHAR(12) NOT NULL");
        DB::connection('db1')->statement("ALTER TABLE clients MODIFY id VARCHAR(12) NOT NULL");
        DB::connection('db1')->statement('SET FOREIGN_KEY_CHECKS=1');
    }
};
