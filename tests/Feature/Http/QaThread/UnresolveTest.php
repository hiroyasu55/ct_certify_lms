<?php

declare(strict_types=1);

namespace Tests\Feature\Http\QaThread;

use App\Enums\QaThreadStatus;
use App\Models\Certification;
use App\Models\QaThread;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UnresolveTest extends TestCase
{
    use RefreshDatabase;

    public function test_投稿主は解決済の投稿を未解決にできる(): void
    {
        $thread = QaThread::factory()->resolved()->create();

        $response = $this->actingAs($thread->user)
            ->post(route('qa-board.unresolve', $thread));

        $response->assertRedirect(route('qa-board.show', $thread));
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('qa_threads', [
            'id' => $thread->id,
            'status' => QaThreadStatus::Open,
        ]);
    }

    public function test_投稿主でない受講生は未解決にできない(): void
    {
        $other = User::factory()->student()->create();
        $thread = QaThread::factory()->resolved()->create();

        $response = $this->actingAs($other)
            ->post(route('qa-board.unresolve', $thread));

        $response->assertForbidden();
        $this->assertDatabaseHas('qa_threads', [
            'id' => $thread->id,
            'status' => QaThreadStatus::Resolved,
        ]);
    }

    public function test_アーカイブ済の資格の投稿は更新できない(): void
    {
        $cert = Certification::factory()->archived()->create();
        $thread = QaThread::factory()->for($cert)->resolved()->create();

        $response = $this->actingAs($thread->user)
            ->post(route('qa-board.unresolve', $thread));

        $response->assertNotFound();
        $this->assertDatabaseHas('qa_threads', [
            'id' => $thread->id,
            'status' => QaThreadStatus::Resolved,
        ]);
    }

    public function test_管理者は未解決にできない(): void
    {
        $admin = User::factory()->admin()->create();
        $thread = QaThread::factory()->resolved()->create();

        $response = $this->actingAs($admin)
            ->post(route('qa-board.unresolve', $thread));

        $response->assertForbidden();
        $this->assertDatabaseHas('qa_threads', [
            'id' => $thread->id,
            'status' => QaThreadStatus::Resolved,
        ]);
    }
}
