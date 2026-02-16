<?php

use App\Models\Cagnotte;
use App\Models\AuditLog;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('cagnottes:close-expired', function () {
    $now = now();

    Cagnotte::query()
        ->where('status', 'active')
        ->where('ends_at', '<=', $now)
        ->orderBy('id')
        ->chunkById(100, function ($cagnottes) {
            foreach ($cagnottes as $cagnotte) {
                DB::transaction(function () use ($cagnotte) {
                    $locked = Cagnotte::query()
                        ->whereKey($cagnotte->id)
                        ->lockForUpdate()
                        ->first();

                    if (!$locked || $locked->status !== 'active') {
                        return;
                    }

                    if ($locked->ends_at && $locked->ends_at->isFuture()) {
                        return;
                    }

                    $locked->status = 'closed';
                    $locked->save();

                    AuditLog::query()->create([
                        'actor_user_id' => null,
                        'action' => 'cagnotte.closed_auto',
                        'auditable_type' => 'cagnotte',
                        'auditable_id' => $locked->id,
                        'metadata' => [
                            'ends_at' => optional($locked->ends_at)->toISOString(),
                        ],
                        'ip' => null,
                        'user_agent' => null,
                        'created_at' => now(),
                    ]);
                });
            }
        });
})->purpose('Close expired cagnottes (active -> closed)');

Schedule::command('cagnottes:close-expired')
    ->everyMinute()
    ->withoutOverlapping();
