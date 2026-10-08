<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\CertificationStatus;
use App\Enums\UserRole;
use App\Models\QaReply;
use App\Models\QaThread;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * 質問スレッドへの回答の認可ルール。
 *
 * 回答投稿は受講生 / コーチ(管理者は不可)、編集・削除は回答者本人のみ許可する。
 */
class QaReplyPolicy
{
    /**
     * 公開中資格かどうか判定
     */
    private function isPublished(QaThread|QaReply $target): bool
    {
        $thread = $target instanceof QaReply ? $target->thread : $target;

        return $thread->certification->status === CertificationStatus::Published;
    }

    /**
     * 投稿者本人かどうか判定
     */
    private function authorOnly(User $auth, QaReply $reply): Response
    {
        return $reply->user_id === $auth->id
            ? Response::allow()
            : Response::deny();
    }

    public function create(User $auth, QaThread $thread): Response
    {
        return match ($auth->role) {
            UserRole::Student => $this->isPublished($thread)
                ? Response::allow()
                : Response::denyAsNotFound(),
            UserRole::Coach => $thread->certification->status === CertificationStatus::Published
                ? (
                    $thread->certification->coaches->contains('id', $auth->id)
                        ? Response::allow()
                        : Response::deny()
                )
                : Response::denyAsNotFound(),
            default => Response::deny(),
        };
    }

    public function update(User $auth, QaReply $reply): Response
    {
        return match ($auth->role) {
            UserRole::Student => $this->isPublished($reply)
                ? $this->authorOnly($auth, $reply)
                : Response::denyAsNotFound(),
            UserRole::Coach => $this->isPublished($reply)
                ? (
                    $reply->thread->certification->coaches->contains('id', $auth->id)
                        ? $this->authorOnly($auth, $reply)
                        : Response::deny()
                )
                : Response::denyAsNotFound(),
            default => Response::deny(),
        };
    }

    public function delete(User $auth, QaReply $reply): Response
    {
        return match ($auth->role) {
            UserRole::Student => $this->isPublished($reply)
                ? $this->authorOnly($auth, $reply)
                : Response::denyAsNotFound(),
            UserRole::Coach => $this->isPublished($reply)
                ? (
                    $reply->thread->certification->coaches->contains('id', $auth->id)
                        ? $this->authorOnly($auth, $reply)
                        : Response::deny()
                )
                : Response::denyAsNotFound(),
            UserRole::Admin => Response::allow(),
            default => Response::deny(),
        };
    }
}
