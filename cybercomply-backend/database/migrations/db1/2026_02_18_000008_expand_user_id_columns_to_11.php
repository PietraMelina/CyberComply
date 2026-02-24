<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::connection('db1')->statement("ALTER TABLE users MODIFY id VARCHAR(11) NOT NULL");
        DB::connection('db1')->statement("ALTER TABLE clients MODIFY deactivated_by VARCHAR(11) NULL");
        DB::connection('db1')->statement("ALTER TABLE tokens MODIFY user_id VARCHAR(11) NOT NULL");
        DB::connection('db1')->statement("ALTER TABLE tokens MODIFY cancelled_by VARCHAR(11) NULL");
        DB::connection('db1')->statement("ALTER TABLE audit_logs MODIFY user_id VARCHAR(11) NOT NULL");
    }

    public function down(): void
    {
        DB::connection('db1')->statement("ALTER TABLE audit_logs MODIFY user_id VARCHAR(10) NOT NULL");
        DB::connection('db1')->statement("ALTER TABLE tokens MODIFY cancelled_by VARCHAR(10) NULL");
        DB::connection('db1')->statement("ALTER TABLE tokens MODIFY user_id VARCHAR(10) NOT NULL");
        DB::connection('db1')->statement("ALTER TABLE clients MODIFY deactivated_by VARCHAR(10) NULL");
        DB::connection('db1')->statement("ALTER TABLE users MODIFY id VARCHAR(10) NOT NULL");
    }
};
