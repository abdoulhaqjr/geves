<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Media;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Documents & médias d'un événement (table `media`), contrôleur + Blade : liens
 * et ressources exposés aux participants de l'événement dans leur espace (livret,
 * documents, vidéos…).
 */
class EventMediaController extends Controller
{
    public function index(Event $event): View
    {
        return view('admin.evenements.documents', [
            'event' => $event,
            'media' => Media::where('event_id', $event->id)->orderBy('ordre')->orderBy('id')->get(),
            'types' => Media::TYPES,
        ]);
    }

    public function store(Request $request, Event $event): RedirectResponse
    {
        $data = $request->validate([
            'type' => ['required', 'in:'.implode(',', array_keys(Media::TYPES))],
            'title' => ['required', 'string', 'max:255'],
            'url' => ['required', 'url', 'max:500'],
        ]);

        $ordre = (int) Media::where('event_id', $event->id)->max('ordre') + 1;

        Media::create([
            'event_id' => $event->id,
            'type' => $data['type'],
            'title' => $data['title'],
            'url' => $data['url'],
            'ordre' => $ordre,
        ]);

        return back()->with('ukc_status', 'Document ajouté.');
    }

    public function destroy(Event $event, Media $medium): RedirectResponse
    {
        if ($medium->event_id === $event->id) {
            $medium->delete();
        }

        return back()->with('ukc_status', 'Document retiré.');
    }
}
