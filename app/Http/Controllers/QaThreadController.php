<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\CertificationStatus;
use App\Http\Requests\QaThread\StoreRequest;
use App\Http\Requests\QaThread\UpdateRequest;
use App\Models\Certification;
use App\Models\QaThread;
use App\UseCases\QaThread\DestroyAction;
use App\UseCases\QaThread\IndexAction;
use App\UseCases\QaThread\ResolveAction;
use App\UseCases\QaThread\ShowAction;
use App\UseCases\QaThread\StoreAction;
use App\UseCases\QaThread\UnresolveAction;
use App\UseCases\QaThread\UpdateAction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * 受講生 / コーチ共有の質問掲示板 Controller。質問スレッドの CRUD を提供する。
 */
class QaThreadController extends Controller
{
    public function index(Request $request, IndexAction $action): View
    {
        $this->authorize('viewAny', QaThread::class);

        $filters = $request->only(['certification_id', 'status', 'keyword']);

        return view('qa-thread.index', [
            'threads' => $action($request->user(), $filters),
            'filters' => $filters,
            'certifications' => Certification::query()->published()->get(),
            'publishedStatus' => CertificationStatus::Published,
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', QaThread::class);

        return view('qa-thread.create', [
            'certifications' => Certification::query()->published()->get(),
        ]);
    }

    public function store(StoreRequest $request, StoreAction $action): RedirectResponse
    {
        $thread = $action($request->user(), $request->validated());

        return redirect()
            ->route('qa-board.show', $thread)
            ->with('success', '質問を投稿しました。');
    }

    public function show(QaThread $thread, ShowAction $action): View
    {
        $this->authorize('view', $thread);

        return view('qa-thread.show', [
            'thread' => $action($thread),
        ]);
    }

    public function edit(QaThread $thread): View
    {
        $this->authorize('update', $thread);

        return view('qa-thread.edit', [
            'thread' => $thread->load('certification'),
        ]);
    }

    public function update(QaThread $thread, UpdateRequest $request, UpdateAction $action): RedirectResponse
    {
        $action($thread, $request->validated());

        return redirect()
            ->route('qa-board.show', $thread)
            ->with('success', '質問を更新しました。');
    }

    public function destroy(QaThread $thread, DestroyAction $action): RedirectResponse
    {
        $this->authorize('delete', $thread);

        if ($thread->replies->count() > 0) {
            return redirect()
                ->route('qa-board.show', $thread)
                ->with('error', '回答が付いているスレッドは削除できません。');
        }

        $action($thread);

        return redirect()
            ->route('qa-board.index')
            ->with('success', '質問を削除しました。');
    }

    public function resolve(QaThread $thread, ResolveAction $action): RedirectResponse
    {
        $this->authorize('resolve', $thread);

        $action($thread);

        return redirect()
            ->route('qa-board.show', $thread)
            ->with('success', '質問を解決済にマークしました。');
    }

    public function unresolve(QaThread $thread, UnresolveAction $action): RedirectResponse
    {
        $this->authorize('unresolve', $thread);

        $action($thread);

        return redirect()
            ->route('qa-board.show', $thread)
            ->with('success', '質問を未解決に戻しました。');
    }
}
