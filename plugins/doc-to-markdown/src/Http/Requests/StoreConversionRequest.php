<?php

namespace Techysavvy\DocToMarkdown\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreConversionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'mimes:docx,pdf', 'max:'.config('doc-to-markdown.max_upload_kb')],
        ];
    }
}
