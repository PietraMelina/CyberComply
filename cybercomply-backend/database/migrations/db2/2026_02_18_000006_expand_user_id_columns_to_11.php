<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::connection('db2')->statement("ALTER TABLE modules MODIFY created_by VARCHAR(11) NOT NULL");
        DB::connection('db2')->statement("ALTER TABLE responses MODIFY answered_by VARCHAR(11) NOT NULL");
        DB::connection('db2')->statement("ALTER TABLE evidences MODIFY uploaded_by VARCHAR(11) NOT NULL");
        DB::connection('db2')->statement("ALTER TABLE audit_logs MODIFY user_id VARCHAR(11) NOT NULL");
    }

    public function down(): void
    {
        DB::connection('db2')->statement("ALTER TABLE audit_logs MODIFY user_id VARCHAR(10) NOT NULL");
        DB::connection('db2')->statement("ALTER TABLE evidences MODIFY uploaded_by VARCHAR(10) NOT NULL");
        DB::connection('db2')->statement("ALTER TABLE responses MODIFY answered_by VARCHAR(10) NOT NULL");
        DB::connection('db2')->statement("ALTER TABLE modules MODIFY created_by VARCHAR(10) NOT NULL");
    }
};
