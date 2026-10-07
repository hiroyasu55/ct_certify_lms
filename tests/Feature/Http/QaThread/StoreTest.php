<?php

declare(strict_types=1);

namespace Tests\Feature\Http\QaThread;

use App\Enums\QaThreadStatus;
use App\Models\Certification;
use App\Models\QaThread;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_受講生は質問投稿画面を表示できる(): void
    {
        $student = User::factory()->student()->create();

        $response = $this->actingAs($student)->get(route('qa-board.create'));

        $response->assertOk();
    }

    public function test_コーチは質問投稿画面を表示できない(): void
    {
        $coach = User::factory()->coach()->create();

        $response = $this->actingAs($coach)->get(route('qa-board.create'));

        $response->assertForbidden();
    }

    public function test_管理者は質問投稿画面を表示できない(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get(route('qa-board.create'));

        $response->assertForbidden();
    }

    public function test_受講生は質問を投稿できる(): void
    {
        $student = User::factory()->student()->create();
        $cert = Certification::factory()->published()->create();

        $response = $this->actingAs($student)->post(route('qa-board.store'), [
            'certification_id' => $cert->id,
            'title' => '質問タイトル',
            'body' => '質問本文',
        ]);

        $thread = QaThread::query()->sole();

        // 質問詳細画面にリダイレクト
        $response->assertRedirect(route('qa-board.show', $thread));
        $response->assertSessionHas('success');
        $this->assertSame($student->id, $thread->user_id);
        $this->assertSame(QaThreadStatus::Open, $thread->status, 'デフォルトは未解決');
    }

    public function test_資格・件名・本文の必須バリデーション(): void
    {
        $student = User::factory()->student()->create();

        $response = $this->actingAs($student)->post(route('qa-board.store'), []);

        $response->assertSessionHasErrors(['certification_id', 'title', 'body']);
        $this->assertDatabaseCount('qa_threads', 0);
    }

    public function test_件名・本文の文字数バリデーション(): void
    {
        $student = User::factory()->student()->create();
        $cert = Certification::factory()->published()->create();

        $response = $this->actingAs($student)->post(route('qa-board.store'), [
            'certification_id' => $cert->id,
            'title' => str_repeat('あ', 201),
            'body' => str_repeat('あ', 5001),
        ]);

        $response->assertSessionHasErrors(['title', 'body']);
    }

    public function test_アーカイブ済資格への質問は不可(): void
    {
        $student = User::factory()->student()->create();
        $cert = Certification::factory()->archived()->create();

        $response = $this->actingAs($student)->post(route('qa-board.store'), [
            'certification_id' => $cert->id,
            'title' => '質問タイトル',
            'body' => '質問本文',
        ]);

        $response->assertSessionHasErrors('certification_id');
        $this->assertDatabaseCount('qa_threads', 0);
    }

    public function test_コーチは投稿不可(): void
    {
        $coach = User::factory()->coach()->create();
        $cert = Certification::factory()->published()->create();

        $response = $this->actingAs($coach)->post(route('qa-board.store'), [
            'certification_id' => $cert->id,
            'title' => '質問タイトル',
            'body' => '質問本文',
        ]);

        $response->assertForbidden();
        $this->assertDatabaseCount('qa_threads', 0);
    }

    public function test_管理者は投稿不可(): void
    {
        $admin = User::factory()->coach()->create();
        $cert = Certification::factory()->published()->create();

        $response = $this->actingAs($admin)->post(route('qa-board.store'), [
            'certification_id' => $cert->id,
            'title' => '質問タイトル',
            'body' => '質問本文',
        ]);

        $response->assertForbidden();
        $this->assertDatabaseCount('qa_threads', 0);
    }
}
