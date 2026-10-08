<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Http\Requests\QaReply\StoreRequest;
use App\Http\Requests\QaReply\UpdateRequest;
use App\Models\QaReply;
use App\Models\QaThread;
use App\UseCases\QaReply\DestroyAction;
use App\UseCases\QaReply\StoreAction;
use App\UseCases\QaReply\UpdateAction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * 質問スレッドへの回答 Controller。回答の投稿・編集・削除を提供する。
 *
 * ルートは scopeBindings で {reply} が {thread} の回答であることを保証する。
 */
class QaReplyController extends Controller
{
    public function store(QaThread $thread, StoreRequest $request, StoreAction $action): RedirectResponse
    {
        $reply = $action($request->user(), $thread, $request->validated());

        return redirect()
            ->to(route('qa-board.show', $thread).'#reply-'.$reply->id)
            ->with('success', '回答を投稿しました。');
    }

    public function edit(QaThread $thread, QaReply $reply): View
    {
        $this->authorize('update', $reply);

        return view('qa-thread.reply-edit', [
            'thread' => $thread,
            'reply' => $reply,
        ]);
    }

    public function update(QaThread $thread, QaReply $reply, UpdateRequest $request, UpdateAction $action): RedirectResponse
    {
        $action($reply, $request->validated());

        return redirect()
            ->to(route('qa-board.show', $thread).'#reply-'.$reply->id)
            ->with('success', '回答を更新しました。');
    }

    public function destroy(QaThread $thread, Request $request, QaReply $reply, DestroyAction $action): RedirectResponse
    {
        $this->authorize('delete', $reply);

        $action($reply);

        return redirect()
            ->route(
                $request->user()->role == UserRole::Admin ? 'admin.qa-board.show' : 'qa-board.show',
                $thread
            )
            ->with('success', '回答を削除しました。');
    }
}
