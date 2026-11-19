{{--
    Stethoscope's dashboard: Craft's cards first, then Pulse's own.
    Publish it to customise: php artisan vendor:publish --tag=stethoscope-dashboard
    (copies to resources/views/vendor/stethoscope/dashboard.blade.php, which then wins).
--}}
<x-stethoscope::layout>
    <livewire:stethoscope.element-saves cols="6" />
    <livewire:stethoscope.templates cols="6" />
    <livewire:stethoscope.element-queries cols="6" />
    <livewire:stethoscope.template-caches cols="6" />
    <livewire:stethoscope.queue-jobs cols="6" />
    <livewire:stethoscope.graphql cols="6" />
    <livewire:stethoscope.searches cols="4" />
    <livewire:stethoscope.transforms cols="4" />
    <livewire:stethoscope.timings cols="4" />

    <livewire:pulse.slow-queries cols="8" />
    <livewire:pulse.queues cols="4" />
    <livewire:pulse.exceptions cols="6" />
    <livewire:pulse.slow-requests cols="6" />
    <livewire:pulse.cache cols="6" />
    <livewire:pulse.slow-outgoing-requests cols="6" />
</x-stethoscope::layout>
