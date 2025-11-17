<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTicketRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Авторизацию проверяем в контроллере через $this->authorize()
        // так как здесь еще нет доступа к объекту Ticket
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'status_id' => ['required', 'exists:statuses,id'],
            'problem_id' => ['required', 'exists:problems,id'],
            'store_id' => ['nullable', 'exists:stores,id'],
            'cash_id' => ['nullable', 'exists:cashes,id'],
            'ticket_category_id' => ['required', 'exists:ticket_categories,id'],
            'executor_id' => ['nullable', 'exists:users,id'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'title.required' => 'Название тикета обязательно для заполнения.',
            'title.max' => 'Название тикета не может быть длиннее 255 символов.',
            'status_id.required' => 'Необходимо выбрать статус.',
            'status_id.exists' => 'Выбранный статус не существует.',
            'problem_id.required' => 'Необходимо выбрать тип проблемы.',
            'problem_id.exists' => 'Выбранный тип проблемы не существует.',
            'store_id.exists' => 'Выбранный магазин не существует.',
            'cash_id.exists' => 'Выбранная касса не существует.',
            'ticket_category_id.required' => 'Необходимо выбрать категорию.',
            'ticket_category_id.exists' => 'Выбранная категория не существует.',
            'executor_id.exists' => 'Выбранный исполнитель не существует.',
            'description.max' => 'Описание не может быть длиннее 5000 символов.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'title' => 'название',
            'description' => 'описание',
            'status_id' => 'статус',
            'problem_id' => 'тип проблемы',
            'store_id' => 'магазин',
            'cash_id' => 'касса',
            'ticket_category_id' => 'категория',
            'executor_id' => 'исполнитель',
        ];
    }
}
