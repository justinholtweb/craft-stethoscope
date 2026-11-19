{{-- One view for every Stethoscope card: optional tiles, then one or more tables. --}}
<x-pulse::card :cols="$cols" :rows="$rows" :class="$class">
    <x-pulse::card-header
        :name="$title"
        x-bind:title="`Time: {{ number_format($time, 0) }}ms; Run at: ${formatDate('{{ $runAt }}')};`"
        :details="$details"
    >
        <x-slot:icon>
            <x-dynamic-component :component="'pulse::icons.'.$icon" />
        </x-slot:icon>
        @if ($sortable)
            <x-slot:actions>
                <x-pulse::select
                    wire:model.live="orderBy"
                    :label="\CraftCms\Cms\t('Sort by', category: 'stethoscope')"
                    :options="$sortOptions"
                    @change="loading = true"
                />
            </x-slot:actions>
        @endif
    </x-pulse::card-header>

    <x-pulse::scroll :expand="$expand" wire:poll.5s="">
        @php($hasRows = collect($data['tables'])->contains(fn ($table) => $table['rows'] !== []))
        @if (! $hasRows)
            <x-pulse::no-results />
        @else
            <div class="flex flex-col gap-6">
                @if (! empty($data['tiles']))
                    <div class="grid grid-cols-3 gap-3 text-center">
                        @foreach ($data['tiles'] as [$value, $label])
                            <div class="flex flex-col justify-center @sm:block">
                                <span class="text-xl uppercase font-bold text-gray-700 dark:text-gray-300 tabular-nums">{{ $value }}</span>
                                <span class="text-xs uppercase font-bold text-gray-500 dark:text-gray-400">{{ $label }}</span>
                            </div>
                        @endforeach
                    </div>
                @endif

                @foreach ($data['tables'] as $table)
                    @if ($table['rows'] !== [])
                        <div>
                            @if (! empty($table['heading']))
                                <h3 class="mb-2 text-xs uppercase font-bold text-gray-500 dark:text-gray-400">{{ $table['heading'] }}</h3>
                            @endif
                            <x-pulse::table>
                                <colgroup>
                                    @foreach ($table['headers'] as $i => $header)
                                        <col width="{{ $i === 0 ? '100%' : '0%' }}" />
                                    @endforeach
                                </colgroup>
                                <x-pulse::thead>
                                    <tr>
                                        @foreach ($table['headers'] as [$label, $numeric])
                                            <x-pulse::th :class="$numeric ? 'text-right whitespace-nowrap' : ''">{{ $label }}</x-pulse::th>
                                        @endforeach
                                    </tr>
                                </x-pulse::thead>
                                <tbody>
                                    @foreach ($table['rows'] as $row)
                                        <tr wire:key="{{ md5($row['key']) }}-spacer" class="h-2 first:h-0"></tr>
                                        <tr wire:key="{{ md5($row['key']) }}-row" data-stethoscope-key="{{ $row['key'] }}">
                                            @foreach ($row['cells'] as $cell)
                                                @if (! empty($cell['numeric']))
                                                    <x-pulse::td numeric @class(['text-gray-700 dark:text-gray-300', 'font-bold' => ! empty($cell['bold'])])>{{ $cell['text'] }}</x-pulse::td>
                                                @elseif (! empty($cell['code']))
                                                    <x-pulse::td class="max-w-[1px]">
                                                        <code class="block text-xs text-gray-900 dark:text-gray-100 truncate" title="{{ $cell['text'] }}">{{ $cell['text'] }}</code>
                                                        @if (! empty($cell['sub']))
                                                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400 truncate">{{ $cell['sub'] }}</p>
                                                        @endif
                                                    </x-pulse::td>
                                                @else
                                                    <x-pulse::td class="text-gray-700 dark:text-gray-300 whitespace-nowrap">{{ $cell['text'] }}</x-pulse::td>
                                                @endif
                                            @endforeach
                                        </tr>
                                    @endforeach
                                </tbody>
                            </x-pulse::table>
                        </div>
                    @endif
                @endforeach

                @if (! empty($data['note']))
                    <p class="text-xs text-gray-400 dark:text-gray-600">{{ $data['note'] }}</p>
                @endif
            </div>
        @endif
    </x-pulse::scroll>
</x-pulse::card>
