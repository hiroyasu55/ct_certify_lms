<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Enums\QaThreadStatus;
use App\Models\Certification;
use App\Models\QaThread;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * QaThread モデルのリレーション・Cast・scope を検証する Unit テスト。
 * 2 リレーション (certification / user) + 2 cast (status enum / resolved_at datetime) + scope ordered を網羅する。
 */
class QaThreadTest extends TestCase
{
    use RefreshDatabase;

    public function test_certification_relation_returns_parent_certification(): void
    {
        // Arrange
        $cert = Certification::factory()->published()->create();
        $thread = QaThread::factory()->for($cert)->create();

        // Act
        $parent = $thread->certification;

        // Assert
        $this->assertTrue($parent->is($cert));
    }

    public function test_user_relation_returns_author(): void
    {
        // Arrange
        $student = User::factory()->student()->create();
        $thread = QaThread::factory()->for($student)->create();

        // Act
        $author = $thread->user;

        // Assert
        $this->assertTrue($author->is($student));
    }

    public function test_status_defaults_to_open(): void
    {
        // Arrange
        $thread = QaThread::factory()->create();

        // Act
        $fresh = $thread->fresh();

        // Assert
        $this->assertSame(QaThreadStatus::Open, $fresh->status, '作成直後のスレッドは未解決のはず');
    }

    public function test_status_cast_converts_to_enum(): void
    {
        // Arrange
        $thread = QaThread::factory()->resolved()->create();

        // Act
        $fresh = $thread->fresh();

        // Assert
        $this->assertInstanceOf(QaThreadStatus::class, $fresh->status, 'status は QaThreadStatus enum にキャストされるはず');
        $this->assertSame(QaThreadStatus::Resolved, $fresh->status);
    }

    public function test_resolved_at_cast_returns_carbon(): void
    {
        // Arrange
        $thread = QaThread::factory()->resolved()->create();

        // Act
        $fresh = $thread->fresh();

        // Assert
        $this->assertInstanceOf(Carbon::class, $fresh->resolved_at);
    }

    public function test_ordered_scope_sorts_by_created_at_desc(): void
    {
        // Arrange
        $oldest = QaThread::factory()->create(['created_at' => now()->subDays(2)]);
        $newest = QaThread::factory()->create(['created_at' => now()]);
        $middle = QaThread::factory()->create(['created_at' => now()->subDay()]);

        // Act
        $ids = QaThread::query()->ordered()->pluck('id')->all();

        // Assert
        $this->assertSame([$newest->id, $middle->id, $oldest->id], $ids, 'created_at の新しい順に並ぶはず');
    }
}
