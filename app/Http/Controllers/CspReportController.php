<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class CspReportController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $raw = $request->getContent();

        if (strlen($raw) > 8192) {
            return response()->noContent();
        }

        $payload = json_decode($raw, true);

        if (! is_array($payload)) {
            return response()->noContent();
        }

        // report-uri envoie {"csp-report": {...}} ; Reporting API envoie une liste.
        $reports = isset($payload['csp-report']) ? [$payload['csp-report']] : array_slice($payload, 0, 10);

        foreach ($reports as $report) {
            if (! is_array($report)) {
                continue;
            }

            $body = $report['body'] ?? $report;
            if (! is_array($body)) {
                continue;
            }

            $document = $body['document-uri'] ?? $body['documentURL'] ?? null;
            if (! is_string($document) || strcasecmp((string) parse_url($document, PHP_URL_HOST), $request->getHost()) !== 0) {
                continue;
            }

            $directive = $body['effective-directive'] ?? $body['effectiveDirective']
                ?? $body['violated-directive'] ?? $body['violatedDirective'] ?? null;
            if (! is_string($directive) || ! preg_match('/^[a-z][a-z-]{0,40}$/', $directive)) {
                continue;
            }

            $blocked = $body['blocked-uri'] ?? $body['blockedURL'] ?? '';
            if (! is_string($blocked)) {
                continue;
            }

            // Ne jamais journaliser l'URL de la page, ses paramètres, ni le corps du rapport.
            $source = in_array($blocked, ['inline', 'eval', 'data', 'blob', 'self'], true)
                ? $blocked
                : (parse_url($blocked, PHP_URL_HOST) ?: 'other');
            $source = substr((string) $source, 0, 120);

            if (Cache::add('csp-report:'.sha1($directive.'|'.$source), true, 3600)) {
                Log::warning('CSP report-only violation', [
                    'directive' => $directive,
                    'source' => $source,
                ]);
            }
        }

        return response()->noContent();
    }
}
