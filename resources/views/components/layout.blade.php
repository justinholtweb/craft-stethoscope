@use('Laravel\Pulse\Facades\Pulse')
@props(['cols' => 12, 'fullWidth' => false])
{{-- Pulse's dashboard layout, Craft-flavoured: no external fonts, a way back to the control panel, and a site filter. --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="robots" content="noindex, nofollow">
        <meta name="referrer" content="same-origin">

        <title>{{ \CraftCms\Cms\t('Stethoscope', category: 'stethoscope') }}</title>
        <style>body{font-family:ui-sans-serif,system-ui,-apple-system,"Segoe UI",Roboto,"Helvetica Neue",Arial,sans-serif}</style>

        {!! Pulse::css() !!}
        @livewireStyles

        {!! Pulse::js() !!}
        @livewireScriptConfig
    </head>
    <body class="bg-gray-50 dark:bg-gray-950 font-sans antialiased">
        <div class="min-h-screen">
            <header class="px-5">
                <div class="{{ $fullWidth ? '' : 'container' }} py-3 sm:py-5 mx-auto border-b border-gray-200 dark:border-gray-900">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <a href="{{ \CraftCms\Cms\cp_url() }}" class="text-xs sm:text-sm text-gray-500 dark:text-gray-400 font-medium">← {{ \CraftCms\Cms\t('Control panel', category: 'stethoscope') }}</a>
                            <span class="ml-2 text-lg sm:text-2xl text-gray-700 dark:text-gray-300 font-medium"><b class="font-bold">{{ \CraftCms\Cms\t('Stethoscope', category: 'stethoscope') }}</b></span>
                        </div>
                        <div class="flex flex-wrap items-center gap-3 sm:gap-6">
                            <livewire:stethoscope.site-selector />
                            <livewire:pulse.period-selector />
                            <x-pulse::theme-switcher />
                        </div>
                    </div>
                </div>
            </header>

            <main class="pt-6 px-6 pb-12">
                <div {{ $attributes->merge(['class' => "mx-auto grid default:grid-cols-{$cols} default:gap-6" . ($fullWidth ? '' : ' container')]) }}>
                    {{ $slot }}
                </div>
            </main>
        </div>
    </body>
</html>
