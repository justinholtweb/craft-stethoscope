<div class="flex">
    @if ($options !== [])
        <div
            x-data="{
                setSite(site) {
                    let query = new URLSearchParams(window.location.search)
                    site === '' ? query.delete('site') : query.set('site', site)
                    window.location = `${location.pathname}?${query}`
                }
            }"
        >
            <x-pulse::select
                id="stethoscope-site"
                :label="\CraftCms\Cms\t('Site', category: 'stethoscope')"
                :options="$options"
                x-init="$el.value = @js($current)"
                @change="setSite($event.target.value)"
            />
        </div>
    @endif
</div>
