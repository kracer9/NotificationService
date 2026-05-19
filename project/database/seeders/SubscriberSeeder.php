<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SubscriberSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $data = [];

        for ($i = 1; $i <= 100; $i++) {
            $data[] = [
                'email' => sprintf('user%s@fakemail.ru', $i),
                'phone' => sprintf('+7 999 000 00 %02s', $i),
                'created_at' => date_create(),
                'updated_at' => date_create(),
            ];
        }

        DB::table('subscribers')->insert($data);
    }
}
