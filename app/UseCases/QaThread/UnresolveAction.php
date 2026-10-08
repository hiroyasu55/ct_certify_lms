<?php

declare(strict_types=1);

namespace App\UseCases\QaThread;

use App\Enums\QaThreadStatus;
use App\Models\QaThread;
use Illuminate\Support\Facades\DB;

/**
 * 質問スレッドを未解決に戻す
 */
final class UnresolveAction
{
    /**
     * @param QaThread $thread
     */
    public function __invoke(QaThread $thread): QaThread
    {
        DB::transaction(fn () => $thread->update([
            'status' => QaThreadStatus::Open,
            'resolved_at' => null,
        ]));

        return $thread;
    }
}
