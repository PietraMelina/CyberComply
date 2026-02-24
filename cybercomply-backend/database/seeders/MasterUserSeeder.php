<?php

namespace Database\Seeders;

use App\Models\DB1\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class MasterUserSeeder extends Seeder
{
    public function run(): void
    {
        $email = (string) env('MASTER_SEED_EMAIL', 'master@cybercomply.local');
        $password = (string) env('MASTER_SEED_PASSWORD', 'ChangeMe!123');
        $userId = (string) env('MASTER_SEED_ID', 'MSTR-ADM001');

        $masterRole = Role::query()->where('name', 'MASTER')->firstOrFail();

        User::query()->updateOrCreate(
            ['email' => $email],
            [
                'id' => $userId,
                'client_id' => null,
                'display_name' => 'Master Admin',
                'password_hash' => Hash::make($password),
                'role_id' => $masterRole->id,
                'is_active' => true,
                'accepted_terms_at' => now(),
                'accepted_terms_version' => 'v1',
                'email_verified_at' => now(),
            ]
        );
    }
}
