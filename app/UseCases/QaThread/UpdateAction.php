<?php

declare(strict_types=1);

namespace App\UseCases\QaThread;

use App\Models\QaThread;
use Illuminate\Support\Facades\DB;

/**
 * 質問スレッドのタイトル・本文を更新するユースケース。資格の差し替えは不可。
 */
final class UpdateAction
{
    /**
     * @param array{title: string, body: string} $validated
     */
    public function __invoke(QaThread $thread, array $validated): QaThread
    {
        DB::transaction(fn () => $thread->update([
            'title' => $validated['title'],
            'body' => $validated['body'],
        ]));

        return $thread;
    }
}
