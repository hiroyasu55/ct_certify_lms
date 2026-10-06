<?php

declare(strict_types=1);

namespace Tests\Feature\Http\QaThread\Admin;

use App\Enums\QaThreadStatus;
use App\Models\Certification;
use App\Models\QaThread;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\ContentTestHelpers;
use Tests\TestCase;

class IndexTest extends TestCase
{
    use ContentTestHelpers, RefreshDatabase;

    public function test_管理者はモデレーション画面からスレッド一覧を閲覧できる(): void
    {
        $admin = User::factory()->admin()->create();
        $thread = QaThread::factory()->create(['title' => '一覧に出る質問']);

        $response = $this->actingAs($admin)->get(route('admin.qa-board.index'));

        $response->assertOk();
        $response->assertSee($thread->title);
    }

    public function test_受講者はモデレーション画面にアクセスできない(): void
    {
        $student = User::factory()->student()->create();

        $response = $this->actingAs($student)->get(route('admin.qa-board.index'));

        $response->assertForbidden();
    }

    public function test_コーチはモデレーション画面にアクセスできない(): void
    {
        $coach = User::factory()->coach()->create();

        $response = $this->actingAs($coach)->get(route('admin.qa-board.index'));

        $response->assertForbidden();
    }
}
