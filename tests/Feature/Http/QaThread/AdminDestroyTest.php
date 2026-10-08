<?php

declare(strict_types=1);

namespace Tests\Feature\Http\QaThread;

use App\Models\Certification;
use App\Models\QaReply;
use App\Models\QaThread;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDestroyTest extends TestCase
{
    use RefreshDatabase;

    public function test_管理者は質問を削除できる(): void
    {
        $admin = User::factory()->admin()->create();
        $thread = QaThread::factory()->create();

        $response = $this->actingAs($admin)->delete(route('admin.qa-board.destroy', $thread));

        $response->assertRedirect(route('admin.qa-board.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('qa_threads', ['id' => $thread->id]);
    }

    public function test_管理者はアーカイブされた資格の質問を削除できる(): void
    {
        $admin = User::factory()->admin()->create();
        $cert = Certification::factory()->archived()->create();
        $thread = QaThread::factory()->for($cert)->create();

        $response = $this->actingAs($admin)->delete(route('admin.qa-board.destroy', $thread));

        $response->assertRedirect(route('admin.qa-board.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('qa_threads', ['id' => $thread->id]);
    }

    public function test_回答が存在する質問を削除できる(): void
    {
        $admin = User::factory()->admin()->create();
        $thread = QaThread::factory()->create();
        $reply = QaReply::factory()->forThread($thread)->create();

        $response = $this->actingAs($admin)->delete(route('admin.qa-board.destroy', $thread));

        $response->assertRedirect(route('admin.qa-board.index'));
        $response->assertSessionHas('success');

        // 回答も同時に削除
        $this->assertDatabaseMissing('qa_threads', ['id' => $thread->id]);
        $this->assertDatabaseMissing('qa_replies', ['id' => $reply->id]);
    }

    public function test_受講者は自身の投稿した質問でも削除できない(): void
    {
        $student = User::factory()->student()->create();
        $thread = QaThread::factory()->for($student)->create();

        $response = $this->actingAs($student)->delete(route('admin.qa-board.destroy', $thread));

        $response->assertForbidden();
        $this->assertDatabaseHas('qa_threads', ['id' => $thread->id]);
    }

    public function test_コーチは質問を削除できない(): void
    {
        $coach = User::factory()->coach()->create();
        $thread = QaThread::factory()->create();

        $response = $this->actingAs($coach)->delete(route('admin.qa-board.destroy', $thread));

        $response->assertForbidden();
        $this->assertDatabaseHas('qa_threads', ['id' => $thread->id]);
    }
}
