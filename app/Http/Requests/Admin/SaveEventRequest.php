<?php

namespace App\Http\Requests\Admin;

use App\Enums\EventType;
use App\Support\EventArtwork;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

/**
 * Validation de la fiche d'événement (création/mise à jour). En cas d'échec, on
 * réactive l'onglet du hub d'événement contenant la première erreur (flash
 * `edition_tab`). L'accès est gouverné par le middleware du back-office.
 */
class SaveEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'event_series_id' => ['nullable', 'exists:event_series,id'],
            'new_colloque' => ['nullable', 'string', 'max:200'],
            // Type de la série (colloque, séminaire, journées scientifiques…).
            'series_type' => ['nullable', Rule::enum(EventType::class)],
            'title' => ['required', 'string', 'max:255'],
            'event_label' => ['nullable', 'string', 'max:100'],
            'subtitle' => ['nullable', 'string', 'max:255'],
            'theme' => ['nullable', 'string', 'max:2000'],
            'year' => ['nullable', 'integer', 'min:2000', 'max:2100'],
            'date_start' => ['nullable', 'date'],
            'date_end' => ['nullable', 'date', 'after_or_equal:date_start'],
            'location' => ['nullable', 'string', 'max:600'],
            'status' => ['required', 'in:draft,open,closed,archived'],
            'slug' => ['nullable', 'string', 'max:220'],
            'submission_open' => ['boolean'],
            'submission_deadline' => ['nullable', 'date'],
            'max_communications' => ['required', 'integer', 'min:1', 'max:10'],
            'intro' => ['nullable', 'string', 'max:5000'],
            'short_name' => ['nullable', 'string', 'max:120'],
            'short_desc' => ['nullable', 'string', 'max:2000'],
            'format' => ['nullable', 'string', 'max:80'],
            'main_language' => ['nullable', 'string', 'max:60'],
            'languages' => ['array'],
            'languages.*' => ['nullable', 'string', 'max:60'],
            'tracks' => ['nullable', 'string', 'max:3000'],
            'axes' => ['array'],
            'axes.*.label' => ['nullable', 'string', 'max:200'],
            'keyDates' => ['array'],
            'keyDates.*.date' => ['nullable', 'string', 'max:40'],
            'keyDates.*.label' => ['nullable', 'string', 'max:200'],
            'why' => ['array'],
            'why.*.title' => ['nullable', 'string', 'max:200'],
            'why.*.text' => ['nullable', 'string', 'max:1000'],
            'infoCards' => ['array'],
            'infoCards.*.title' => ['nullable', 'string', 'max:120'],
            'infoCards.*.text' => ['nullable', 'string', 'max:1000'],
            'ref_prefix_mode' => ['nullable', 'in:global,axis'],
            'ref_prefix' => ['nullable', 'string', 'max:40'],
            'ref_axes' => ['array'],
            'ref_axes.*' => ['nullable', 'string', 'max:40'],
            'attestation_max' => ['nullable', 'integer', 'min:1', 'max:50'],
            'coauthors_max' => ['nullable', 'integer', 'min:1', 'max:30'],
            'gen_participation' => ['boolean'],
            'gen_communication' => ['boolean'],
            'hero_style' => ['nullable', 'string', 'max:60'],
            'hero_align' => ['nullable', 'string', 'max:20'],
            'color_primary' => ['nullable', 'string', 'max:20'],
            'color_secondary' => ['nullable', 'string', 'max:20'],
            'hero_video_enabled' => ['boolean'],
            'hero_video_muted' => ['boolean'],
            'stats_enabled' => ['boolean'],
            'stats' => ['array'],
            'stats.*.label' => ['nullable', 'string', 'max:60'],
            'stats.*.value' => ['nullable', 'string', 'max:40'],
            'opt_show_public' => ['boolean'],
            'opt_hybrid' => ['boolean'],
            'opt_livret' => ['boolean'],
            'opt_badges' => ['boolean'],
            'fees' => ['array'],
            'fees.*.label' => ['nullable', 'string', 'max:150'],
            'fees.*.amount' => ['nullable', 'numeric', 'min:0'],
            'fees.*.grades' => ['nullable', 'array'],
            'fees.*.grades.*' => ['nullable', 'string', 'max:100'],
            'fees.*.default' => ['nullable', 'boolean'],
            'committees' => ['array'], // rétro-compat (ancien format plat)
            'committees.*.role' => ['nullable', 'in:organisation,scientifique,supervision'],
            'committees.*.person_name' => ['nullable', 'string', 'max:200'],
            'committees.*.person_title' => ['nullable', 'string', 'max:200'],
            'committees.*.institution' => ['nullable', 'string', 'max:255'],
            'committees.*.photo_url' => ['nullable', 'string', 'max:500'],
            'committees_org' => ['array'],
            'committees_org.*.person_name' => ['nullable', 'string', 'max:200'],
            'committees_org.*.person_title' => ['nullable', 'string', 'max:200'],
            'committees_org.*.institution' => ['nullable', 'string', 'max:255'],
            'committees_sci' => ['array'],
            'committees_sci.*.person_name' => ['nullable', 'string', 'max:200'],
            'committees_sci.*.person_title' => ['nullable', 'string', 'max:200'],
            'committees_sci.*.institution' => ['nullable', 'string', 'max:255'],
            'committees_sup' => ['array'],
            'committees_sup.*.person_name' => ['nullable', 'string', 'max:200'],
            'committees_sup.*.person_title' => ['nullable', 'string', 'max:200'],
            'committees_sup.*.institution' => ['nullable', 'string', 'max:255'],
            'partners' => ['array'],
            'partners.*.name' => ['nullable', 'string', 'max:200'],
            'partners.*.url' => ['nullable', 'string', 'max:500'],
            'partners.*.logo_url' => ['nullable', 'string', 'max:500'],
            // Logo téléversé, ligne par ligne. ⚠️ Pas de SVG : il serait servi
            // depuis notre domaine et peut porter du script.
            'partners.*.logo_file' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:'.min(2048, EventArtwork::maxUploadKb())],
            'partners.*.level' => ['nullable', 'string', 'max:40'],
            'faq' => ['array'],
            'faq.*.question' => ['nullable', 'string', 'max:300'],
            'faq.*.answer' => ['nullable', 'string', 'max:2000'],
            'hero_image' => ['nullable', 'string', 'max:500'],
            'banner_image' => ['nullable', 'string', 'max:500'],
            // Habillage téléversé : c'est le fichier qui l'emporte sur l'URL saisie.
            'hero_image_file' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp,avif', 'max:'.min(3072, EventArtwork::maxUploadKb())],
            'banner_image_file' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp,avif', 'max:'.min(3072, EventArtwork::maxUploadKb())],
            // ⚠️ Plafond = celui du serveur : au-delà, PHP coupe la requête avant
            // la validation et l'admin n'obtiendrait aucun message.
            'hero_video_file' => ['nullable', 'file', 'mimetypes:video/mp4,video/webm', 'max:'.EventArtwork::maxUploadKb()],
            'hero_video' => ['nullable', 'string', 'max:500'],
            'contact_email' => ['nullable', 'email', 'max:200'],
            'contact_phone' => ['nullable', 'string', 'max:60'],
            'contact_address' => ['nullable', 'string', 'max:300'],
            'promo_text' => ['nullable', 'string', 'max:200'],
            'promo_url' => ['nullable', 'string', 'max:500'],
            'promo_cta' => ['nullable', 'string', 'max:60'],
            'banner_enabled' => ['boolean'],
            'social_facebook' => ['nullable', 'url', 'max:300'],
            'social_x' => ['nullable', 'url', 'max:300'],
            'social_youtube' => ['nullable', 'url', 'max:300'],
            'social_linkedin' => ['nullable', 'url', 'max:300'],
        ];
    }

    /** Garde : un colloque existant OU un nouveau colloque doit être fourni. */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v): void {
            if (! $this->filled('event_series_id') && trim((string) $this->input('new_colloque')) === '') {
                $v->errors()->add('event_series_id', 'Choisissez un colloque existant ou saisissez-en un nouveau.');
            }
        });
    }

    /** En cas d'échec, réactive l'onglet contenant la première erreur. */
    protected function failedValidation(Validator $validator): void
    {
        $tab = $this->tabForField((string) array_key_first($validator->errors()->toArray()));

        throw new HttpResponseException(
            back()->withInput()->withErrors($validator, $this->errorBag)->with('edition_tab', $tab)
        );
    }

    /** Associe un champ à l'onglet du hub d'événement qui le contient. */
    private function tabForField(string $field): string
    {
        return match (true) {
            str_starts_with($field, 'keyDates') => 'general',
            in_array($field, ['submission_open', 'submission_deadline', 'max_communications', 'ref_prefix', 'ref_prefix_mode', 'attestation_max', 'coauthors_max', 'gen_participation', 'gen_communication'], true),
            str_starts_with($field, 'fees'), str_starts_with($field, 'ref_axes') => 'submissions',
            str_starts_with($field, 'committees') => 'committees',
            str_starts_with($field, 'partners') => 'partners',
            in_array($field, ['hero_style', 'hero_align', 'color_primary', 'color_secondary', 'hero_image', 'hero_image_file', 'banner_image', 'banner_image_file', 'hero_video', 'hero_video_file', 'hero_video_enabled', 'hero_video_muted', 'stats_enabled', 'banner_enabled', 'promo_text', 'promo_url', 'promo_cta'], true),
            str_starts_with($field, 'stats') => 'appearance',
            str_starts_with($field, 'opt_') => 'options',
            in_array($field, ['intro', 'tracks'], true),
            str_starts_with($field, 'axes'), str_starts_with($field, 'why'), str_starts_with($field, 'infoCards'),
            str_starts_with($field, 'faq'), str_starts_with($field, 'contact') => 'content',
            default => 'general',
        };
    }
}
