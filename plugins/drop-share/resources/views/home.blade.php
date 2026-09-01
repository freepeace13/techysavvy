<x-brand::layout title="Drop Share">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="rounded-tag border border-steel-200 bg-surface p-6">
            <h2 class="font-display font-semibold text-ink mb-4">Upload a file</h2>

            @if (session('drop_share_phrase'))
                <x-brand::alert class="mb-4">
                    Your phrase: <strong class="font-mono">{{ session('drop_share_phrase') }}</strong>
                </x-brand::alert>
            @endif

            <form method="POST" action="{{ route('drop-share.upload') }}" enctype="multipart/form-data" class="space-y-4">
                @csrf
                <input type="file" name="file" required class="block w-full text-ink">
                @error('file')
                    <p class="font-mono text-xs text-signal-600">{{ $message }}</p>
                @enderror
                <x-brand::button>Upload</x-brand::button>
            </form>
        </div>

        <div class="rounded-tag border border-steel-200 bg-surface p-6">
            <h2 class="font-display font-semibold text-ink mb-4">Retrieve a file</h2>

            @if (session('drop_share_error'))
                <x-brand::alert variant="error" class="mb-4">
                    {{ session('drop_share_error') }}
                </x-brand::alert>
            @endif

            <form method="POST" action="{{ route('drop-share.download') }}" class="space-y-4">
                @csrf
                <x-brand::input name="phrase" required placeholder="correct-horse-battery-staple" mono />
                <x-brand::button variant="secondary">Download</x-brand::button>
            </form>
        </div>
    </div>
</x-brand::layout>
