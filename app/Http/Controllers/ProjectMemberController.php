<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;

class ProjectMemberController extends Controller
{
    /**
     * Добавить участника в проект
     */
    public function store(Request $request, Project $project): RedirectResponse
    {
        $this->authorize('manageMembers', $project);

        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'role' => 'required|in:owner,admin,member,viewer',
        ]);

        // Проверить что пользователь еще не является участником
        if ($project->hasMember(User::find($validated['user_id']))) {
            return back()->with('error', 'Пользователь уже является участником проекта.');
        }

        $project->members()->attach($validated['user_id'], ['role' => $validated['role']]);

        return back()->with('success', 'Участник добавлен в проект!');
    }

    /**
     * Удалить участника из проекта
     */
    public function destroy(Project $project, User $user): RedirectResponse
    {
        $this->authorize('manageMembers', $project);

        // Нельзя удалить владельца
        if ($project->owner_id === $user->id) {
            return back()->with('error', 'Невозможно удалить владельца проекта.');
        }

        $project->members()->detach($user->id);

        return back()->with('success', 'Участник удален из проекта.');
    }

    /**
     * Изменить роль участника
     */
    public function updateRole(Request $request, Project $project, User $user): RedirectResponse
    {
        $this->authorize('manageMembers', $project);

        $validated = $request->validate([
            'role' => 'required|in:owner,admin,member,viewer',
        ]);

        // Нельзя изменить роль владельца
        if ($project->owner_id === $user->id) {
            return back()->with('error', 'Невозможно изменить роль владельца проекта.');
        }

        $project->members()->updateExistingPivot($user->id, ['role' => $validated['role']]);

        return back()->with('success', 'Роль участника изменена!');
    }
}
