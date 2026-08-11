<?php

use App\Enums\StreamStatus;
use App\Models\Account;
use App\Models\EventSession;
use App\Support\StreamEmbed;

// openEdition() -> AccountSubmissionTest

// --- StreamEmbed : normalisation des URL -----------------------------------

it('normalise les liens YouTube en URL embarquable', function (string $input) {
    expect(StreamEmbed::toEmbedUrl($input))->toBe('https://www.youtube.com/embed/dQw4w9WgXcQ');
    expect(StreamEmbed::provider($input))->toBe('youtube');
})->with([
    'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
    'https://youtu.be/dQw4w9WgXcQ',
    'https://www.youtube.com/live/dQw4w9WgXcQ',
    'https://www.youtube.com/embed/dQw4w9WgXcQ',
]);

it('normalise les liens Vimeo en URL embarquable', function () {
    expect(StreamEmbed::toEmbedUrl('https://vimeo.com/123456789'))
        ->toBe('https://player.vimeo.com/video/123456789');
    expect(StreamEmbed::provider('https://vimeo.com/123456789'))->toBe('vimeo');
});

it('laisse passer un embed personnalisé tel quel', function () {
    $url = 'https://stream.univkara.tg/live/session-1';
    expect(StreamEmbed::toEmbedUrl($url))->toBe($url);
    expect(StreamEmbed::provider($url))->toBe('custom');
});

it('renvoie null pour une URL vide', function () {
    expect(StreamEmbed::toEmbedUrl(''))->toBeNull();
    expect(StreamEmbed::provider(null))->toBeNull();
});

// --- EventSession : état & embed -------------------------------------------

it('reflète l’état de diffusion d’une session', function () {
    $edition = openEdition();

    $none = $edition->sessions()->create(['type' => 'plenary', 'label' => 'Sans diffusion']);
    expect($none->hasStream())->toBeFalse();
    expect($none->isLive())->toBeFalse();

    $live = $edition->sessions()->create([
        'type' => 'plenary', 'label' => 'Séance plénière',
        'stream_url' => 'https://youtu.be/dQw4w9WgXcQ',
        'stream_status' => StreamStatus::Live->value,
    ]);
    expect($live->hasStream())->toBeTrue();
    expect($live->isLive())->toBeTrue();
    expect($live->streamEmbedUrl())->toBe('https://www.youtube.com/embed/dQw4w9WgXcQ');
    expect($live->streamProvider())->toBe('youtube');
});

it('ne considère pas « live » une session au statut live sans URL', function () {
    $edition = openEdition();
    $session = $edition->sessions()->create([
        'type' => 'plenary', 'label' => 'Vide', 'stream_status' => StreamStatus::Live->value,
    ]);

    expect($session->isLive())->toBeFalse();
});

it('le scope live ne retourne que les sessions en direct avec URL', function () {
    $edition = openEdition();
    $edition->sessions()->create(['type' => 'plenary', 'label' => 'A', 'stream_url' => 'https://youtu.be/dQw4w9WgXcQ', 'stream_status' => 'live']);
    $edition->sessions()->create(['type' => 'plenary', 'label' => 'B', 'stream_status' => 'scheduled']);
    $edition->sessions()->create(['type' => 'plenary', 'label' => 'C', 'stream_status' => 'live']); // sans URL

    expect(EventSession::query()->live()->count())->toBe(1);
});

// --- Contrôle d'accès -------------------------------------------------------

it('autorise tout le monde sur une diffusion publique', function () {
    $edition = openEdition();
    $session = $edition->sessions()->create([
        'type' => 'plenary', 'label' => 'Ouverture', 'stream_url' => 'https://youtu.be/dQw4w9WgXcQ',
        'stream_status' => 'live', 'stream_visibility' => 'public',
    ]);

    expect($session->canBeWatchedBy(null))->toBeTrue();
});

it('réserve une diffusion « participants » aux inscrits de l’édition', function () {
    $edition = openEdition();
    $session = $edition->sessions()->create([
        'type' => 'plenary', 'label' => 'Atelier', 'stream_url' => 'https://youtu.be/dQw4w9WgXcQ',
        'stream_status' => 'live', 'stream_visibility' => 'participants',
    ]);

    $inscrit = Account::create(['first_name' => 'In', 'last_name' => 'Scrit', 'email' => 'in@example.tg', 'password' => 'pass12345']);
    $edition->participants()->create(['account_id' => $inscrit->id, 'first_name' => 'In', 'last_name' => 'Scrit', 'email' => 'in@example.tg', 'role' => 'author']);

    $etranger = Account::create(['first_name' => 'Et', 'last_name' => 'Ranger', 'email' => 'et@example.tg', 'password' => 'pass12345']);

    expect($session->canBeWatchedBy($inscrit))->toBeTrue();
    expect($session->canBeWatchedBy($etranger))->toBeFalse();
    expect($session->canBeWatchedBy(null))->toBeFalse();
});
