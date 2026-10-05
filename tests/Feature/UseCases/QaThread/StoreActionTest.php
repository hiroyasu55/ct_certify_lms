<?php

declare(strict_types=1);

namespace Tests\Feature\UseCases\QaThread;

use App\Enums\QaThreadStatus;
use App\Models\Certification;
use App\Models\User;
use App\UseCases\QaThread\StoreAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StoreActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_受講者は質問を作成できる(): void
    {
        $student = User::factory()->student()->create();
        $cert = Certification::factory()->published()->create();

        $thread = app(StoreAction::class)($student, [
            'certification_id' => $cert->id,
            'title' => '質問タイトル',
            'body' => '質問本文',
        ]);

        $this->assertDatabaseHas('qa_threads', [
            'id' => $thread->id,
            'certification_id' => $cert->id,
            'user_id' => $student->id,
            'title' => '質問タイトル',
            'body' => '質問本文',
        ]);

        $fresh = $thread->fresh();
        $this->assertSame($student->id, $thread->user_id);
        $this->assertSame(QaThreadStatus::Unresolved, $fresh->status);
        $this->assertNull($fresh->resolved_at);
    }
}
