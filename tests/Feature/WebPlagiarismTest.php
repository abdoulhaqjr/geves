<?php

use App\Support\Plagiarism\GoogleSearchProvider;
use App\Support\Plagiarism\PlagiarismRegistry;
use App\Support\Plagiarism\PrepostseoProvider;
use App\Support\Settings;
use Illuminate\Support\Facades\Http;

// adminStaff() et seededSubmission() sont définis dans tests/Pest.php.

/** Réponse au format documenté par Prepostseo. */
function fakePrepostseo(float $plag = 42.5): void
{
    Http::fake([
        '*prepostseo.com/apis/checkPlag' => Http::response([
            'plagPercent' => $plag,
            'uniquePercent' => 100 - $plag,
            'paraphrasePercent' => 0,
            'totalQueries' => 3,
            'isQueriesFinished' => false,
            'sources' => [
                ['link' => 'https://exemple.org/article-a', 'count' => 4, 'percent' => 30],
                ['link' => 'https://exemple.org/article-b', 'count' => 1, 'percent' => 12.5],
            ],
        ], 200),
    ]);
}

it('parse la réponse Prepostseo en résultat normalisé', function () {
    Settings::set('plagiarism.prepostseo_key', 'test-key');
    fakePrepostseo(42.5);

    $result = (new PrepostseoProvider)->check('Un résumé scientifique à vérifier contre le web.');

    expect($result['provider'])->toBe('prepostseo')
        ->and($result['plagPercent'])->toBe(42.5)
        ->and($result['uniquePercent'])->toBe(57.5)
        ->and($result['sources'])->toHaveCount(2)
        ->and($result['sources'][0]['link'])->toBe('https://exemple.org/article-a');
});

it('le registre n’active un fournisseur que s’il est configuré', function () {
    expect((new PlagiarismRegistry)->active())->toBeNull(); // « none » par défaut

    Settings::set('plagiarism.provider', 'prepostseo');
    expect((new PlagiarismRegistry)->active())->toBeNull(); // pas de clé → inactif

    Settings::set('plagiarism.prepostseo_key', 'k');
    expect((new PlagiarismRegistry)->active())->not->toBeNull();
});

it('lance la vérification et met le résultat en cache sur la soumission', function () {
    $staff = adminStaff();
    $submission = seededSubmission('submitted');
    Settings::set('plagiarism.provider', 'prepostseo');
    Settings::set('plagiarism.prepostseo_key', 'k');
    fakePrepostseo(37.0);

    $this->actingAs($staff, 'web')
        ->post(route('admin.submissions.plagiarism', $submission))
        ->assertRedirect();

    expect((float) $submission->fresh()->meta['web_plagiarism']['plagPercent'])->toBe(37.0);
});

it('Google Recherche calcule un score de présence web et remonte les sources', function () {
    Settings::set('plagiarism.google_key', 'k');
    Settings::set('plagiarism.google_cx', 'cx');
    Http::fake([
        '*googleapis.com/customsearch*' => Http::response([
            'items' => [['link' => 'https://exemple.org/source', 'title' => 'T', 'snippet' => 'S']],
        ], 200),
    ]);

    $text = 'Cette étude analyse en profondeur les déterminants socio économiques de la réussite scolaire au Sahel. '
        .'Les résultats montrent une corrélation forte entre accompagnement familial et performance académique des élèves.';

    $result = (new GoogleSearchProvider)->check($text);

    expect($result['provider'])->toBe('google')
        ->and($result['plagPercent'])->toBe(100.0)
        ->and($result['sources'][0]['link'])->toBe('https://exemple.org/source');
});

it('le registre active Google si clé + cx sont présents', function () {
    Settings::set('plagiarism.provider', 'google');
    expect((new PlagiarismRegistry)->active())->toBeNull(); // clé/cx manquants

    Settings::set('plagiarism.google_key', 'k');
    Settings::set('plagiarism.google_cx', 'cx');
    expect((new PlagiarismRegistry)->active())->not->toBeNull();
});

it('refuse la vérification si aucun fournisseur n’est configuré', function () {
    $staff = adminStaff();
    $submission = seededSubmission('submitted');

    $this->actingAs($staff, 'web')
        ->post(route('admin.submissions.plagiarism', $submission))
        ->assertRedirect();

    expect($submission->fresh()->meta['web_plagiarism'] ?? null)->toBeNull();
});
