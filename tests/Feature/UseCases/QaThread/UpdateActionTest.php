<?php

declare(strict_types=1);

namespace Tests\Feature\UseCases\QaThread;

use App\Models\Certification;
use App\Models\QaThread;
use App\UseCases\QaThread\UpdateAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_タイトルと本文を更新する(): void
    {
        $thread = QaThread::factory()->create();

        app(UpdateAction::class)($thread, [
            'title' => '更新後タイトル',
            'body' => '更新後本文',
        ]);

        $this->assertDatabaseHas('qa_threads', [
            'id' => $thread->id,
            'title' => '更新後タイトル',
            'body' => '更新後本文',
        ]);
    }

    public function test_資格と投稿者は変更しない(): void
    {
        $thread = QaThread::factory()->create();
        $originalCertId = $thread->certification_id;
        $originalUserId = $thread->user_id;

        app(UpdateAction::class)($thread, [
            'title' => '更新後タイトル',
            'body' => '更新後本文',
            'certification_id' => Certification::factory()->published()->create()->id,
        ]);

        $fresh = $thread->fresh();
        $this->assertSame($originalCertId, $fresh->certification_id, '資格は差し替わらないはず');
        $this->assertSame($originalUserId, $fresh->user_id);
    }

    public function test_更新後のスレッドを返す(): void
    {
        $thread = QaThread::factory()->create();

        $result = app(UpdateAction::class)($thread, [
            'title' => '更新後タイトル',
            'body' => '更新後本文',
        ]);

        $this->assertTrue($result->is($thread));
        $this->assertSame('更新後タイトル', $result->title);
    }
}
