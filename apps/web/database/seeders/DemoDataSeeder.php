<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\AccountType;
use App\Enums\CampaignStatus;
use App\Enums\LeadStatus;
use App\Models\Campaign;
use App\Models\Lead;
use App\Models\User;
use App\Models\WebsiteAudit;
use Illuminate\Database\Seeder;

/**
 * ข้อมูลตัวอย่างสำหรับ Demo/Dev เท่านั้น — ไม่ใช่ข้อมูลบริษัทจริง
 * ออกแบบให้หน้าตา/ปริมาณข้อมูลใกล้เคียงกับ ui/assets/js/mock-data.js ต้นฉบับ
 * (10 Campaigns, Leads รวมทุก Campaign ~24-30 รายการ) เพื่อให้ทุกหน้าที่แปลงจาก
 * ui/ folder มีข้อมูลให้ดูทันทีหลัง `php artisan migrate:fresh --seed`
 */
class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::query()->firstOrCreate(
            ['email' => 'admin@example.com'],
            ['name' => 'System Admin', 'account_type' => AccountType::Admin->value, 'password' => bcrypt('password')]
        );

        $sales = User::query()->firstOrCreate(
            ['email' => 'sales@example.com'],
            ['name' => 'สมชาย ฝ่ายขาย', 'account_type' => AccountType::Sales->value, 'password' => bcrypt('password')]
        );

        User::query()->firstOrCreate(
            ['email' => 'viewer@example.com'],
            ['name' => 'วิเชียร ผู้เข้าชม', 'account_type' => AccountType::Viewer->value, 'password' => bcrypt('password')]
        );

        if (Campaign::query()->exists()) {
            return; // idempotent: ไม่ seed ซ้ำถ้ามีข้อมูลอยู่แล้ว
        }

        $blueprints = [
            ['name' => 'ค้นหาร้านอาหารในกรุงเทพฯ', 'keyword' => 'ร้านอาหาร', 'category' => 'ร้านอาหาร', 'province' => 'กรุงเทพมหานคร', 'status' => CampaignStatus::Completed],
            ['name' => 'โรงงานผลิตอาหาร นครปฐม', 'keyword' => 'โรงงานผลิตอาหาร', 'category' => 'ธุรกิจ E-commerce', 'province' => 'นครปฐม', 'status' => CampaignStatus::Completed],
            ['name' => 'ธุรกิจโลจิสติกส์ อุบลราชธานี', 'keyword' => 'ขนส่งและโลจิสติกส์', 'category' => 'โลจิสติกส์และขนส่ง', 'province' => 'อุบลราชธานี', 'status' => CampaignStatus::PartiallyCompleted],
            ['name' => 'รีสอร์ทและโรงแรม ภูเก็ต', 'keyword' => 'รีสอร์ท', 'category' => 'เครื่องสำอาง', 'province' => 'ภูเก็ต', 'status' => CampaignStatus::Completed],
            ['name' => 'อสังหาริมทรัพย์ กาญจนบุรี', 'keyword' => 'อสังหาริมทรัพย์', 'category' => 'อสังหาริมทรัพย์', 'province' => 'กาญจนบุรี', 'status' => CampaignStatus::Processing],
            ['name' => 'ธุรกิจแมนูแฟคเจอริ่ง ชลบุรี', 'keyword' => 'โรงงานผลิต', 'category' => 'อุตสาหกรรม', 'province' => 'ชลบุรี', 'status' => CampaignStatus::Queued],
            ['name' => 'ร้านค้าปลีก เชียงใหม่', 'keyword' => 'ร้านค้าปลีก', 'category' => 'ค้าปลีก', 'province' => 'เชียงใหม่', 'status' => CampaignStatus::Draft],
            ['name' => 'คลินิกความงาม กรุงเทพฯ', 'keyword' => 'คลินิกความงาม', 'category' => 'สุขภาพและความงาม', 'province' => 'กรุงเทพมหานคร', 'status' => CampaignStatus::Failed],
            ['name' => 'ธุรกิจก่อสร้าง ขอนแก่น', 'keyword' => 'รับเหมาก่อสร้าง', 'category' => 'ก่อสร้าง', 'province' => 'ขอนแก่น', 'status' => CampaignStatus::Cancelled],
            ['name' => 'ธุรกิจการเกษตร นครราชสีมา', 'keyword' => 'ฟาร์มเกษตร', 'category' => 'เกษตรกรรม', 'province' => 'นครราชสีมา', 'status' => CampaignStatus::Draft],
        ];

        $companyPrefixes = ['บริษัท', 'ห้างหุ้นส่วนจำกัด', 'บริษัท', 'บริษัท'];
        $companySuffixes = ['จำกัด', 'กรุ๊ป จำกัด', 'จำกัด (มหาชน)'];
        $companyCores = [
            'ไทย รุ่งเรือง', 'เอเชีย แปซิฟิก', 'สยาม พัฒนา', 'โกลบอล เทรดดิ้ง', 'แลนด์มาร์ค',
            'ยูไนเต็ด', 'พรีเมียร์', 'อีสเทิร์น แมนูแฟคเจอริ่ง', 'ภูเก็ต รีสอร์ท กรุ๊ป', 'กาญจนบุรี วู้ด',
            'อุดรธานี เอ็กซ์เพรส', 'มั่นคง แมททีเรียล', 'ศรีสยาม ฟู้ด', 'ร่มเกล้า โลจิสติกส์', 'บางกอก ครีเอทีฟ',
        ];

        $statusPool = [
            LeadStatus::New, LeadStatus::Qualified, LeadStatus::Contacted, LeadStatus::Interested,
            LeadStatus::NotInterested, LeadStatus::Invalid, LeadStatus::Duplicate, LeadStatus::Converted,
        ];

        foreach ($blueprints as $i => $bp) {
            $campaign = Campaign::factory()->for($sales)->create([
                'name' => $bp['name'],
                'business_keyword' => $bp['keyword'],
                'business_category' => $bp['category'],
                'province' => $bp['province'],
                'district' => null,
                'radius_km' => [10, 15, 25, 50][$i % 4],
                'maximum_leads' => [50, 100, 150][$i % 3],
                'include_businesses_with_website' => true,
                'include_businesses_without_website' => true,
                'minimum_rating' => 3.0,
                'minimum_review_count' => 5,
                'search_language' => 'th',
                'country' => 'TH',
                'status' => $bp['status']->value,
            ]);

            // จำนวน Lead ต่อ Campaign แปรผัน ให้รวมทั้งระบบ ~24-30 รายการ (ใกล้เคียง ui/ mock 24 รายการ)
            $leadCount = in_array($bp['status'], [CampaignStatus::Draft, CampaignStatus::Queued, CampaignStatus::Cancelled], true) ? 0 : random_int(2, 4);

            for ($j = 0; $j < $leadCount; $j++) {
                $hasWebsite = random_int(1, 100) <= 75; // ~75% มีเว็บไซต์ ใกล้เคียง 18/24 ของ mock เดิม
                $companyName = $companyPrefixes[array_rand($companyPrefixes)].' '
                    .$companyCores[array_rand($companyCores)].' '
                    .$companySuffixes[array_rand($companySuffixes)];
                $domain = 'example-'.\Illuminate\Support\Str::slug(substr($companyCores[array_rand($companyCores)], 0, 10)).'.co.th';

                $lead = Lead::factory()->for($campaign)->create([
                    'company_name' => $companyName,
                    'normalized_company_name' => \Illuminate\Support\Str::lower($companyName),
                    'phone' => sprintf('0%d-%03d-%04d', random_int(2, 9), random_int(0, 999), random_int(0, 9999)),
                    'website_url' => $hasWebsite ? 'https://'.$domain : null,
                    'normalized_domain' => $hasWebsite ? $domain : null,
                    'address' => null,
                    'province' => $bp['province'],
                    'district' => null,
                    'business_type' => $bp['category'],
                    'rating' => $hasWebsite ? round(random_int(30, 50) / 10, 1) : null,
                    'review_count' => $hasWebsite ? random_int(5, 250) : 0,
                    'source' => 'google_maps',
                    'status' => $statusPool[array_rand($statusPool)]->value,
                    'discovered_at' => now()->subDays(random_int(0, 20)),
                ]);

                if ($hasWebsite && random_int(1, 100) <= 60) {
                    WebsiteAudit::factory()->for($lead)->create([
                        'website_url' => $lead->website_url,
                        'audit_score' => random_int(20, 95),
                        'https_enabled' => random_int(0, 1) === 1,
                        'audited_at' => now()->subDays(random_int(0, 15)),
                    ]);
                }
            }
        }

        // มอบหมาย Admin ให้เห็นภาพรวมทั้งหมดด้วย (ไม่ต้องสร้างข้อมูลเพิ่ม เพราะ Policy อนุญาต Admin เห็นทุก Campaign อยู่แล้ว)
        unset($admin);
    }
}
