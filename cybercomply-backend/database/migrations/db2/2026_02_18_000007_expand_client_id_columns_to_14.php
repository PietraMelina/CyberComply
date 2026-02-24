<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::connection('db2')->statement("ALTER TABLE modules MODIFY client_id VARCHAR(14) NOT NULL");
        DB::connection('db2')->statement("ALTER TABLE questions MODIFY client_id VARCHAR(14) NOT NULL");
        DB::connection('db2')->statement("ALTER TABLE responses MODIFY client_id VARCHAR(14) NOT NULL");
        DB::connection('db2')->statement("ALTER TABLE evidences MODIFY client_id VARCHAR(14) NOT NULL");
        DB::connection('db2')->statement("ALTER TABLE audit_logs MODIFY client_id VARCHAR(14) NULL");
    }

    public function down(): void
    {
        DB::connection('db2')->statement("ALTER TABLE audit_logs MODIFY client_id VARCHAR(12) NULL");
        DB::connection('db2')->statement("ALTER TABLE evidences MODIFY client_id VARCHAR(12) NOT NULL");
        DB::connection('db2')->statement("ALTER TABLE responses MODIFY client_id VARCHAR(12) NOT NULL");
        DB::connection('db2')->statement("ALTER TABLE questions MODIFY client_id VARCHAR(12) NOT NULL");
        DB::connection('db2')->statement("ALTER TABLE modules MODIFY client_id VARCHAR(12) NOT NULL");
    }
};
