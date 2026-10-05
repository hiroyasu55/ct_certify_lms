<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\QaReply;
use App\Models\QaThread;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * QaReply モデルのリレーション・親スレッド削除時の cascade を検証する Unit テスト。
 * 2 リレーション (thread / user) + QaThread::replies との往復 + FK cascade を網羅する。
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

    public function test_user_relation_returns_responder(): void
    {
        // Arrange
        $coach = User::factory()->coach()->create();
        $reply = QaReply::factory()->for($coach)->create();

        // Act
        $responder = $reply->user;

        // Assert
        $this->assertTrue($responder->is($coach));
    }

    public function test_thread_replies_relation_returns_only_own_replies(): void
    {
        // Arrange
        $thread = QaThread::factory()->create();
        $ownReplies = QaReply::factory()->count(2)->forThread($thread)->create();
        QaReply::factory()->create();

        // Act
        $ids = $thread->replies()->pluck('id')->sort()->values()->all();

        // Assert
        $this->assertSame($ownReplies->pluck('id')->sort()->values()->all(), $ids, '他スレッドの回答は含まれないはず');
    }

    public function test_replies_are_deleted_when_parent_thread_is_deleted(): void
    {
        // Arrange
        $thread = QaThread::factory()->create();
        $reply = QaReply::factory()->forThread($thread)->create();

        // Act
        $thread->delete();

        // Assert
        $this->assertModelMissing($reply);
    }
}
