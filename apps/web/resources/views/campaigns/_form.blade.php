@php($c = $campaign ?? null)

<div>
    <label class="block text-sm font-medium text-ink-muted mb-1.5">ชื่อแคมเปญ</label>
    <input type="text" name="name" value="{{ old('name', $c?->name) }}" class="input" required>
</div>

<div>
    <label class="block text-sm font-medium text-ink-muted mb-1.5">ประเภทธุรกิจ / Keyword</label>
    <input type="text" name="business_keyword" value="{{ old('business_keyword', $c?->business_keyword) }}" class="input" required>
</div>

<div class="grid grid-cols-2 gap-4">
    <div>
        <label class="block text-sm font-medium text-ink-muted mb-1.5">จังหวัด</label>
        <input type="text" name="province" value="{{ old('province', $c?->province) }}" class="input">
    </div>
    <div>
        <label class="block text-sm font-medium text-ink-muted mb-1.5">อำเภอ/เขต</label>
        <input type="text" name="district" value="{{ old('district', $c?->district) }}" class="input">
    </div>
</div>

<div class="grid grid-cols-2 gap-4">
    <div>
        <label class="block text-sm font-medium text-ink-muted mb-1.5">รัศมี (กม.)</label>
        <input type="number" name="radius_km" value="{{ old('radius_km', $c?->radius_km ?? 10) }}" class="input">
    </div>
    <div>
        <label class="block text-sm font-medium text-ink-muted mb-1.5">จำนวน Lead สูงสุด</label>
        <input type="number" name="maximum_leads" value="{{ old('maximum_leads', $c?->maximum_leads ?? 100) }}" class="input">
    </div>
</div>

<div class="flex items-center gap-6 pt-1">
    <label class="flex items-center gap-2 text-sm text-ink-muted">
        <input type="checkbox" name="include_businesses_without_website" value="1" class="rounded border-line text-brand focus:ring-brand/30"
               @checked(old('include_businesses_without_website', $c?->include_businesses_without_website ?? true))>
        รวมธุรกิจที่ไม่มีเว็บไซต์
    </label>
    <label class="flex items-center gap-2 text-sm text-ink-muted">
        <input type="checkbox" name="include_businesses_with_website" value="1" class="rounded border-line text-brand focus:ring-brand/30"
               @checked(old('include_businesses_with_website', $c?->include_businesses_with_website ?? true))>
        รวมธุรกิจที่มีเว็บไซต์
    </label>
</div>
