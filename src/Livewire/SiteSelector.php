<?php

declare(strict_types=1);

namespace JustinHoltWeb\Stethoscope\Livewire;

use CraftCms\Cms\Support\Facades\Sites;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Support\Facades\View;
use Livewire\Attributes\Url;
use Livewire\Component;

use function CraftCms\Cms\t;

/** The dashboard's site filter (`?site=`), shown when there's more than one site. */
class SiteSelector extends Component
{
    #[Url]
    public string $site = '';

    public function render(): Renderable
    {
        $options = ['' => t('All sites', category: 'stethoscope')];

        foreach (Sites::getAllSites(true) as $site) {
            $options[$site->handle] = $site->getName();
        }

        return View::make('stethoscope::livewire.site-selector', [
            'options' => count($options) > 2 ? $options : [],
            'current' => array_key_exists($this->site, $options) ? $this->site : '',
        ]);
    }
}
