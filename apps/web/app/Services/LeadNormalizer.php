<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Mirrors app/services/normalizer.py on the Python side exactly, so a
 * phone or domain normalizes to the same value regardless of which
 * service computed it. Requirement.md §7.
 */
class LeadNormalizer
{
    private const LEGAL_ENTITY_WORDS = ['บริษัท', 'หจก.', 'ห้างหุ้นส่วนจำกัด', 'จำกัด (มหาชน)', 'จำกัด'];

    private const STRIPPED_PUNCTUATION = ['.', ',', '(', ')', '"', "'", '"', '"', '\'', '\''];

    public function normalizeCompanyName(string $companyName): string
    {
        $value = trim($companyName);
        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;
        $value = mb_strtolower($value);
        $value = str_replace(self::STRIPPED_PUNCTUATION, '', $value);

        foreach (self::LEGAL_ENTITY_WORDS as $word) {
            $value = str_replace(mb_strtolower($word), '', $value);
        }

        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;

        return trim($value);
    }

    public function normalizePhone(?string $phone): ?string
    {
        if ($phone === null || $phone === '') {
            return null;
        }

        $digitsAndPlus = preg_replace('/[^\d+]/', '', $phone) ?? '';

        if (str_starts_with($digitsAndPlus, '+66')) {
            return '0'.substr($digitsAndPlus, 3);
        }

        if (str_starts_with($digitsAndPlus, '66') && strlen($digitsAndPlus) > 9) {
            return '0'.substr($digitsAndPlus, 2);
        }

        $digitsOnly = preg_replace('/\D/', '', $digitsAndPlus) ?? '';

        return $digitsOnly === '' ? null : $digitsOnly;
    }

    public function normalizeDomain(?string $url): ?string
    {
        if ($url === null || $url === '') {
            return null;
        }

        $candidate = trim($url);
        if (! str_contains($candidate, '//')) {
            $candidate = '//'.$candidate;
        }

        $host = parse_url($candidate, PHP_URL_HOST);
        if (! $host) {
            return null;
        }

        $host = mb_strtolower(rtrim($host, '/'));

        if (str_starts_with($host, 'www.')) {
            $host = substr($host, 4);
        }

        return $host === '' ? null : $host;
    }
}
