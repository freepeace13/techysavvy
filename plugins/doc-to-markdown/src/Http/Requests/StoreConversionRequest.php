<?php

namespace Techysavvy\DocToMarkdown\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Techysavvy\DocToMarkdown\Support\UploadLimit;

class StoreConversionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'mimes:docx,pdf', 'max:'.UploadLimit::kilobytes()],
        ];
    }

    public function messages(): array
    {
        return [
            // An upload over PHP's post_max_size arrives as an empty body, so
            // "required" is what actually fires for an oversize file.
            'file.required' => 'No file was received. It may be larger than the '.UploadLimit::label().' upload limit.',
            'file.uploaded' => 'The upload failed — the file may be larger than the '.UploadLimit::label().' upload limit.',
            'file.max' => 'That file is larger than the '.UploadLimit::label().' upload limit.',
            'file.mimes' => 'Only .docx and .pdf files can be converted.',
        ];
    }
}
