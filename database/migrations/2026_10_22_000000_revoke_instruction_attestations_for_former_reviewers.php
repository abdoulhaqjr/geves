<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $attestations = DB::table('attestations as a')
            ->join('participants as p', 'p.id', '=', 'a.participant_id')
            ->whereColumn('p.event_id', 'a.event_id')
            ->where('a.type', 'instruction_resumes')
            ->where('a.revoked', false)
            ->where('p.role', '!=', 'reviewer')
            ->select('a.id', 'p.id as participant_id', 'p.meta')
            ->get();

        foreach ($attestations as $attestation) {
            DB::table('attestations')->where('id', $attestation->id)->update(['revoked' => true]);
            $meta = json_decode((string) ($attestation->meta ?? ''), true);
            $meta = is_array($meta) ? $meta : [];
            $meta['instruction_attestation_revoked_with_reviewer'] = true;
            DB::table('participants')->where('id', $attestation->participant_id)
                ->update(['meta' => json_encode($meta, JSON_UNESCAPED_UNICODE)]);
        }
    }

    public function down(): void
    {
        // Une attestation devenue invalide ne peut pas être rétablie par un rollback.
    }
};
