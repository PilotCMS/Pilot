<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Pilot\Core\Database\Seeders\DatabaseSeeder as PilotDatabaseSeeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(PilotDatabaseSeeder::class);
    }
}
