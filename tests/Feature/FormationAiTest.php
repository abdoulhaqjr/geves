<?php

use App\AI\AiManager;
use App\AI\Contracts\AiProvider;

// adminStaff() -> AdminSubmissionWorkflowTest

/** Manager IA factice : disponible, et dont l'extraction renvoie des champs fixes. */
function fakeFormationManager(array $fields): AiManager
{
    return new class($fields) extends AiManager
    {
        /** @param array<string, mixed> $f */
        public function __construct(public array $f) {}

        public function resolve(bool $needsPdf = false): ?AiProvider
        {
            return new class implements AiProvider
            {
                public function key(): string
                {
                    return 'fake';
                }

                public function label(): string
                {
                    return 'Fake';
                }

                public function isAvailable(): bool
                {
                    return true;
                }

                public function supportsPdf(): bool
                {
                    return true;
                }

                public function extract(string $instruction, array $files = [], ?string $tier = null): array
                {
                    return [];
                }
            };
        }

        public function extract(string $instruction, array $files = [], ?string $tier = null, ?string $preferKey = null): array
        {
            return $this->f;
        }
    };
}

it('propose des champs de formation via le paramétrage assisté (IA)', function () {
    $this->app->instance(AiManager::class, fakeFormationManager([
        'title' => 'Rédaction scientifique', 'fee' => 15000, 'objective' => 'Structurer un article', 'starts_at' => '2026-10-01 09:00',
    ]));

    $this->actingAs(adminStaff(), 'web')
        ->post(route('admin.formations.ai'), ['aiPrompt' => 'Formation de rédaction, 15000 FCFA, le 1er octobre 9h'])
        ->assertOk()
        ->assertJsonPath('ok', true)
        ->assertJsonPath('fields.title', 'Rédaction scientifique')
        ->assertJsonPath('fields.fee', 15000)
        ->assertJsonPath('fields.starts_at', '2026-10-01T09:00');
});

it('refuse le paramétrage assisté sans document ni consigne', function () {
    $this->app->instance(AiManager::class, fakeFormationManager([]));

    $this->actingAs(adminStaff(), 'web')
        ->post(route('admin.formations.ai'), [])
        ->assertStatus(422)
        ->assertJsonPath('ok', false);
});
