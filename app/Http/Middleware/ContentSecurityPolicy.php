<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ContentSecurityPolicy
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        // HSTS uniquement pour ce sous-domaine, si Cloudflare contacte aussi
        // l'origine en HTTPS. Ne pas se fier au seul X-Forwarded-Proto.
        if ($request->getHost() === 'geves.univkara.tg'
            && in_array(strtolower((string) $request->server('HTTPS')), ['on', '1'], true)) {
            $response->headers->set('Strict-Transport-Security', 'max-age=2592000');
        }

        if (! str_starts_with(strtolower((string) $response->headers->get('Content-Type')), 'text/html')) {
            return $response;
        }

        // Ces directives protègent déjà les pages sans limiter leurs ressources externes.
        $response->headers->set('Content-Security-Policy', "base-uri 'self'; object-src 'none'; frame-ancestors 'self'");

        // ⚠️ L'origine du service bureautique vient de la CONFIGURATION, jamais
        // d'une liste en dur : le SDK et son cadre sont servis par la salle de
        // l'établissement, dont l'adresse change d'une installation à l'autre.
        $docspace = parse_url((string) config('services.docspace.url'));
        $docspaceOrigin = is_array($docspace)
            && ($docspace['scheme'] ?? null) === 'https'
            && isset($docspace['host'])
                ? ' https://'.$docspace['host'].(isset($docspace['port']) ? ':'.$docspace['port'] : '')
                : '';

        // Les autres sources sont d'abord observées : les vues actuelles contiennent
        // encore des scripts/styles intégrés et plusieurs services externes.
        $response->headers->set('Content-Security-Policy-Report-Only', implode('; ', [
            "default-src 'self'",
            "script-src 'self' https://challenges.cloudflare.com https://cdn.fedapay.com".$docspaceOrigin,
            "style-src 'self' https://fonts.bunny.net https://fonts.googleapis.com",
            "font-src 'self' https://fonts.bunny.net https://fonts.gstatic.com data:",
            "img-src 'self' data: blob: https:",
            "connect-src 'self' https: wss:",
            "frame-src 'self' https://challenges.cloudflare.com https://www.youtube.com https://www.youtube-nocookie.com https://player.vimeo.com".$docspaceOrigin,
            "media-src 'self' blob: https:",
            "worker-src 'self' blob:",
            "form-action 'self' https:",
            'report-uri /csp-report',
            'report-to csp',
        ]));
        $response->headers->set('Reporting-Endpoints', 'csp="'.rtrim((string) config('app.url'), '/').'/csp-report"');

        return $response;
    }
}
