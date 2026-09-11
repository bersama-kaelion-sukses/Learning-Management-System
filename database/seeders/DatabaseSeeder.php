<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Kalau UserSeeder ada di namespace yang sama, cukup panggil class-nya
        $this->call([
            UserSeeder::class,
        ]);
    }
}