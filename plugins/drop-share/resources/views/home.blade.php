<x-brand::layout title="Drop Share">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="rounded-brand border border-brand-100 bg-surface p-6">
            <h2 class="text-lg font-semibold text-ink mb-4">Upload a file</h2>

            @if (session('drop_share_phrase'))
                <p class="mb-4 rounded-brand border border-brand-100 bg-brand-50 p-3 text-ink">
                    Your phrase: <strong>{{ session('drop_share_phrase') }}</strong>
                </p>
            @endif

            <form method="POST" action="{{ route('drop-share.upload') }}" enctype="multipart/form-data" class="space-y-4">
                @csrf
                <input type="file" name="file" required class="block w-full text-ink">
                @error('file')
                    <p class="text-sm text-red-600">{{ $message }}</p>
                @enderror
                <button type="submit" class="rounded-brand bg-brand-500 px-4 py-2 text-white">Upload</button>
            </form>
        </div>

        <div class="rounded-brand border border-brand-100 bg-surface p-6">
            <h2 class="text-lg font-semibold text-ink mb-4">Retrieve a file</h2>

            @if (session('drop_share_error'))
                <p class="mb-4 rounded-brand border border-brand-200 bg-brand-50 p-3 text-ink">
                    {{ session('drop_share_error') }}
                </p>
            @endif

            <form method="POST" action="{{ route('drop-share.download') }}" class="space-y-4">
                @csrf
                <input type="text" name="phrase" required placeholder="correct-horse-battery-staple" class="block w-full rounded-brand border border-brand-100 p-2 text-ink">
                @error('phrase')
                    <p class="text-sm text-red-600">{{ $message }}</p>
                @enderror
                <button type="submit" class="rounded-brand bg-brand-500 px-4 py-2 text-white">Download</button>
            </form>
        </div>
    </div>
</x-brand::layout>
