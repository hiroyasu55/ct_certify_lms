<?php

declare(strict_types=1);

namespace Tests\Feature\UseCases\QaThread;

use App\Models\QaReply;
use App\Models\QaThread;
use App\UseCases\QaThread\ShowAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShowActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_詳細表示に必要なリレーションを読み込む(): void
    {
        $thread = QaThread::factory()->create();
        QaReply::factory()->forThread($thread)->create();

        $result = app(ShowAction::class)($thread->fresh());

        $this->assertTrue($result->relationLoaded('certification'));
        $this->assertTrue($result->relationLoaded('user'));
        $this->assertTrue($result->relationLoaded('replies'));
        $this->assertTrue($result->replies->first()->relationLoaded('user'));
    }

    public function test_回答が投稿順に並ぶ(): void
    {
        $thread = QaThread::factory()->create();
        $second = QaReply::factory()->forThread($thread)->create(['created_at' => now()->subDay()]);
        $third = QaReply::factory()->forThread($thread)->create(['created_at' => now()]);
        $first = QaReply::factory()->forThread($thread)->create(['created_at' => now()->subDays(2)]);

        $result = app(ShowAction::class)($thread);

        $this->assertSame(
            [$first->id, $second->id, $third->id],
            $result->replies->pluck('id')->all(),
        );
    }

    public function test_対象スレッドの回答だけを読み込む(): void
    {
        $thread = QaThread::factory()->create();
        $own = QaReply::factory()->forThread($thread)->create();
        QaReply::factory()->create();

        $result = app(ShowAction::class)($thread);

        $this->assertSame([$own->id], $result->replies->pluck('id')->all());
    }

    public function test_回答数を読み込む(): void
    {
        $thread = QaThread::factory()->create();
        QaReply::factory()->forThread($thread)->count(2)->create();

        $result = app(ShowAction::class)($thread);

        $this->assertSame(2, $result->replies_count);
    }

    public function test_回答がない場合は回答数0(): void
    {
        $thread = QaThread::factory()->create();

        $result = app(ShowAction::class)($thread);

        $this->assertSame(0, $result->replies_count);
        $this->assertTrue($result->replies->isEmpty());
    }
}
