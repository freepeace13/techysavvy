<?php

namespace Techysavvy\Core\Http;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Techysavvy\Core\Assets\AssetRegistry;

class AssetController
{
    public function __invoke(Request $request, AssetRegistry $registry, string $bundle, string $file): BinaryFileResponse
    {
        $declared = $registry->get($bundle);

        // Only names the plugin declared are ever resolved to a path, so there is
        // no way to address any other file.
        abort_unless($declared !== null && $declared->has($file), 404);

        $path = $declared->path($file);
        abort_unless(is_file($path), 404);

        $response = response()->file($path, [
            'Content-Type' => str_ends_with($file, '.css') ? 'text/css; charset=utf-8' : 'text/javascript; charset=utf-8',
        ]);

        $response->headers->set('Cache-Control', 'public, max-age=31536000, immutable');
        $response->setEtag(hash_file('xxh128', $path));
        $response->isNotModified($request);

        return $response;
    }
}
