<?php

declare(strict_types=1);

namespace App\UseCases\QaThread;

use App\Models\QaThread;

/**
 * 質問スレッドの詳細表示ユースケース。
 *
 * 詳細表示に必要なリレーション (certification / user / replies.user) を Eager Load して返す。回答は投稿順で並べる。
 */
final class ShowAction
{
    public function __invoke(QaThread $thread): QaThread
    {
        $thread
            ->loadMissing([
                'certification',
                'user',
                'replies' => fn ($q) => $q->with('user')->orderBy('created_at'),
            ])
            ->loadCount('replies');
        $thread->replies->each->setRelation('thread', $thread);

        return $thread;
    }
}
