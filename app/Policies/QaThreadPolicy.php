<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\CertificationStatus;
use App\Enums\UserRole;
use App\Models\QaThread;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * 質問スレッドの認可ルール。
 *
 * 閲覧は受講生 / コーチ、投稿は受講生のみ、編集・削除は投稿者本人のみ許可する。
 */
class QaThreadPolicy
{
    /**
     * 公開中資格かどうか判定
     */
    private function isPublished(QaThread $thread): bool
    {
        return $thread->certification->status === CertificationStatus::Published;
    }

    /**
     * 投稿者本人かどうか判定
     */
    private function authorOnly(User $auth, QaThread $thread): Response
    {
        return $thread->user_id === $auth->id
            ? Response::allow()
            : Response::deny();
    }

    public function viewAny(User $auth): bool
    {
        return in_array($auth->role, [UserRole::Student, UserRole::Coach, UserRole::Admin], true);
    }

    public function view(User $auth, QaThread $thread): Response
    {
        return match ($auth->role) {
            UserRole::Student => $this->isPublished($thread)
                ? Response::allow()
                : Response::denyAsNotFound(),
            UserRole::Coach => $this->isPublished($thread)
                ? (
                    $thread->certification->coaches->contains('id', $auth->id)
                        ? Response::allow()
                        : Response::deny()
                )
                : Response::denyAsNotFound(),
            UserRole::Admin => Response::allow(),
            default => Response::deny(),
        };
    }

    public function create(User $auth): bool
    {
        return $auth->role == UserRole::Student;
    }

    public function update(User $auth, QaThread $thread): Response
    {
        return match ($auth->role) {
            UserRole::Student => $this->isPublished($thread)
                ? $this->authorOnly($auth, $thread)
                : Response::denyAsNotFound(),
            default => Response::deny(),
        };
    }

    public function delete(User $auth, QaThread $thread): Response
    {
        return match ($auth->role) {
            UserRole::Student => $this->isPublished($thread)
                ? $this->authorOnly($auth, $thread)
                : Response::denyAsNotFound(),
            UserRole::Admin => Response::allow(),
            default => Response::deny(),
        };
    }

    public function resolve(User $auth, QaThread $thread): Response
    {
        return match ($auth->role) {
            UserRole::Student => $this->isPublished($thread)
                ? $this->authorOnly($auth, $thread)
                : Response::denyAsNotFound(),
            default => Response::deny(),
        };
    }

    public function unresolve(User $auth, QaThread $thread): Response
    {
        return match ($auth->role) {
            UserRole::Student => $this->isPublished($thread)
                ? $this->authorOnly($auth, $thread)
                : Response::denyAsNotFound(),
            default => Response::deny(),
        };
    }
}
