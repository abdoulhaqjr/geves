<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Edition;
use App\Models\Media;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Documents & médias d'une édition (table `media`), contrôleur + Blade : liens
 * et ressources exposés aux participants de l'édition dans leur espace (livret,
 * documents, vidéos…).
 */
class EditionMediaController extends Controller
{
    public function index(Edition $edition): View
    {
        return view('admin.editions.documents', [
            'edition' => $edition,
            'media' => Media::where('edition_id', $edition->id)->orderBy('ordre')->orderBy('id')->get(),
            'types' => Media::TYPES,
        ]);
    }

    public function store(Request $request, Edition $edition): RedirectResponse
    {
        $data = $request->validate([
            'type' => ['required', 'in:'.implode(',', array_keys(Media::TYPES))],
            'title' => ['required', 'string', 'max:255'],
            'url' => ['required', 'url', 'max:500'],
        ]);

        $ordre = (int) Media::where('edition_id', $edition->id)->max('ordre') + 1;

        Media::create([
            'edition_id' => $edition->id,
            'type' => $data['type'],
            'title' => $data['title'],
            'url' => $data['url'],
            'ordre' => $ordre,
        ]);

        return back()->with('ukc_status', 'Document ajouté.');
    }

    public function destroy(Edition $edition, Media $medium): RedirectResponse
    {
        if ($medium->edition_id === $edition->id) {
            $medium->delete();
        }

        return back()->with('ukc_status', 'Document retiré.');
    }
}
