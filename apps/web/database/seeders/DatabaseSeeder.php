<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * เรียก DemoDataSeeder ซึ่งสร้างชุดข้อมูลตัวอย่าง (3 users, 10 campaigns, ~24-30 leads)
     * ที่ออกแบบให้หน้าตาใกล้เคียงกับ ui/ folder mock data มากที่สุดเท่าที่ schema จริงรองรับ
     * รัน: php artisan migrate:fresh --seed
     */
    public function run(): void
    {
        $this->call(DemoDataSeeder::class);
    }
}
