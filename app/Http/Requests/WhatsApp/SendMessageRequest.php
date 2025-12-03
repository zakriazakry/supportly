<?php

namespace App\Http\Requests\WhatsApp;

use Illuminate\Foundation\Http\FormRequest;

class SendMessageRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'number' => 'required|string',
            'text' => 'required|string|max:4096',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'number.required' => 'رقم الهاتف مطلوب',
            'text.required' => 'نص الرسالة مطلوب',
            'text.max' => 'نص الرسالة طويل جداً (الحد الأقصى 4096 حرف)',
        ];
    }
}
