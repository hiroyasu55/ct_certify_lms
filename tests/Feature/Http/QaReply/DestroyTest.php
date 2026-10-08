<?php

declare(strict_types=1);

namespace Tests\Feature\Http\QaReply;

use App\Models\Certification;
use App\Models\QaReply;
use App\Models\QaThread;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\ContentTestHelpers;
use Tests\TestCase;

class DestroyTest extends TestCase
{
    use ContentTestHelpers, RefreshDatabase;

    public function test_受講者は自身の投稿した回答を削除できる(): void
    {
        $student = User::factory()->student()->create();
        $reply = QaReply::factory()->for($student)->create();

        $response = $this->actingAs($reply->user)
            ->delete(route('qa-board.replies.destroy', ['thread' => $reply->qa_thread_id, 'reply' => $reply->id]));

        $response->assertRedirect(route('qa-board.show', $reply->qa_thread_id));
        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('qa_replies', ['id' => $reply->id]);
    }

    public function test_受講者は自身の投稿でない回答を削除できない(): void
    {
        $student = User::factory()->student()->create();
        $reply = QaReply::factory()->create();

        $response = $this->actingAs($student)
            ->delete(route('qa-board.replies.destroy', ['thread' => $reply->qa_thread_id, 'reply' => $reply->id]));

        $response->assertForbidden();
        $this->assertDatabaseHas('qa_replies', ['id' => $reply->id]);
    }

    public function test_受講者はアーカイブ済の資格の回答削除時に404エラー(): void
    {
        $cert = Certification::factory()->archived()->create();
        $thread = QaThread::factory()->for($cert)->create();
        $reply = QaReply::factory()->forThread($thread)->create();

        $response = $this->actingAs($reply->user)
            ->delete(route('qa-board.replies.destroy', ['thread' => $reply->qa_thread_id, 'reply' => $reply->id]));

        $response->assertNotFound();
        $this->assertDatabaseHas('qa_threads', ['id' => $thread->id]);
    }

    public function test_コーチは自身の投稿した回答を削除できる(): void
    {
        $coach = User::factory()->coach()->create();
        $cert = Certification::factory()->published()->create();
        $this->assignCoach($coach, $cert);
        $thread = QaThread::factory()->for($cert)->create();
        $reply = QaReply::factory()->forThread($thread)->for($coach)->create();

        $response = $this->actingAs($reply->user)
            ->delete(route('qa-board.replies.destroy', ['thread' => $reply->qa_thread_id, 'reply' => $reply->id]));

        $response->assertRedirect(route('qa-board.show', $reply->qa_thread_id));
        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('qa_replies', ['id' => $reply->id]);
    }

    public function test_コーチは自身の投稿でない回答を削除できない(): void
    {
        $coach = User::factory()->coach()->create();
        $cert = Certification::factory()->published()->create();
        $thread = QaThread::factory()->for($cert)->create();
        $reply = QaReply::factory()->forThread($thread)->create();

        $response = $this->actingAs($coach)
            ->delete(route('qa-board.replies.destroy', ['thread' => $reply->qa_thread_id, 'reply' => $reply->id]));

        $response->assertForbidden();
        $this->assertDatabaseHas('qa_replies', ['id' => $reply->id]);
    }

    public function test_コーチはアーカイブ済の資格の回答削除時に404エラー(): void
    {
        $coach = User::factory()->coach()->create();
        $cert = Certification::factory()->archived()->create();
        $this->assignCoach($coach, $cert);
        $thread = QaThread::factory()->for($cert)->create();
        $reply = QaReply::factory()->forThread($thread)->for($coach)->create();

        $response = $this->actingAs($reply->user)
            ->delete(route('qa-board.replies.destroy', ['thread' => $reply->qa_thread_id, 'reply' => $reply->id]));

        $response->assertNotFound();
        $this->assertDatabaseHas('qa_threads', ['id' => $thread->id]);
    }

    public function test_元スレッドが異なる場合404エラー(): void
    {
        $student = User::factory()->student()->create();
        $reply = QaReply::factory()->for($student)->create();
        $otherThread = QaThread::factory()->create();

        $response = $this->actingAs($student)
            ->delete(route('qa-board.replies.destroy', ['thread' => $otherThread->id, 'reply' => $reply->id]));

        $response->assertNotFound();
        $this->assertDatabaseHas('qa_replies', ['id' => $reply->id]);
    }
}
