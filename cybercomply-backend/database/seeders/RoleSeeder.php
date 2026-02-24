<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoleSeeder extends Seeder
{
    public function run()
    {
        DB::connection('db1')->table('roles')->upsert([
            [
                'name' => 'MASTER',
                'type' => 'INTERNAL',
                'permissions' => json_encode(['*']),
                'is_system_role' => true
            ],
            [
                'name' => 'GESTOR',
                'type' => 'INTERNAL',
                'permissions' => json_encode(['clients.*', 'users.read', 'audit.read', 'reports.*']),
                'is_system_role' => true
            ],
            [
                'name' => 'AUDITOR',
                'type' => 'INTERNAL',
                'permissions' => json_encode(['clients.read', 'modules.read', 'responses.read', 'evidences.read', 'audit.read']),
                'is_system_role' => true
            ],
            [
                'name' => 'ADMIN_CLIENTE',
                'type' => 'CLIENT',
                'permissions' => json_encode(['tenant.*', 'users.manage', 'modules.*', 'responses.*', 'evidences.*']),
                'is_system_role' => true
            ],
            [
                'name' => 'TECNICO',
                'type' => 'CLIENT',
                'permissions' => json_encode(['modules.read', 'responses.write', 'evidences.write']),
                'is_system_role' => true
            ],
            [
                'name' => 'LEITURA',
                'type' => 'CLIENT',
                'permissions' => json_encode(['modules.read', 'responses.read', 'evidences.read']),
                'is_system_role' => true
            ],
        ], ['name'], ['type', 'permissions', 'is_system_role']);
    }
}
