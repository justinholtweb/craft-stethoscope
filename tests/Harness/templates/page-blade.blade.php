{{-- Stethoscope harness page (Blade): the same queries, cache block, timing and search as page.twig. --}}
@php($entries = \CraftCms\Cms\Entry\Elements\Entry::find()->section('stethoscopeLog')->orderBy('id asc')->limit(5)->all())
@stethoscope('st-loop')
<ol>@foreach ($entries as $e)@php($again = \CraftCms\Cms\Entry\Elements\Entry::find()->id($e->id)->one())<li data-id="{{ $again->id }}">{{ $again->title }}</li>@endforeach</ol>
@endstethoscope
@craftCache(key: 'st-nav-'.'42')<nav>cached</nav>@endCraftCache
@if (request('q'))<p id="found">{{ count(\CraftCms\Cms\Entry\Elements\Entry::find()->section('stethoscopeLog')->search(request('q'))->all()) }}</p>@endif
@if (request('qs'))<p id="scored">{{ count(\CraftCms\Cms\Entry\Elements\Entry::find()->section('stethoscopeLog')->search(request('qs'))->orderBy('score desc')->all()) }}</p>@endif
