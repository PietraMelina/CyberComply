<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $seeders = [
            RoleSeeder::class,
        ];

        if (filter_var(env('SEED_MASTER_USER', true), FILTER_VALIDATE_BOOL)) {
            $seeders[] = MasterUserSeeder::class;
        }

        $this->call($seeders);
    }
}
