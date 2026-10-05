<?php

declare(strict_types=1);

namespace App\UseCases\QaThread;

use App\Enums\QaThreadStatus;
use App\Models\QaThread;
use Illuminate\Support\Facades\DB;

/**
 * 質問スレッドを解決済にする
 */
final class ResolveAction
{
    /**
     * @param QaThread $thread
     */
    public function __invoke(QaThread $thread): QaThread
    {
        DB::transaction(fn () => $thread->update([
            'status' => QaThreadStatus::Resolved,
            'resolved_at' => now(),
        ]));

        return $thread;
    }
}
