<?php

declare(strict_types=1);

namespace Tests\Feature\Http\QaThread;

use App\Enums\QaThreadStatus;
use App\Models\Certification;
use App\Models\QaThread;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResolveTest extends TestCase
{
    use RefreshDatabase;

    public function test_投稿主は未解決の投稿を解決済にマークできる(): void
    {
        $thread = QaThread::factory()->open()->create();

        $response = $this->actingAs($thread->user)
            ->post(route('qa-board.resolve', $thread));

        $response->assertRedirect(route('qa-board.show', $thread));
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('qa_threads', [
            'id' => $thread->id,
            'status' => QaThreadStatus::Resolved,
        ]);
    }

    public function test_投稿主でない受講生は解決済にできない(): void
    {
        $other = User::factory()->student()->create();
        $thread = QaThread::factory()->open()->create();

        $response = $this->actingAs($other)
            ->post(route('qa-board.resolve', $thread));

        $response->assertForbidden();
        $this->assertDatabaseHas('qa_threads', [
            'id' => $thread->id,
            'status' => QaThreadStatus::Open,
        ]);
    }

    public function test_アーカイブ済の資格の投稿は更新できない(): void
    {
        $cert = Certification::factory()->archived()->create();
        $thread = QaThread::factory()->for($cert)->open()->create();

        $response = $this->actingAs($thread->user)
            ->post(route('qa-board.resolve', $thread));

        $response->assertNotFound();
        $this->assertDatabaseHas('qa_threads', [
            'id' => $thread->id,
            'status' => QaThreadStatus::Open,
        ]);
    }

    public function test_管理者は解決済にマークできない(): void
    {
        $admin = User::factory()->admin()->create();
        $thread = QaThread::factory()->open()->create();

        $response = $this->actingAs($admin)
            ->post(route('qa-board.resolve', $thread));

        $response->assertForbidden();
        $this->assertDatabaseHas('qa_threads', [
            'id' => $thread->id,
            'status' => QaThreadStatus::Open,
        ]);
    }
}
