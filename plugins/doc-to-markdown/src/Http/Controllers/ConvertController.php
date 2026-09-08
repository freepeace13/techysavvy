<?php

namespace Techysavvy\DocToMarkdown\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Techysavvy\DocToMarkdown\Converters\ConverterResolver;
use Techysavvy\DocToMarkdown\Http\Requests\StoreConversionRequest;

class ConvertController
{
    public function store(StoreConversionRequest $request, ConverterResolver $resolver): JsonResponse
    {
        $file = $request->file('file');
        $extension = $file->getClientOriginalExtension();

        try {
            $markdown = $resolver->resolve($extension)->convert($file->getRealPath());
        } catch (\Throwable) {
            return response()->json([
                'message' => "That file couldn't be read. Make sure it's a valid .docx or .pdf.",
            ], 422);
        }

        return response()->json([
            'markdown' => $markdown,
            'filename' => pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME).'.md',
        ]);
    }
}
