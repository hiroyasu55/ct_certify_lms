<?php

declare(strict_types=1);

namespace Tests\Feature\UseCases\QaThread;

use App\Enums\QaThreadStatus;
use App\Models\QaThread;
use App\UseCases\QaThread\UnresolveAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UnresolveActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_未解決にする(): void
    {
        $thread = QaThread::factory()->resolved()->create();

        app(UnresolveAction::class)($thread);

        $fresh = $thread->fresh();

        $this->assertSame(QaThreadStatus::Open, $fresh->status);
        $this->assertNull($fresh->resolved_at);
    }
}
