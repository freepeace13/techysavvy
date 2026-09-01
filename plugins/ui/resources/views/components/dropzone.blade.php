@props([
    'name',
    'required' => true,
    'draggingExpr' => 'dragging',
    'fileExpr' => 'file',
    'idleExpr' => "state === 'idle'",
    'selectExpr' => null,
])

@php
    $selectExpr ??= "\$refs.{$name}Input.click()";
@endphp

<div
    @dragover.prevent="{{ $draggingExpr }} = true"
    @dragleave.prevent="{{ $draggingExpr }} = false"
    @drop.prevent="handleDrop($event)"
    @click="({{ $idleExpr }}) && ({{ $selectExpr }})"
    :class="{
        'border-signal-500 bg-signal-50': {{ $draggingExpr }},
        'border-signal-300': {{ $fileExpr }} && ({{ $idleExpr }}),
        'border-steel-300': !{{ $draggingExpr }} && !({{ $fileExpr }} && ({{ $idleExpr }})),
        'cursor-pointer hover:border-signal-300 hover:bg-surface-muted': {{ $idleExpr }},
    }"
    {{ $attributes->merge(['class' => 'flex flex-col items-center gap-3 rounded-brand border-2 border-dashed px-4 py-8 text-center transition-colors duration-150']) }}
>
    <input
        x-ref="{{ $name }}Input"
        type="file"
        name="{{ $name }}"
        @if ($required) required @endif
        class="sr-only"
        @change="handleSelect($event)"
    >

    <template x-if="!{{ $fileExpr }}">
        <div class="flex flex-col items-center gap-3">
            {{ $slot }}
        </div>
    </template>

    <template x-if="{{ $fileExpr }}">
        <div class="flex w-full items-center justify-between gap-3 font-mono text-xs text-ink" @click.stop>
            {{ $selected }}
        </div>
    </template>
</div>
