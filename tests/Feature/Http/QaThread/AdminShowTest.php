<?php

declare(strict_types=1);

namespace Tests\Feature\Http\QaThread;

use App\Models\Certification;
use App\Models\QaThread;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\ContentTestHelpers;
use Tests\TestCase;

class AdminShowTest extends TestCase
{
    use ContentTestHelpers, RefreshDatabase;

    /**
     * 資格にコーチをアサインする
     */
    public function test_管理者は公開中資格の質問を閲覧できる(): void
    {
        $admin = User::factory()->admin()->create();
        $cert = Certification::factory()->published()->create();
        $thread = QaThread::factory()->for($cert)->create();

        $response = $this->actingAs($admin)->get(route('admin.qa-board.show', $thread));

        $response->assertOk();
        $response->assertSee($thread->title);
    }

    public function test_管理者はアーカイブされた資格の質問を閲覧できる(): void
    {
        $admin = User::factory()->admin()->create();
        $cert = Certification::factory()->archived()->create();
        $thread = QaThread::factory()->for($cert)->create();

        $response = $this->actingAs($admin)->get(route('admin.qa-board.show', $thread));

        $response->assertOk();
        $response->assertSee($thread->title);
    }

    public function test_受講者はモデレーション画面の質問にアクセスできない(): void
    {
        $student = User::factory()->student()->create();
        $thread = QaThread::factory()->create();

        $response = $this->actingAs($student)->get(route('admin.qa-board.show', $thread));

        $response->assertForbidden();
    }

    public function test_コーチはモデレーション画面の質問にアクセスできない(): void
    {
        $coach = User::factory()->student()->create();
        $thread = QaThread::factory()->create();

        $response = $this->actingAs($coach)->get(route('admin.qa-board.show', $thread));

        $response->assertForbidden();
    }
}
