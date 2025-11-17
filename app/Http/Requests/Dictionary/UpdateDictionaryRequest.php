<?php

namespace App\Http\Requests\Dictionary;

use App\Services\DictionaryService;
use Illuminate\Foundation\Http\FormRequest;

class UpdateDictionaryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->isAdmin();
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $dictionaryService = app(DictionaryService::class);
        $key = $this->route('dictionary');
        $id = $this->route('id');

        $modelClass = $dictionaryService->getModelClass($key);

        if (!$modelClass) {
            return [];
        }

        return $modelClass::getUpdateValidationRules($id);
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Название обязательно для заполнения',
            'name.string' => 'Название должно быть строкой',
            'name.max' => 'Название не должно превышать :max символов',
            'name.unique' => 'Запись с таким названием уже существует',
        ];
    }
}
