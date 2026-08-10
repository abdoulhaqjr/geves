<?php

namespace App\Livewire\Admin\Editions;

use App\Actions\ExtractEditionFromDocuments;
use App\AI\AiManager;
use App\Models\Colloque;
use App\Models\Edition;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Création / édition d'une édition de colloque (champs cœur + réglages :
 * introduction, axes thématiques, grille tarifaire).
 */
#[Title('Édition')]
class Form extends Component
{
    use WithFileUploads;

    public ?Edition $edition = null;

    // Paramétrage assisté par IA
    public array $aiFiles = [];

    public array $aiSummary = [];

    // Colloque de rattachement
    public ?int $colloque_id = null;

    public string $new_colloque = '';

    // Champs cœur
    public string $title = '';

    public string $edition_label = '';

    public string $subtitle = '';

    public string $theme = '';

    public ?int $year = null;

    public string $date_start = '';

    public string $date_end = '';

    public string $location = '';

    public string $status = 'draft';

    public string $slug = '';

    public bool $submission_open = false;

    public string $submission_deadline = '';

    public int $max_communications = 3;

    // Réglages
    public string $intro = '';

    public string $tracks = '';       // un axe par ligne

    public array $fees = [];          // [['label' => '', 'amount' => '']]

    public array $committees = [];    // [['role','person_name','person_title','institution','photo_url']]

    public array $partners = [];      // [['name','url','logo_url','level']]

    public array $faq = [];           // [['question','answer']]

    // Apparence & contact (page publique)
    public string $hero_image = '';

    public string $hero_video = '';

    public string $contact_email = '';

    public string $contact_phone = '';

    public string $contact_address = '';

    // Bannière promo + infos pratiques (page publique)
    public string $promo_text = '';

    public string $promo_url = '';

    public string $promo_cta = '';

    public string $practical_venue = '';

    public string $practical_accommodation = '';

    public string $practical_transport = '';

    /** Rôles de comité. */
    public array $committeeRoles = [
        'organisation' => 'Comité d’organisation',
        'scientifique' => 'Comité scientifique',
        'supervision' => 'Comité de supervision',
    ];

    public function mount(?Edition $edition = null): void
    {
        $this->year = (int) date('Y');

        if ($edition && $edition->exists) {
            $this->edition = $edition;
            $this->fillFromEdition($edition);
        }
    }

    protected function fillFromEdition(Edition $e): void
    {
        $this->colloque_id = $e->colloque_id;
        $this->title = (string) $e->title;
        $this->edition_label = (string) $e->edition_label;
        $this->subtitle = (string) $e->subtitle;
        $this->theme = (string) $e->theme;
        $this->year = (int) $e->year ?: (int) date('Y');
        $this->date_start = $e->date_start?->format('Y-m-d') ?? '';
        $this->date_end = $e->date_end?->format('Y-m-d') ?? '';
        $this->location = (string) $e->location;
        $this->status = (string) $e->status;
        $this->slug = (string) $e->slug;
        $this->submission_open = (bool) $e->submission_open;
        $this->submission_deadline = $e->submission_deadline?->format('Y-m-d\TH:i') ?? '';

        $settings = $e->settings ?? [];
        $this->max_communications = (int) ($settings['max_communications'] ?? 3);
        $this->intro = (string) ($settings['intro'] ?? '');
        $this->tracks = implode("\n", $settings['tracks'] ?? []);
        $this->fees = $settings['fees'] ?? [];
        $this->partners = $settings['partners'] ?? [];
        $this->faq = $settings['faq'] ?? [];
        $this->hero_image = (string) ($settings['hero_image'] ?? '');
        $this->hero_video = (string) ($settings['hero_video'] ?? '');
        $this->contact_email = (string) ($settings['contact']['email'] ?? '');
        $this->contact_phone = (string) ($settings['contact']['phone'] ?? '');
        $this->contact_address = (string) ($settings['contact']['address'] ?? '');
        $this->promo_text = (string) ($settings['promo']['text'] ?? '');
        $this->promo_url = (string) ($settings['promo']['url'] ?? '');
        $this->promo_cta = (string) ($settings['promo']['cta'] ?? '');
        $this->practical_venue = (string) ($settings['practical']['venue'] ?? '');
        $this->practical_accommodation = (string) ($settings['practical']['accommodation'] ?? '');
        $this->practical_transport = (string) ($settings['practical']['transport'] ?? '');

        $this->committees = $e->committeeMembers()
            ->orderBy('ordre')
            ->get(['role', 'person_name', 'person_title', 'institution', 'photo_url'])
            ->map(fn ($m) => $m->only(['role', 'person_name', 'person_title', 'institution', 'photo_url']))
            ->all();
    }

    public function addCommittee(): void
    {
        $this->committees[] = ['role' => 'organisation', 'person_name' => '', 'person_title' => '', 'institution' => '', 'photo_url' => ''];
    }

    public function removeCommittee(int $i): void
    {
        unset($this->committees[$i]);
        $this->committees = array_values($this->committees);
    }

    public function addPartner(): void
    {
        $this->partners[] = ['name' => '', 'url' => '', 'logo_url' => '', 'level' => ''];
    }

    public function removePartner(int $i): void
    {
        unset($this->partners[$i]);
        $this->partners = array_values($this->partners);
    }

    public function addFaq(): void
    {
        $this->faq[] = ['question' => '', 'answer' => ''];
    }

    public function removeFaq(int $i): void
    {
        unset($this->faq[$i]);
        $this->faq = array_values($this->faq);
    }

    /** Analyse les documents et pré-remplit les champs trouvés (révisable). */
    public function runAiAssist(ExtractEditionFromDocuments $action)
    {
        $this->validate([
            'aiFiles' => ['required', 'array', 'min:1', 'max:6'],
            'aiFiles.*' => ['file', 'mimes:pdf,png,jpg,jpeg,webp', 'max:10240'],
        ]);

        $files = collect($this->aiFiles)
            ->map(fn ($f) => ['mime' => $f->getMimeType(), 'data' => base64_encode($f->get())])
            ->all();

        try {
            $data = $action->handle($files);
        } catch (\Throwable $e) {
            report($e);
            $this->addError('aiFiles', 'Échec de l’analyse : '.$e->getMessage());

            return;
        }

        $this->applyExtracted($data);
        $this->aiFiles = [];

        session()->flash('ukc_status', $this->aiSummary === []
            ? 'Aucune information exploitable n’a été extraite des documents.'
            : count($this->aiSummary).' rubrique(s) pré-remplie(s) : '.implode(', ', $this->aiSummary).'. Vérifiez puis enregistrez.');
    }

    /** Applique les valeurs extraites aux champs du formulaire (sans écraser à vide). */
    protected function applyExtracted(array $d): void
    {
        $this->aiSummary = [];

        $text = ['title' => 'Titre', 'edition_label' => 'Libellé', 'subtitle' => 'Sous-titre',
            'theme' => 'Thème', 'location' => 'Lieu', 'intro' => 'Présentation'];
        foreach ($text as $k => $label) {
            if (filled($d[$k] ?? null)) {
                $this->{$k} = (string) $d[$k];
                $this->aiSummary[] = $label;
            }
        }

        if (! empty($d['year'])) {
            $this->year = (int) $d['year'];
            $this->aiSummary[] = 'Année';
        }
        if (! empty($d['date_start'])) {
            $this->date_start = $d['date_start'];
            $this->aiSummary[] = 'Dates';
        }
        if (! empty($d['date_end'])) {
            $this->date_end = $d['date_end'];
        }
        if (! empty($d['submission_deadline'])) {
            $this->submission_deadline = $d['submission_deadline'].'T23:59';
            $this->aiSummary[] = 'Date limite';
        }
        if (! empty($d['tracks'])) {
            $this->tracks = implode("\n", $d['tracks']);
            $this->aiSummary[] = 'Axes';
        }
        if (! empty($d['fees'])) {
            $this->fees = $d['fees'];
            $this->aiSummary[] = 'Tarifs';
        }
        if (! empty($d['committees'])) {
            $this->committees = $d['committees'];
            $this->aiSummary[] = 'Comités';
        }
        if (! empty($d['partners'])) {
            $this->partners = $d['partners'];
            $this->aiSummary[] = 'Partenaires';
        }
        if (! empty($d['faq'])) {
            $this->faq = $d['faq'];
            $this->aiSummary[] = 'FAQ';
        }
    }

    public function addFee(): void
    {
        $this->fees[] = ['label' => '', 'amount' => ''];
    }

    public function removeFee(int $i): void
    {
        unset($this->fees[$i]);
        $this->fees = array_values($this->fees);
    }

    protected function rules(): array
    {
        return [
            'colloque_id' => ['nullable', 'exists:colloques,id'],
            'new_colloque' => ['nullable', 'string', 'max:200'],
            'title' => ['required', 'string', 'max:255'],
            'edition_label' => ['nullable', 'string', 'max:100'],
            'subtitle' => ['nullable', 'string', 'max:255'],
            'theme' => ['nullable', 'string', 'max:2000'],
            'year' => ['nullable', 'integer', 'min:2000', 'max:2100'],
            'date_start' => ['nullable', 'date'],
            'date_end' => ['nullable', 'date', 'after_or_equal:date_start'],
            'location' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'in:draft,open,closed,archived'],
            'slug' => ['nullable', 'string', 'max:220'],
            'submission_open' => ['boolean'],
            'submission_deadline' => ['nullable', 'date'],
            'max_communications' => ['required', 'integer', 'min:1', 'max:10'],
            'intro' => ['nullable', 'string', 'max:5000'],
            'tracks' => ['nullable', 'string', 'max:3000'],
            'fees' => ['array'],
            'fees.*.label' => ['nullable', 'string', 'max:150'],
            'fees.*.amount' => ['nullable', 'numeric', 'min:0'],
            'committees' => ['array'],
            'committees.*.role' => ['nullable', 'in:organisation,scientifique,supervision'],
            'committees.*.person_name' => ['nullable', 'string', 'max:200'],
            'committees.*.person_title' => ['nullable', 'string', 'max:200'],
            'committees.*.institution' => ['nullable', 'string', 'max:255'],
            'committees.*.photo_url' => ['nullable', 'string', 'max:500'],
            'partners' => ['array'],
            'partners.*.name' => ['nullable', 'string', 'max:200'],
            'partners.*.url' => ['nullable', 'string', 'max:500'],
            'partners.*.logo_url' => ['nullable', 'string', 'max:500'],
            'partners.*.level' => ['nullable', 'string', 'max:40'],
            'faq' => ['array'],
            'faq.*.question' => ['nullable', 'string', 'max:300'],
            'faq.*.answer' => ['nullable', 'string', 'max:2000'],
            'hero_image' => ['nullable', 'string', 'max:500'],
            'hero_video' => ['nullable', 'string', 'max:500'],
            'contact_email' => ['nullable', 'email', 'max:200'],
            'contact_phone' => ['nullable', 'string', 'max:60'],
            'contact_address' => ['nullable', 'string', 'max:300'],
            'promo_text' => ['nullable', 'string', 'max:200'],
            'promo_url' => ['nullable', 'string', 'max:500'],
            'promo_cta' => ['nullable', 'string', 'max:60'],
            'practical_venue' => ['nullable', 'string', 'max:2000'],
            'practical_accommodation' => ['nullable', 'string', 'max:2000'],
            'practical_transport' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function save()
    {
        $data = $this->validate();

        if ($this->colloque_id === null && trim($this->new_colloque) === '') {
            $this->addError('colloque_id', 'Choisissez un colloque existant ou saisissez-en un nouveau.');

            return null;
        }

        // Colloque de rattachement (existant ou nouveau).
        $colloqueId = $this->colloque_id;
        if (trim($this->new_colloque) !== '') {
            $colloque = Colloque::create([
                'name' => $this->new_colloque,
                'slug' => $this->uniqueColloqueSlug(Str::slug($this->new_colloque)),
            ]);
            $colloqueId = $colloque->id;
        }

        $tracks = collect(preg_split('/\r\n|\r|\n/', $this->tracks))
            ->map(fn ($t) => trim($t))->filter()->values()->all();

        $fees = collect($this->fees)
            ->filter(fn ($f) => trim((string) ($f['label'] ?? '')) !== '')
            ->map(fn ($f) => ['label' => $f['label'], 'amount' => (float) ($f['amount'] ?? 0)])
            ->values()->all();

        $partners = collect($this->partners)
            ->filter(fn ($p) => trim((string) ($p['name'] ?? '')) !== '')
            ->map(fn ($p) => [
                'name' => $p['name'], 'url' => $p['url'] ?? null,
                'logo_url' => $p['logo_url'] ?? null, 'level' => $p['level'] ?? null,
            ])->values()->all();

        $faq = collect($this->faq)
            ->filter(fn ($f) => trim((string) ($f['question'] ?? '')) !== '')
            ->map(fn ($f) => ['question' => $f['question'], 'answer' => $f['answer'] ?? ''])
            ->values()->all();

        $payload = [
            'colloque_id' => $colloqueId,
            'title' => $this->title,
            'edition_label' => $this->edition_label,
            'subtitle' => $this->subtitle ?: null,
            'theme' => $this->theme ?: null,
            'year' => $this->year ?: 0,
            'date_start' => $this->date_start ?: null,
            'date_end' => $this->date_end ?: null,
            'location' => $this->location ?: null,
            'status' => $this->status,
            'slug' => $this->resolveSlug(),
            'submission_open' => $this->submission_open,
            'submission_deadline' => $this->submission_deadline ?: null,
            // Fusion : préserve les clés gérées ailleurs (ex. submission_form).
            'settings' => array_merge($this->edition?->settings ?? [], [
                'intro' => $this->intro ?: null,
                'tracks' => $tracks,
                'fees' => $fees,
                'max_communications' => $this->max_communications,
                'partners' => $partners,
                'faq' => $faq,
                'hero_image' => $this->hero_image ?: null,
                'hero_video' => $this->hero_video ?: null,
                'contact' => [
                    'email' => $this->contact_email ?: null,
                    'phone' => $this->contact_phone ?: null,
                    'address' => $this->contact_address ?: null,
                ],
                'promo' => [
                    'text' => $this->promo_text ?: null,
                    'url' => $this->promo_url ?: null,
                    'cta' => $this->promo_cta ?: null,
                ],
                'practical' => [
                    'venue' => $this->practical_venue ?: null,
                    'accommodation' => $this->practical_accommodation ?: null,
                    'transport' => $this->practical_transport ?: null,
                ],
            ]),
        ];

        // Enregistrement + sync des comités atomiques : la suppression puis
        // recréation des comités ne peut pas laisser l'édition dans un état
        // partiel si une écriture échoue.
        $edition = DB::transaction(function () use ($payload) {
            if ($this->edition) {
                $this->edition->update($payload);
                $edition = $this->edition;
            } else {
                $edition = Edition::create($payload);
            }

            $this->syncCommittees($edition);

            return $edition;
        });

        session()->flash('ukc_status', $this->edition ? 'Édition mise à jour.' : 'Édition créée.');

        return $this->redirect(route('admin.editions.index'), navigate: true);
    }

    /** Remplace les membres de comité de l'édition par ceux du formulaire. */
    protected function syncCommittees(Edition $edition): void
    {
        $edition->committeeMembers()->delete();

        $rows = collect($this->committees)
            ->filter(fn ($c) => trim((string) ($c['person_name'] ?? '')) !== '')
            ->values();

        foreach ($rows as $i => $c) {
            $edition->committeeMembers()->create([
                'role' => $c['role'] ?? 'organisation',
                'person_name' => $c['person_name'],
                'person_title' => $c['person_title'] ?? null,
                'institution' => $c['institution'] ?? null,
                'photo_url' => $c['photo_url'] ?? null,
                'ordre' => $i,
            ]);
        }
    }

    protected function resolveSlug(): string
    {
        $base = Str::slug($this->slug ?: $this->title);
        if ($base === '') {
            $base = 'edition';
        }

        $slug = $base;
        $i = 2;
        while (Edition::where('slug', $slug)
            ->when($this->edition, fn ($q) => $q->whereKeyNot($this->edition->id))
            ->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }

    protected function uniqueColloqueSlug(string $base): string
    {
        $base = $base ?: 'colloque';
        $slug = $base;
        $i = 2;
        while (Colloque::where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }

    public function render()
    {
        return view('livewire.admin.editions.form', [
            'colloques' => Colloque::orderBy('name')->get(),
            'aiAvailable' => app(AiManager::class)->available() !== [],
        ])->layout('components.layouts.admin', [
            'title' => $this->edition ? 'Modifier l’édition' : 'Nouvelle édition',
        ]);
    }
}
