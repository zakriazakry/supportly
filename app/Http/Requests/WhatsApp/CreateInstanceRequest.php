<?php

namespace App\Http\Requests\WhatsApp;

use Illuminate\Foundation\Http\FormRequest;

class CreateInstanceRequest extends FormRequest
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
            'instance_name' => 'required|string|max:255|unique:whats_app_instances,instance_name',
            'integration' => 'nullable|in:WHATSAPP-BAILEYS,WHATSAPP-BUSINESS',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'instance_name.required' => 'اسم الـ Instance مطلوب',
            'instance_name.unique' => 'اسم الـ Instance موجود مسبقاً',
            'integration.in' => 'نوع التكامل غير صحيح',
        ];
    }
}
