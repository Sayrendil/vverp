<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\TaskActivity;
use App\Models\TaskAttachment;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;

class TaskAttachmentController extends Controller
{
    /**
     * Загрузить вложение к задаче
     */
    public function store(Request $request, Task $task): RedirectResponse
    {
        $this->authorize('attach', $task);

        $request->validate([
            'file' => 'required|file|max:10240', // Max 10MB
        ]);

        $file = $request->file('file');

        // Сохранить файл
        $path = $file->store('task_attachments', 'public');

        TaskAttachment::create([
            'task_id' => $task->id,
            'user_id' => auth()->id(),
            'file_name' => $file->getClientOriginalName(),
            'file_path' => $path,
            'file_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
        ]);

        // Логировать добавление вложения
        TaskActivity::create([
            'task_id' => $task->id,
            'user_id' => auth()->id(),
            'action' => 'attached',
            'new_value' => json_encode($file->getClientOriginalName()),
        ]);

        return back()->with('success', 'Файл загружен!');
    }

    /**
     * Удалить вложение
     */
    public function destroy(TaskAttachment $attachment): RedirectResponse
    {
        $task = $attachment->task;

        $this->authorize('deleteAttachment', [$task, $attachment]);

        $attachment->delete(); // Файл удалится автоматически через boot метод модели

        return back()->with('success', 'Вложение удалено.');
    }
}
