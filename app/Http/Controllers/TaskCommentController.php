<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\TaskActivity;
use App\Models\TaskComment;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;

class TaskCommentController extends Controller
{
    /**
     * Добавить комментарий к задаче
     */
    public function store(Request $request, Task $task): RedirectResponse
    {
        $this->authorize('comment', $task);

        $validated = $request->validate([
            'content' => 'required|string',
        ]);

        $comment = TaskComment::create([
            'task_id' => $task->id,
            'user_id' => auth()->id(),
            'content' => $validated['content'],
        ]);

        // Логировать комментирование
        TaskActivity::create([
            'task_id' => $task->id,
            'user_id' => auth()->id(),
            'action' => 'commented',
        ]);

        return back()->with('success', 'Комментарий добавлен!');
    }

    /**
     * Удалить комментарий
     */
    public function destroy(TaskComment $comment): RedirectResponse
    {
        $task = $comment->task;

        $this->authorize('deleteComment', [$task, $comment]);

        $comment->delete();

        return back()->with('success', 'Комментарий удален.');
    }
}
