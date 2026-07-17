<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

/**
 * Verifies signature, timestamp freshness, replay, and payload size for
 * every request coming from the Python research service
 * (Requirement.md §11). Mirrors app/security/hmac.py + internal_auth.py
 * on the Python side exactly, so both directions of the internal API
 * use the same guarantees.
 */
class VerifyInternalSignature
{
    private const MAX_PAYLOAD_BYTES = 2 * 1024 * 1024; // 2MB

    public function handle(Request $request, Closure $next): Response
    {
        $internalKey = $request->header('X-Internal-Key');
        $timestamp = $request->header('X-Timestamp');
        $signature = $request->header('X-Signature');
        $idempotencyKey = $request->header('X-Idempotency-Key');

        if (! $internalKey || ! $timestamp || ! $signature || ! $idempotencyKey) {
            abort(401, 'Missing internal auth headers');
        }

        $body = $request->getContent();

        if (strlen($body) > self::MAX_PAYLOAD_BYTES) {
            abort(413, 'Payload too large');
        }

        if (! $this->isFreshTimestamp((int) $timestamp)) {
            abort(401, 'Request timestamp is outside the allowed window');
        }

        if (! hash_equals($this->expectedSignature($timestamp, $body), (string) $signature)) {
            abort(401, 'Invalid signature');
        }

        $idempotencyCacheKey = "internal-idempotency:{$idempotencyKey}";

        if (Cache::has($idempotencyCacheKey)) {
            abort(409, 'Duplicate request (idempotency key reused)');
        }

        Cache::put($idempotencyCacheKey, true, now()->addSeconds(config('internal.signature_ttl_seconds')));

        return $next($request);
    }

    private function isFreshTimestamp(int $timestamp): bool
    {
        $ttl = (int) config('internal.signature_ttl_seconds');

        return abs(time() - $timestamp) <= $ttl;
    }

    private function expectedSignature(string $timestamp, string $body): string
    {
        $secret = (string) config('internal.shared_secret');

        return hash_hmac('sha256', "{$timestamp}.{$body}", $secret);
    }
}
