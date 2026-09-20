<?php

namespace Techysavvy\DocToMarkdown\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;
use Techysavvy\DocToMarkdown\Converters\ConverterResolver;
use Techysavvy\DocToMarkdown\Http\Requests\StoreConversionRequest;

class ConvertController
{
    public function store(StoreConversionRequest $request, ConverterResolver $resolver): JsonResponse
    {
        $file = $request->file('file');

        // Parsing is CPU-heavy; bound it so one upload can't pin a worker.
        set_time_limit((int) config('doc-to-markdown.max_execution_seconds'));

        try {
            $markdown = $resolver->resolve($this->extensionOf($file))->convert($file->getRealPath());
        } catch (\Throwable $e) {
            // Users get one generic message, but the cause must stay visible
            // to whoever runs the site.
            report($e);

            return response()->json([
                'message' => "That file couldn't be read. Make sure it's a valid .docx or .pdf.",
            ], 422);
        }

        if (trim($markdown) === '') {
            return response()->json([
                'message' => 'No text could be extracted. A scanned or image-only document has no text layer to convert.',
            ], 422);
        }

        $name = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);

        return response()->json([
            'markdown' => $markdown,
            'filename' => ($name !== '' ? $name : 'converted').'.md',
        ]);
    }

    /**
     * Pick the converter from what the file actually is, not what the client
     * called it — a PDF renamed to .docx must still go to the PDF converter.
     * Some detectors report a .docx as a generic zip; fall back to the name.
     */
    private function extensionOf(UploadedFile $file): string
    {
        $guessed = $file->guessExtension();

        if (in_array($guessed, ['docx', 'pdf'], true)) {
            return $guessed;
        }

        return $file->getClientOriginalExtension();
    }
}
