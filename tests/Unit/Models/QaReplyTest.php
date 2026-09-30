<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\QaReply;
use App\Models\QaThread;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * QaReply モデルのリレーションを検証する Unit テスト。
 * 2 リレーション (thread / user) + 親 QaThread 側の replies リレーションを網羅する。
 */
class QaReplyTest extends TestCase
{
    use RefreshDatabase;

    public function test_thread_relation_returns_parent_thread(): void
    {
        // Arrange
        $thread = QaThread::factory()->create();
        $reply = QaReply::factory()->forThread($thread)->create();

        // Act
        $parent = $reply->thread;

        // Assert
        $this->assertTrue($parent->is($thread));
    }

    public function test_user_relation_returns_author(): void
    {
        // Arrange
        $coach = User::factory()->coach()->create();
        $reply = QaReply::factory()->for($coach)->create();

        // Act
        $author = $reply->user;

        // Assert
        $this->assertTrue($author->is($coach));
    }

    public function test_user_relation_returns_null_for_withdrawn_author(): void
    {
        // Arrange
        $coach = User::factory()->coach()->create();
        $reply = QaReply::factory()->for($coach)->create();
        $coach->delete();

        // Act
        $author = $reply->fresh()->user;

        // Assert
        $this->assertNull($author, '論理削除されたユーザーは user リレーションで取得されないはず(画面では「不明」表示)');
    }

    public function test_thread_replies_relation_returns_only_own_replies(): void
    {
        // Arrange
        $thread = QaThread::factory()->create();
        QaReply::factory()->forThread($thread)->count(2)->create();
        QaReply::factory()->create();

        // Act
        $replies = $thread->replies;

        // Assert
        $this->assertCount(2, $replies, '対象スレッドの回答のみが取得されるはず');
    }
}
