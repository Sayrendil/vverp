<?php

namespace App\Http\Requests\Telegram;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Валидация данных для создания тикета через Telegram
 */
class CreateTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'description' => [
                'required',
                'string',
                'min:20',
                'max:4096', // Лимит Telegram для текста сообщения
            ],
            'problem_id' => [
                'required',
                'integer',
                'exists:problems,id',
            ],
            'store_id' => [
                'nullable',
                'integer',
                'exists:stores,id',
            ],
            'ticket_category_id' => [
                'nullable',
                'integer',
                'exists:ticket_categories,id',
            ],
            'attachments' => [
                'nullable',
                'array',
                'max:10', // Максимум 10 файлов
            ],
            'attachments.*.file_id' => [
                'required',
                'string',
                'max:255',
            ],
            'attachments.*.type' => [
                'required',
                'string',
                'in:photo,video,document',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'description.required' => 'Описание обязательно для заполнения',
            'description.min' => 'Описание должно содержать минимум :min символов',
            'description.max' => 'Описание не должно превышать :max символов',
            'problem_id.required' => 'Выберите проблему',
            'problem_id.exists' => 'Выбранная проблема не существует',
            'store_id.exists' => 'Выбранный магазин не существует',
            'ticket_category_id.exists' => 'Выбранная категория не существует',
            'attachments.max' => 'Максимум :max файлов',
        ];
    }
}
