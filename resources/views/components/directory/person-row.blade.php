@props(['leader', 'translation', 'role' => null])
{{-- ADR-0012: a person in a sidebar or a list: round portrait, name, position; the whole row is the link. --}}
<article {{ $attributes->class('reveal group relative flex items-center gap-4 rounded-card border border-line bg-surface p-3 pe-4 transition-colors duration-150 hover:border-cyan-500') }} lang="{{ $translation->locale }}">
    @if ($leader->portrait)
        <x-picture :media="$leader->portrait" preset="square" sizes="4rem" class="size-14 shrink-0 rounded-full object-cover ring-2 ring-surface-muted group-hover:ring-cyan-500" />
    @else
        <span aria-hidden="true" class="flex size-14 shrink-0 items-center justify-center rounded-full bg-night-900 font-display text-base font-bold text-night-ink ring-2 ring-surface-muted group-hover:ring-cyan-500">{{ \App\Support\Initials::of($translation->name) }}</span>
    @endif
    <div class="min-w-0">
        @if ($role)
            <p class="font-mono text-[11px] tracking-wider text-cyan-700 uppercase dark:text-cyan-300">{{ $role }}</p>
        @endif
        <h3 class="truncate font-display font-semibold leading-snug tracking-tight">
            <a href="{{ route('leaders.show', ['locale' => $translation->locale, 'slug' => $translation->slug]) }}" class="underline-offset-4 after:absolute after:inset-0 group-hover:underline">{{ $translation->name }}</a>
        </h3>
        @if ($translation->position)
            <p class="line-clamp-2 text-sm text-ink-muted">{{ $translation->position }}</p>
        @endif
    </div>
</article>
