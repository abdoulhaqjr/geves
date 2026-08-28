<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Edition;
use App\Models\Email;
use App\Models\Payment;
use App\Models\Submission;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Tableau de bord du back-office (Blade, fidèle au mockup admin-dashboard.html) :
 * bannière de l'édition active, indicateurs, pipeline des soumissions, graphiques
 * (Chart.js), dernières soumissions, actions prioritaires et activité récente.
 * Données agrégées en temps réel — vue en lecture seule, sans état (donc Blade).
 */
class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $edition = Edition::with('colloque')->where('status', 'open')->latest('id')->first()
            ?? Edition::with('colloque')->latest('id')->first();
        $edId = $edition?->id;

        $subs = fn () => Submission::query()->where('revision_of', 0)
            ->when($edId, fn ($q) => $q->where('edition_id', $edId));
        $pay = fn () => Payment::query()->when($edId, fn ($q) => $q->where('edition_id', $edId));

        $byStatus = $subs()->selectRaw('status, count(*) as c')->groupBy('status')->pluck('c', 'status');
        $n = fn (string $s): int => (int) ($byStatus[$s] ?? 0);

        $accepted = $n('accepted');
        $oral = $subs()->where('status', 'accepted')->where('type', 'oral')->count();

        $revenue = (float) $pay()->where('status', 'paid')->sum('amount');
        $paidParticipants = $pay()->where('status', 'paid')->distinct('participant_id')->count('participant_id');
        $pendingPay = $pay()->where('status', 'pending')->count();

        // Soumissions par mois (6 derniers mois).
        $months = collect(range(5, 0))->map(fn ($i) => Carbon::now()->subMonths($i));
        $subsMonth = $months->map(fn (Carbon $m) => $subs()
            ->whereBetween('created_at', [$m->copy()->startOfMonth(), $m->copy()->endOfMonth()])->count());

        // Axes thématiques (tracks).
        $byTrack = $subs()->selectRaw("COALESCE(NULLIF(track, ''), 'Autres') as t, count(*) as c")
            ->groupBy('t')->orderByDesc('c')->limit(6)->pluck('c', 't');

        // Paiements cumulés (8 dernières semaines).
        $paidRows = $pay()->where('status', 'paid')->get(['amount', 'created_at']);
        $weekEnds = collect(range(7, 0))->map(fn ($i) => Carbon::now()->subWeeks($i)->endOfWeek());
        $cumRevenue = $weekEnds->map(fn (Carbon $w) => (int) round($paidRows->where('created_at', '<=', $w)->sum('amount') / 1000));
        $cumCount = $weekEnds->map(fn (Carbon $w) => $paidRows->where('created_at', '<=', $w)->count());

        $recent = $subs()->with('participant')->latest('created_at')->limit(6)->get();

        // Tendances 7 jours (hints « + … cette semaine »).
        $subs7 = $subs()->where('created_at', '>=', now()->subDays(7))->count();
        $pay7 = $pay()->where('status', 'paid')->where('created_at', '>=', now()->subDays(7))->count();

        // Calendrier clé : jalons datés de l'édition (réels, masqués si absents).
        $s = $edition?->settings ?? [];
        $calendar = collect([
            ['Clôture soumissions', $edition?->submission_deadline],
            ["Notifications d'acceptation", isset($s['notification_date']) ? Carbon::parse($s['notification_date']) : null],
            ['Fin des inscriptions', isset($s['registration_end']) ? Carbon::parse($s['registration_end']) : null],
            ['Colloque', $edition?->date_start],
        ])->filter(fn ($r) => $r[1] instanceof Carbon)->map(function ($r) use ($edition) {
            $val = $r[0] === 'Colloque' && $edition?->date_end
                ? $edition->date_start->format('d/m').'–'.$edition->date_end->format('d/m/Y')
                : $r[1]->format('d/m/Y');

            return ['label' => $r[0], 'value' => $val];
        })->values();

        // Actions prioritaires réelles.
        $todos = collect([
            ['txt' => $n('submitted').' nouvelle(s) soumission(s) à assigner', 'n' => $n('submitted'), 'pill' => 'err', 'lbl' => 'Urgent'],
            ['txt' => $n('under_review').' avis en revue à décider', 'n' => $n('under_review'), 'pill' => 'review', 'lbl' => 'À décider'],
            ['txt' => $pendingPay.' paiement(s) en attente à relancer', 'n' => $pendingPay, 'pill' => 'warn', 'lbl' => 'Moyen'],
        ])->filter(fn ($t) => $t['n'] > 0)->values();

        // Activité récente (soumissions + paiements).
        $activity = $recent->map(fn (Submission $s) => [
            'dot' => 'blue', 'txt' => 'Soumission « '.Str::limit($s->title, 42).' »',
            'when' => $s->created_at?->diffForHumans(),
        ])->merge(
            $pay()->where('status', 'paid')->with('participant')->latest('created_at')->limit(3)->get()
                ->map(fn (Payment $p) => [
                    'dot' => 'green', 'txt' => 'Paiement validé — '.(optional($p->participant)->full_name ?? 'participant'),
                    'when' => $p->created_at?->diffForHumans(),
                ])
        )->sortByDesc(fn ($a) => $a['when'])->take(7)->values();

        $dashboardData = [
            'status' => [
                'labels' => ['Soumises', 'En revue', 'Corrections', 'Acceptées', 'Refusées'],
                'data' => [$n('submitted'), $n('under_review'), $n('revision'), $accepted, $n('rejected')],
            ],
            'months' => ['labels' => $months->map(fn (Carbon $m) => ucfirst($m->translatedFormat('M')))->all(), 'data' => $subsMonth->all()],
            'axes' => ['labels' => $byTrack->keys()->all(), 'data' => $byTrack->values()->map(fn ($v) => (int) $v)->all()],
            'payments' => [
                'labels' => collect(range(1, 8))->map(fn ($i) => 'S'.$i)->all(),
                'revenue' => $cumRevenue->all(),
                'count' => $cumCount->all(),
            ],
        ];

        return view('admin.dashboard', [
            'edition' => $edition,
            'stats' => [
                'submissions' => $subs()->count(),
                'underReview' => $n('under_review'),
                'accepted' => $accepted,
                'oral' => $oral,
                'poster' => max(0, $accepted - $oral),
                'paidParticipants' => $paidParticipants,
                'revenue' => $revenue,
                'emails7' => Email::when($edId, fn ($q) => $q->where('edition_id', $edId))->where('created_at', '>=', now()->subDays(7))->count(),
                'subs7' => $subs7,
                'pay7' => $pay7,
            ],
            'pipeline' => [
                'submitted' => $n('submitted'), 'under_review' => $n('under_review'),
                'revision' => $n('revision'), 'accepted' => $accepted, 'rejected' => $n('rejected'),
            ],
            'recent' => $recent,
            'todos' => $todos,
            'activity' => $activity,
            'calendar' => $calendar,
            'dashboardData' => $dashboardData,
        ]);
    }
}
