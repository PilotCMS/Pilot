<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            BlockTypeSeeder::class,
            SpaceSeeder::class,
        ]);

        // Create additional test users with different roles
        $editor = User::firstOrCreate(
            ['email' => 'editor@pilot.com'],
            [
                'name' => 'Editor User',
                'password' => bcrypt('password'),
            ]
        );
        if (! $editor->hasRole('Editor')) {
            $editor->assignRole('Editor');
        }

        $author = User::firstOrCreate(
            ['email' => 'author@pilot.com'],
            [
                'name' => 'Author User',
                'password' => bcrypt('password'),
            ]
        );
        if (! $author->hasRole('Author')) {
            $author->assignRole('Author');
        }

        $viewer = User::firstOrCreate(
            ['email' => 'viewer@pilot.com'],
            [
                'name' => 'Viewer User',
                'password' => bcrypt('password'),
            ]
        );
        if (! $viewer->hasRole('Viewer')) {
            $viewer->assignRole('Viewer');
        }
    }
}
