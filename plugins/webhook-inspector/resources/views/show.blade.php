@pluginAssets('webhook-inspector')

<x-ui::layout title="Webhook Inspector">
    <x-ui::page-header eyebrow="Debug bench" title="Webhook Inspector">
        Send anything to the capture URL below. Requests show up here as they arrive.
    </x-ui::page-header>

    <div
        x-data="webhookInspector($el.dataset)"
        data-poll-url="{{ route('webhook-inspector.bins.requests', $bin->view_token) }}"
        data-capture-url="{{ $captureUrl }}"
        data-expires-at="{{ $bin->expires_at->toIso8601String() }}"
        data-interval-ms="{{ config('webhook-inspector.poll_interval_ms') }}"
        data-max-requests="{{ config('webhook-inspector.max_requests_per_bin') }}"
        data-max-body-kb="{{ config('webhook-inspector.max_body_kb') }}"
        data-expired="{{ $expired ? 'true' : 'false' }}"
        class="flex flex-col gap-6"
    >
        <x-ui::panel eyebrow="Your bin" title="Capture URL">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-end">
                <div class="flex-1">
                    <x-ui::input name="capture_url" mono readonly value="{{ $captureUrl }}" @focus="$el.select()" />
                </div>
                <x-ui::button type="button" variant="secondary" @click="copyCaptureUrl()">
                    <span x-text="copiedLabel === 'url' ? 'Copied' : 'Copy URL'">Copy URL</span>
                </x-ui::button>
            </div>

            <div class="mt-4 flex flex-wrap items-center justify-between gap-3 font-mono text-xs text-ink-muted">
                <span>
                    <span x-text="requests.length">0</span> request(s) ·
                    <span x-text="expiryText"></span>
                </span>

                <form
                    method="POST"
                    action="{{ route('webhook-inspector.bins.destroy', $bin->view_token) }}"
                    @submit="if (! confirm('Delete this bin and all captured requests?')) $event.preventDefault()"
                >
                    @csrf
                    @method('DELETE')
                    <x-ui::button variant="secondary">Delete bin</x-ui::button>
                </form>
            </div>
        </x-ui::panel>

        <x-ui::alert variant="error" x-show="expired" :style="$expired ? null : 'display: none'">
            This bin has expired, so it no longer accepts or shows requests.
            <a href="{{ route('webhook-inspector.home') }}" class="underline">Create a new bin</a>.
        </x-ui::alert>

        <div x-show="! expired" class="grid grid-cols-1 gap-6 md:grid-cols-[18rem_1fr] md:items-start">
            <x-ui::panel eyebrow="Inbox" title="Requests">
                <p x-show="requests.length === 0" class="text-sm text-ink-muted">
                    Waiting for requests&hellip; send one to the capture URL.
                </p>
                <pre x-show="requests.length === 0" class="mt-3 overflow-x-auto rounded-brand bg-surface-muted p-3 font-mono text-xs text-ink">curl -X POST -H 'Content-Type: application/json' -d '{"hello":"world"}' {{ $captureUrl }}</pre>

                <ul class="flex flex-col gap-1">
                    <template x-for="req in requests" :key="req.id">
                        <li>
                            <button
                                type="button"
                                @click="select(req.id)"
                                :class="selectedId === req.id ? 'border-signal-300 bg-signal-50' : 'border-transparent hover:bg-surface-muted'"
                                class="flex w-full flex-col gap-1 rounded-brand border px-3 py-2 text-left"
                            >
                                <span class="flex items-center gap-2">
                                    <span class="rounded-tag bg-steel-200 px-1.5 py-0.5 font-mono text-[11px] font-semibold text-ink" x-text="req.method"></span>
                                    <span class="truncate font-mono text-xs text-ink" x-text="req.path + (req.query ? '?' + req.query : '')"></span>
                                </span>
                                <span class="font-mono text-[11px] text-ink-muted">
                                    <span x-text="rowTime(req)"></span> · <span x-text="rowSize(req)"></span>
                                </span>
                            </button>
                        </li>
                    </template>
                </ul>
            </x-ui::panel>

            <x-ui::panel eyebrow="Detail" title="Request">
                <p x-show="! selected" class="text-sm text-ink-muted">Select a request to see its details.</p>

                <template x-if="selected">
                    <div class="flex flex-col gap-4">
                        <p class="break-all font-mono text-sm text-ink">
                            <span class="font-semibold" x-text="selected.method"></span>
                            <span x-text="selected.path + (selected.query ? '?' + selected.query : '')"></span>
                        </p>
                        <p class="font-mono text-[11px] text-ink-muted">
                            from <span x-text="selected.ip"></span> ·
                            <span x-text="selected.content_type || 'no content type'"></span> ·
                            <span x-text="rowSize(selected)"></span>
                        </p>

                        <x-ui::tabs
                            :options="[
                                ['value' => 'body', 'label' => 'Body'],
                                ['value' => 'headers', 'label' => 'Headers'],
                                ['value' => 'query', 'label' => 'Query'],
                            ]"
                            model="tab"
                            class="self-start"
                        />

                        <div x-show="tab === 'body'" class="flex flex-col gap-3">
                            <x-ui::alert x-show="selectedNotice"><span x-text="selectedNotice"></span></x-ui::alert>
                            <pre class="max-h-[32rem] overflow-auto rounded-brand bg-surface-muted p-3 font-mono text-xs text-ink" x-text="selectedBody.text"></pre>
                            <x-ui::button type="button" variant="secondary" class="self-start" @click="copyBody()" x-bind:disabled="! selectedBody.copyable">
                                <span x-text="copiedLabel === 'body' ? 'Copied' : 'Copy body'">Copy body</span>
                            </x-ui::button>
                        </div>

                        <div x-show="tab === 'headers'">
                            <table class="w-full text-left font-mono text-xs">
                                <tbody>
                                    <template x-for="([name, value], i) in selectedHeaders" :key="i">
                                        <tr class="border-b border-dashed border-steel-200 align-top">
                                            <td class="py-1.5 pr-4 text-ink-muted" x-text="name"></td>
                                            <td class="break-all py-1.5 text-ink" x-text="value"></td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>

                        <div x-show="tab === 'query'">
                            <p x-show="selectedQuery.length === 0" class="text-sm text-ink-muted">No query parameters.</p>
                            <table x-show="selectedQuery.length" class="w-full text-left font-mono text-xs">
                                <tbody>
                                    <template x-for="([name, value], i) in selectedQuery" :key="i">
                                        <tr class="border-b border-dashed border-steel-200 align-top">
                                            <td class="py-1.5 pr-4 text-ink-muted" x-text="name"></td>
                                            <td class="break-all py-1.5 text-ink" x-text="value"></td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </template>
            </x-ui::panel>
        </div>
    </div>
</x-ui::layout>
