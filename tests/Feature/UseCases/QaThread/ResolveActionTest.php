<?php

declare(strict_types=1);

namespace Tests\Feature\UseCases\QaThread;

use App\Enums\QaThreadStatus;
use App\Models\QaThread;
use App\UseCases\QaThread\ResolveAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ResolveActionTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_解決済にする(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-01 10:00:00'));

        $thread = QaThread::factory()->unresolved()->create();

        app(ResolveAction::class)($thread);

        $fresh = $thread->fresh();

        $this->assertSame(QaThreadStatus::Resolved, $fresh->status);
        $this->assertSame('2026-09-01 10:00:00', $fresh->resolved_at->format('Y-m-d H:i:s'));
    }
}
