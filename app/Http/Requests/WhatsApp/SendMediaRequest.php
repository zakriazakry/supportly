<?php

namespace App\Http\Requests\WhatsApp;

use Illuminate\Foundation\Http\FormRequest;

class SendMediaRequest extends FormRequest
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
            'media_type' => 'required|in:image,video,document,audio',
            'media_url' => 'required|url',
            'caption' => 'nullable|string|max:1024',
            'file_name' => 'nullable|string|max:255',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'number.required' => 'رقم الهاتف مطلوب',
            'media_type.required' => 'نوع الوسائط مطلوب',
            'media_type.in' => 'نوع الوسائط غير صحيح',
            'media_url.required' => 'رابط الوسائط مطلوب',
            'media_url.url' => 'رابط الوسائط غير صحيح',
            'caption.max' => 'التعليق طويل جداً (الحد الأقصى 1024 حرف)',
        ];
    }
}
