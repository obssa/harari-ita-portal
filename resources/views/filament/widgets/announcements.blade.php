<x-filament-widgets::widget>
    @php $announcements = $this->announcements(); @endphp
    @if ($announcements->isNotEmpty())
        <x-filament::section :heading="$this::title()" icon="heroicon-o-megaphone">
            <ul class="hinta-stack">
                @foreach ($announcements as $announcement)
                    <li class="hinta-note">
                        <p class="hinta-note-head">
                            <x-filament::badge :color="$announcement->tone === 'warning' ? 'warning' : ($announcement->tone === 'success' ? 'success' : 'info')">{{ __("dashboard.tone.{$announcement->tone}") }}</x-filament::badge>
                            {{ $announcement->title }}
                        </p>
                        @if (filled($announcement->body))
                            <p class="hinta-note-body">{{ $announcement->body }}</p>
                        @endif
                    </li>
                @endforeach
            </ul>
        </x-filament::section>
    @endif
</x-filament-widgets::widget>
