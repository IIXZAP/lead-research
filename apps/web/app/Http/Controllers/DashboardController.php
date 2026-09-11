<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Route;
use App\Enums\AccountType;
use Illuminate\Support\Facades\Auth;

/**
 * DashboardController
 * -----------------------------------------------------------------
 * แปลงจาก assets/js/dashboard.js + mock-data.js ของ Demo มาเป็นฝั่ง Server
 *
 * ขั้นนี้ยัง "ไม่เชื่อม Database" ตามข้อกำหนด: ข้อมูลเป็น PHP array (mock) ในคอนโทรลเลอร์
 * และการคำนวณ (summary, chart bucket, sort, scope) ทำในชั้นนี้ทั้งหมดเพื่อให้ Blade
 * ไม่มี business logic (มีแค่ @foreach / การแสดงผล)
 *
 * TODO(ขั้นถัดไป): ย้าย mock*() ไปเป็น Repository/Service + Eloquent เมื่อมี DB จริง
 * และแทน scope/permission ด้านล่างด้วย Policy/Gate (ห้ามเชื่อ account_type จาก client)
 */
final class DashboardController extends Controller
{
    /** วันที่อ้างอิงของระบบ (Demo fix ไว้ที่ 2026-07-17 เพื่อให้ mock data สาธิตได้คงที่) */
    private const NOW = '2026-07-17 12:00:00';

    /** ป้ายกำกับ + สีของสถานะ Campaign (ตรงกับ CAMPAIGN_STATUS_META ใน mock-data.js) */
    private const STATUS_META = [
        'draft'               => ['label' => 'Draft',               'color' => 'bg-slate-100 text-slate-600 ring-slate-500/20'],
        'queued'              => ['label' => 'Queued',              'color' => 'bg-blue-50 text-blue-700 ring-blue-600/20'],
        'processing'          => ['label' => 'Processing',          'color' => 'bg-amber-50 text-amber-700 ring-amber-600/20'],
        'partially_completed' => ['label' => 'Partially Completed', 'color' => 'bg-orange-50 text-orange-700 ring-orange-600/20'],
        'completed'           => ['label' => 'Completed',           'color' => 'bg-emerald-50 text-emerald-700 ring-emerald-600/20'],
        'failed'              => ['label' => 'Failed',              'color' => 'bg-rose-50 text-rose-700 ring-rose-600/20'],
        'cancelled'           => ['label' => 'Cancelled',           'color' => 'bg-slate-200 text-slate-500 ring-slate-500/20'],
    ];

    /** สีของแต่ละสถานะบน Donut chart (เรียงลำดับตาม STATUS_META — ตรงกับ dashboard.js เดิม) */
    private const STATUS_CHART_COLORS = ['#94a3b8', '#3b82f6', '#f59e0b', '#fb923c', '#10b981', '#f43f5e', '#cbd5e1'];

    /** class สีของไอคอนใน Summary card (ตรงกับ colorMap ใน dashboard.js) */
    private const CARD_COLOR = [
        'indigo'  => 'bg-indigo-50 text-indigo-600',
        'amber'   => 'bg-amber-50 text-amber-600',
        'emerald' => 'bg-emerald-50 text-emerald-600',
        'sky'     => 'bg-sky-50 text-sky-600',
    ];

    public function index(): View
    {
        $accountType = auth()->user()->account_type ?? AccountType::Admin;
        $userId = Auth::id();
        // กรองข้อมูลตามสิทธิ์การมองเห็น (เทียบเท่า filterCampaignsByScope/filterBusinessesByScope)
        $campaigns  = $this->scopeCampaigns($this->mockCampaigns(), $accountType, $userId);
        $businesses = $this->scopeBusinesses($this->mockBusinesses(), $accountType);

        return view('dashboard.index', [
            'accountType'      => $accountType,
            'scopeDesc'        => $this->scopeDescription($accountType),
            'canCreateCampaign' => in_array($accountType, [AccountType::Admin, AccountType::Sales], true), // permissions.js: campaign.create
            'summaryCards'     => $this->buildSummaryCards($campaigns, $businesses),
            'recentCampaigns'  => $this->buildRecentCampaigns($campaigns),
            'topLeads'         => $this->buildTopLeads($businesses),
            'dailyChart'       => $this->buildDailyLeadsChart($businesses),
            'statusChart'      => $this->buildCampaignStatusChart($campaigns),
            'links'            => [
                'create'    => $this->routeOr('campaigns.create', [], '/campaigns/create'),
                'campaigns' => $this->routeOr('campaigns.index', [], '/campaigns'),
                'leads'     => $this->routeOr('leads.index', [], '/leads'),
            ],
        ]);
    }

    // -----------------------------------------------------------------
    // Presentation builders (แปลง raw mock → โครงข้อมูลพร้อมแสดงผล)
    // -----------------------------------------------------------------

    /** Summary cards 4 ใบ (ตรงกับ renderSummaryCards ใน dashboard.js) */
    private function buildSummaryCards(array $campaigns, array $businesses): array
    {
        $processing = count(array_filter($campaigns, fn($c) => in_array($c['status'], ['processing', 'queued'], true)));
        $completed  = count(array_filter($campaigns, fn($c) => in_array($c['status'], ['completed', 'partially_completed'], true)));

        return [
            ['label' => 'Campaign ทั้งหมด',      'value' => count($campaigns),  'icon' => 'target',        'colorClass' => self::CARD_COLOR['indigo'],  'trend' => '+12.5%', 'up' => true],
            ['label' => 'กำลังประมวลผล',        'value' => $processing,        'icon' => 'loader-circle', 'colorClass' => self::CARD_COLOR['amber'],   'trend' => '+2',     'up' => true],
            ['label' => 'เสร็จแล้ว',             'value' => $completed,         'icon' => 'check-circle-2', 'colorClass' => self::CARD_COLOR['emerald'], 'trend' => '+8.1%',  'up' => true],
            ['label' => 'Lead ที่ค้นพบทั้งหมด', 'value' => count($businesses), 'icon' => 'users',         'colorClass' => self::CARD_COLOR['sky'],     'trend' => '+18.4%', 'up' => true],
        ];
    }

    /** Campaign ล่าสุด 6 รายการ (เรียงตาม created_at ใหม่→เก่า) */
    private function buildRecentCampaigns(array $campaigns): array
    {
        usort($campaigns, fn($a, $b) => strtotime($b['created_at']) <=> strtotime($a['created_at']));

        return array_map(function (array $c) {
            $meta = self::STATUS_META[$c['status']] ?? ['label' => $c['status'], 'color' => 'bg-slate-100 text-slate-600'];

            return [
                'title'        => $c['title'],
                'province'     => $c['province'],
                'leads_found'  => $c['leads_found'],
                'maximum_leads' => $c['maximum_leads'],
                'time_ago'     => $this->timeAgo($c['created_at']),
                'status_label' => $meta['label'],
                'status_color' => $meta['color'],
                'url'          => $this->routeOr('campaigns.show', ['campaign' => $c['id']], '/campaigns/' . $c['id']),
            ];
        }, array_slice($campaigns, 0, 6));
    }

    /** Lead คุณภาพสูง 6 รายการ (เรียงตามคะแนนมาก→น้อย) */
    private function buildTopLeads(array $businesses): array
    {
        usort($businesses, fn($a, $b) => $b['score'] <=> $a['score']);

        return array_map(fn(array $b) => [
            'company_name_th' => $b['company_name_th'],
            'business_type'   => $b['business_type'],
            'score'           => $b['score'],
            'quality'         => $b['quality'],
            'initials'        => mb_strtoupper(mb_substr(trim($b['company_name_th']), 0, 2)),
            'url'             => $this->routeOr('business.show', ['business' => $b['id']], '/businesses/' . $b['id']),
        ], array_slice($businesses, 0, 6));
    }

    /** Area chart: จำนวน Lead ที่ค้นพบรายวัน 14 วันล่าสุด (ตรงกับ renderDailyLeadsChart) */
    private function buildDailyLeadsChart(array $businesses): array
    {
        $thaiMonthShort = [1 => 'ม.ค.', 2 => 'ก.พ.', 3 => 'มี.ค.', 4 => 'เม.ย.', 5 => 'พ.ค.', 6 => 'มิ.ย.', 7 => 'ก.ค.', 8 => 'ส.ค.', 9 => 'ก.ย.', 10 => 'ต.ค.', 11 => 'พ.ย.', 12 => 'ธ.ค.'];
        $base = strtotime(substr(self::NOW, 0, 10));

        $categories = [];
        $counts = [];
        for ($i = 13; $i >= 0; $i--) {
            $ts = $base - $i * 86400;
            $key = date('Y-m-d', $ts);
            $categories[] = (int) date('j', $ts) . ' ' . $thaiMonthShort[(int) date('n', $ts)];
            $counts[] = count(array_filter($businesses, fn($b) => substr($b['created_at'], 0, 10) === $key));
        }

        return ['categories' => $categories, 'counts' => $counts];
    }

    /** Donut chart: จำนวน Campaign แยกตามสถานะ (ตรงกับ renderCampaignStatusChart) */
    private function buildCampaignStatusChart(array $campaigns): array
    {
        $statuses = array_keys(self::STATUS_META);

        return [
            'labels' => array_map(fn($s) => self::STATUS_META[$s]['label'], $statuses),
            'counts' => array_map(fn($s) => count(array_filter($campaigns, fn($c) => $c['status'] === $s)), $statuses),
            'colors' => self::STATUS_CHART_COLORS,
        ];
    }

    // -----------------------------------------------------------------
    // Scope & helpers (เทียบเท่า permissions.js / utils.js)
    // -----------------------------------------------------------------
    private function scopeDescription(AccountType $accountType): string
    {
        return match ($accountType) {
            AccountType::Admin  => 'ภาพรวมแคมเปญและ Lead ทั้งองค์กร',
            AccountType::Sales  => 'ภาพรวม Campaign และ Lead ที่ได้รับมอบหมาย',
            AccountType::Viewer => 'ภาพรวม Campaign และ Lead ที่ได้รับสิทธิ์เข้าถึง',
        };
    }

    /**
     * admin/manager เห็นทั้งหมด; sale เห็นเฉพาะที่ได้รับมอบหมาย
     * NOTE: mock ยังไม่มีข้อมูล assignment จริง จึงคืนทั้งหมดไปก่อน
     * การกรองระดับ sale ที่ถูกต้องต้องทำที่ Policy + DB ในขั้นถัดไป
     */
    private function scopeCampaigns(array $campaigns, AccountType $accountType, int $userId): array
    {
        return match ($accountType) {
            AccountType::Admin => $campaigns,
            AccountType::Sales => array_values(array_filter(
                $campaigns,
                fn($campaign) => $campaign['user_id'] === $userId
            )),
            AccountType::Viewer => [], // หรือ logic กรองตาม granted campaigns ถ้ามี
        };
    }

    private function scopeBusinesses(array $businesses, AccountType $accountType): array
    {
        return $businesses;
    }

    /** timeAgo อ้างอิงเวลาระบบคงที่ (ตรงกับ utils.js timeAgo) */
    private function timeAgo(string $dateStr): string
    {
        $now = strtotime(self::NOW);
        $t = strtotime($dateStr);
        $diffMin = (int) round(($now - $t) / 60);
        if ($diffMin < 1) {
            return 'เมื่อสักครู่';
        }
        if ($diffMin < 60) {
            return "{$diffMin} นาทีที่แล้ว";
        }
        $diffHr = (int) round($diffMin / 60);
        if ($diffHr < 24) {
            return "{$diffHr} ชั่วโมงที่แล้ว";
        }
        $diffDay = (int) round($diffHr / 24);

        return "{$diffDay} วันที่แล้ว";
    }

    /** คืน URL จาก named route ถ้ามี ไม่งั้น fallback (กันหน้าเว็บล่มเมื่อ route ยังไม่ถูกสร้าง) */
    private function routeOr(string $name, array $params, string $fallback): string
    {
        if (! Route::has($name)) {
            return url($fallback);
        }
        try {
            return route($name, $params);
        } catch (\Throwable $e) {
            return url($fallback);
        }
    }

    // -----------------------------------------------------------------
    // Mock data (แทน generateMockCampaigns / generateMockBusinesses)
    // ชื่อ/ประเภทอ้างอิงจาก CAMPAIGN_TEMPLATES และ COMPANY_NAME_POOL ใน mock-data.js
    // -----------------------------------------------------------------

    private function mockCampaigns(): array
    {
        return [
            ['id' => 'CMP-2026-001', 'title' => 'Lead โรงงานผลิตอาหาร กรุงเทพฯ และปริมณฑล', 'status' => 'completed',           'province' => 'กรุงเทพมหานคร', 'leads_found' => 92,  'maximum_leads' => 100, 'created_at' => '2026-07-16 09:30:00'],
            ['id' => 'CMP-2026-002', 'title' => 'ค้นหาร้านอาหารในเชียงใหม่ที่ยังไม่มีเว็บไซต์', 'status' => 'processing',        'province' => 'เชียงใหม่',     'leads_found' => 34,  'maximum_leads' => 50,  'created_at' => '2026-07-15 14:10:00'],
            ['id' => 'CMP-2026-003', 'title' => 'Lead โรงแรมและที่พักภูเก็ต',                  'status' => 'completed',           'province' => 'ภูเก็ต',        'leads_found' => 210, 'maximum_leads' => 250, 'created_at' => '2026-07-14 11:05:00'],
            ['id' => 'CMP-2026-004', 'title' => 'บริษัทอสังหาริมทรัพย์ นนทบุรี-ปทุมธานี',       'status' => 'queued',             'province' => 'นนทบุรี',       'leads_found' => 0,   'maximum_leads' => 100, 'created_at' => '2026-07-17 08:00:00'],
            ['id' => 'CMP-2026-005', 'title' => 'ผู้รับเหมาก่อสร้างในชลบุรี',                    'status' => 'failed',             'province' => 'ชลบุรี',        'leads_found' => 12,  'maximum_leads' => 100, 'created_at' => '2026-07-13 16:45:00'],
            ['id' => 'CMP-2026-006', 'title' => 'โลจิสติกส์และคลังสินค้า สมุทรปราการ',           'status' => 'partially_completed', 'province' => 'สมุทรปราการ',   'leads_found' => 60,  'maximum_leads' => 100, 'created_at' => '2026-07-12 10:20:00'],
            ['id' => 'CMP-2026-007', 'title' => 'ร้านค้าปลีกในขอนแก่นที่ต้องการระบบ POS',       'status' => 'draft',              'province' => 'ขอนแก่น',       'leads_found' => 0,   'maximum_leads' => 50,  'created_at' => '2026-07-11 13:15:00'],
            ['id' => 'CMP-2026-008', 'title' => 'โรงงานอุตสาหกรรมในระยอง',                      'status' => 'processing',         'province' => 'ระยอง',         'leads_found' => 40,  'maximum_leads' => 100, 'created_at' => '2026-07-10 09:00:00'],
            ['id' => 'CMP-2026-009', 'title' => 'คลินิกความงามและเครื่องสำอาง กรุงเทพฯ',         'status' => 'cancelled',          'province' => 'กรุงเทพมหานคร', 'leads_found' => 8,   'maximum_leads' => 50,  'created_at' => '2026-07-09 15:30:00'],
            ['id' => 'CMP-2026-010', 'title' => 'ธุรกิจ E-commerce ที่ต้องการระบบ CRM',         'status' => 'completed',          'province' => 'ปทุมธานี',      'leads_found' => 180, 'maximum_leads' => 250, 'created_at' => '2026-07-08 12:00:00'],
        ];
    }

    private function mockBusinesses(): array
    {
        $rows = [
            ['id' => 'BUS-2026-001', 'company_name_th' => 'บริษัท ตัวอย่าง ฟู้ด จำกัด',            'business_type' => 'โรงงานผลิตอาหาร', 'score' => 88, 'created_at' => '2026-07-17 09:12:00'],
            ['id' => 'BUS-2026-002', 'company_name_th' => 'บริษัท สยาม โลจิสติกส์ จำกัด',          'business_type' => 'โลจิสติกส์และขนส่ง', 'score' => 74, 'created_at' => '2026-07-17 10:40:00'],
            ['id' => 'BUS-2026-003', 'company_name_th' => 'ห้างหุ้นส่วนจำกัด รุ่งเรืองก่อสร้าง',    'business_type' => 'ก่อสร้าง',         'score' => 52, 'created_at' => '2026-07-16 08:05:00'],
            ['id' => 'BUS-2026-004', 'company_name_th' => 'บริษัท กรีนฟาร์ม ออร์แกนิค จำกัด',       'business_type' => 'โรงงานผลิตอาหาร', 'score' => 81, 'created_at' => '2026-07-16 13:22:00'],
            ['id' => 'BUS-2026-005', 'company_name_th' => 'บริษัท เชียงใหม่ คราฟท์ จำกัด',          'business_type' => 'ค้าปลีก',         'score' => 66, 'created_at' => '2026-07-15 09:50:00'],
            ['id' => 'BUS-2026-006', 'company_name_th' => 'บริษัท ภูเก็ต รีสอร์ท กรุ๊ป จำกัด',      'business_type' => 'โรงแรมและที่พัก',  'score' => 92, 'created_at' => '2026-07-15 15:30:00'],
            ['id' => 'BUS-2026-007', 'company_name_th' => 'ห้างหุ้นส่วนจำกัด วัสดุก่อสร้างมั่นคง',  'business_type' => 'ก่อสร้าง',         'score' => 43, 'created_at' => '2026-07-14 11:11:00'],
            ['id' => 'BUS-2026-008', 'company_name_th' => 'บริษัท ไทย เทคโนโลยี โซลูชั่น จำกัด',    'business_type' => 'เทคโนโลยี',        'score' => 79, 'created_at' => '2026-07-14 16:00:00'],
            ['id' => 'BUS-2026-009', 'company_name_th' => 'บริษัท เอเชีย คอสเมติกส์ จำกัด',         'business_type' => 'เครื่องสำอาง',     'score' => 71, 'created_at' => '2026-07-13 10:25:00'],
            ['id' => 'BUS-2026-010', 'company_name_th' => 'บริษัท ขอนแก่น ค้าปลีก จำกัด',           'business_type' => 'ค้าปลีก',         'score' => 58, 'created_at' => '2026-07-13 14:45:00'],
            ['id' => 'BUS-2026-011', 'company_name_th' => 'บริษัท อีสเทิร์น แมนูแฟคเจอริ่ง จำกัด',  'business_type' => 'โรงงานอุตสาหกรรม', 'score' => 85, 'created_at' => '2026-07-12 09:30:00'],
            ['id' => 'BUS-2026-012', 'company_name_th' => 'บริษัท ระยอง อินดัสเทรียล จำกัด',        'business_type' => 'โรงงานอุตสาหกรรม', 'score' => 63, 'created_at' => '2026-07-12 12:10:00'],
            ['id' => 'BUS-2026-013', 'company_name_th' => 'ห้างหุ้นส่วนจำกัด ครัวคุณยาย',           'business_type' => 'ร้านอาหาร',       'score' => 49, 'created_at' => '2026-07-11 08:40:00'],
            ['id' => 'BUS-2026-014', 'company_name_th' => 'บริษัท สมาร์ท อีคอมเมิร์ซ จำกัด',        'business_type' => 'ธุรกิจ E-commerce', 'score' => 90, 'created_at' => '2026-07-11 17:05:00'],
            ['id' => 'BUS-2026-015', 'company_name_th' => 'บริษัท นนทบุรี พร็อพเพอร์ตี้ จำกัด',     'business_type' => 'อสังหาริมทรัพย์',  'score' => 55, 'created_at' => '2026-07-10 11:20:00'],
            ['id' => 'BUS-2026-016', 'company_name_th' => 'บริษัท พัทยา ฮอสพิทาลิตี้ จำกัด',        'business_type' => 'โรงแรมและที่พัก',  'score' => 77, 'created_at' => '2026-07-09 10:00:00'],
            ['id' => 'BUS-2026-017', 'company_name_th' => 'บริษัท คลินิก สกิน แคร์ จำกัด',          'business_type' => 'การแพทย์และคลินิก', 'score' => 68, 'created_at' => '2026-07-08 13:35:00'],
            ['id' => 'BUS-2026-018', 'company_name_th' => 'บริษัท สงขลา ซีฟู้ด เอ็กซ์พอร์ต จำกัด',  'business_type' => 'นำเข้าและส่งออก',  'score' => 83, 'created_at' => '2026-07-07 09:15:00'],
            ['id' => 'BUS-2026-019', 'company_name_th' => 'บริษัท บางนา ดิจิทัล มาร์เก็ตติ้ง จำกัด', 'business_type' => 'บริการด้านการตลาด', 'score' => 60, 'created_at' => '2026-07-06 14:50:00'],
            ['id' => 'BUS-2026-020', 'company_name_th' => 'บริษัท อุดรธานี ขนส่งด่วน จำกัด',        'business_type' => 'โลจิสติกส์และขนส่ง', 'score' => 47, 'created_at' => '2026-07-05 10:30:00'],
        ];

        // แปลงคะแนน → คุณภาพ (ตรงกับ lead_analysis.quality ใน mock-data.js: >=75 High, >=55 Medium, ไม่งั้น Low)
        return array_map(function (array $b) {
            $b['quality'] = $b['score'] >= 75 ? 'High' : ($b['score'] >= 55 ? 'Medium' : 'Low');

            return $b;
        }, $rows);
    }
}
