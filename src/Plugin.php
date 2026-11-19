<?php

declare(strict_types=1);

namespace JustinHoltWeb\Stethoscope;

use CraftCms\Cms\Asset\Events\AfterGenerateTransform;
use CraftCms\Cms\Cp\Data\NavItem;
use CraftCms\Cms\Element\Events\ElementSaved;
use CraftCms\Cms\Element\Events\ElementSaving;
use CraftCms\Cms\Element\Queries\Events\ElementsHydrated;
use CraftCms\Cms\Gql\Events\ExecutedGqlQuery;
use CraftCms\Cms\Gql\Events\GqlQueryExecuting;
use CraftCms\Cms\Gql\Events\GqlSchemaComponentsResolving;
use CraftCms\Cms\Plugin\Plugin as BasePlugin;
use CraftCms\Cms\Search\Events\SearchPerformed;
use CraftCms\Cms\Search\Events\SearchStarting;
use CraftCms\Cms\Twig\Events\TwigCreated;
use CraftCms\Cms\Ui\Controls\IconPicker;
use CraftCms\Cms\Ui\Controls\Lightswitch;
use CraftCms\Cms\Ui\Controls\Number;
use CraftCms\Cms\Ui\Controls\Text;
use CraftCms\Cms\Ui\Nodes\Field;
use CraftCms\Cms\Ui\Nodes\Heading;
use CraftCms\Cms\Ui\Ui;
use CraftCms\Cms\Ui\UiContext;
use CraftCms\Cms\User\Data\Permission;
use CraftCms\Cms\View\Events\PageTemplateRendered;
use CraftCms\Cms\View\Events\PageTemplateRendering;
use CraftCms\Cms\View\Events\TemplateRendered;
use CraftCms\Cms\View\Events\TemplateRendering;
use CraftCms\Cms\View\TemplateCaches as CraftTemplateCaches;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\Compilers\BladeCompiler;
use JustinHoltWeb\Stethoscope\Recorders\Timings;
use JustinHoltWeb\Stethoscope\Support\Privacy;
use Laravel\Pulse\Pulse;
use Livewire\LivewireManager;
use Override;
use Throwable;

use function CraftCms\Cms\t;

/**
 * Stethoscope — Laravel Pulse cards for Craft: element saves and queries,
 * template render times, template caches, queue jobs, GraphQL, search and image
 * transforms, per section and per site, on a dashboard behind a Craft permission.
 *
 * @method Settings getSettings()
 */
class Plugin extends BasePlugin
{
    /** Opens the dashboard (and Pulse's own `/pulse`, whose `viewPulse` gate maps to it). */
    public const string PERMISSION = 'stethoscope:viewDashboard';

    public bool $hasCpSettings = true;

    protected array $events = [
        TwigCreated::class => Listeners\RegisterTwigExtension::class,
        GqlSchemaComponentsResolving::class => Listeners\RegisterGqlComponents::class,
    ];

    protected array $gqlQueries = [
        Gql\SummaryQuery::class,
    ];

    /** Livewire name => card class. */
    public const array CARDS = [
        'stethoscope.element-saves' => Livewire\ElementSaves::class,
        'stethoscope.element-queries' => Livewire\ElementQueries::class,
        'stethoscope.templates' => Livewire\Templates::class,
        'stethoscope.template-caches' => Livewire\TemplateCaches::class,
        'stethoscope.queue-jobs' => Livewire\QueueJobs::class,
        'stethoscope.graphql' => Livewire\Graphql::class,
        'stethoscope.searches' => Livewire\Searches::class,
        'stethoscope.transforms' => Livewire\Transforms::class,
        'stethoscope.timings' => Livewire\Timings::class,
        'stethoscope.site-selector' => Livewire\SiteSelector::class,
    ];

    public function boot(): void
    {
        $this->app->bind(Settings::class, fn (): Settings => $this->getSettings());
        $settings = $this->getSettings();
        $this->hasCpSection = $settings->showInCpNav;

        $this->app->scoped(Stethoscope::class);
        foreach ([Recorders\ElementSaves::class, Recorders\ElementQueries::class, Recorders\Templates::class, Recorders\QueueJobs::class, Recorders\Graphql::class, Recorders\Searches::class, Timings::class] as $recorder) {
            $this->app->scoped($recorder);
        }

        $this->registerAuthorization();
        $this->registerViews();
        $this->registerBlade();

        if (config('pulse.enabled')) {
            $this->registerPrivacy();
            $this->registerRecorders($settings);
        }
    }

    /**
     * Pulse's `viewPulse` gate defaults to “anyone, in the local environment”. A
     * before-callback decides it instead, whatever order providers boot in: a
     * signed-in Craft user with CP access, “Access Stethoscope” (Craft's own per-plugin
     * permission, which also shows the sidebar link) and “View the Stethoscope dashboard”, in every
     * environment. That covers Pulse's own `/pulse` page too.
     */
    private function registerAuthorization(): void
    {
        Gate::before(static function (?Authenticatable $user, string $ability): ?bool {
            if ($ability !== 'viewPulse') {
                return null;
            }

            return $user !== null
                && Gate::forUser($user)->allows('accessCp')
                && Gate::forUser($user)->allows('accessPlugin-stethoscope')
                && Gate::forUser($user)->allows(self::PERMISSION);
        });
    }

    private function registerViews(): void
    {
        $views = $this->getResourcesPath().'/views';
        $this->loadViewsFrom($views, 'stethoscope');

        $this->callAfterResolving('blade.compiler', function (BladeCompiler $blade) use ($views): void {
            $blade->anonymousComponentPath($views.'/components', 'stethoscope');
        });

        $this->callAfterResolving('livewire', function (LivewireManager $livewire): void {
            foreach (self::CARDS as $name => $class) {
                $livewire->component($name, $class);
            }
        });

        if ($this->app->runningInConsole()) {
            $this->publishes([
                $views.'/dashboard.blade.php' => resource_path('views/vendor/stethoscope/dashboard.blade.php'),
            ], 'stethoscope-dashboard');
        }
    }

    /**
     * `@stethoscope('label') … @endstethoscope`, the Blade twin of Twig's
     * `{% stethoscope %}` — both call Timings, so they record identically.
     */
    private function registerBlade(): void
    {
        $this->callAfterResolving('blade.compiler', function (BladeCompiler $blade): void {
            $timings = 'app(\\'.Timings::class.'::class)';
            $blade->directive('stethoscope', fn (string $expression): string => "<?php {$timings}->start(".($expression !== '' ? $expression : "''").'); try { ?>');
            $blade->directive('endstethoscope', fn (): string => "<?php } finally { {$timings}->stop(); } ?>");
        });
    }

    /** Scrubbing for every Pulse entry, and how users appear on Pulse's user cards. */
    private function registerPrivacy(): void
    {
        $pulse = $this->app->make(Pulse::class);
        $privacy = new Privacy($this->getSettings());
        $pulse->filter($privacy);

        try {
            $pulse->user(fn (Authenticatable $user): array => $privacy->user($user));
        } catch (Throwable) {
            // The app bound its own user resolver: it decides what users look like.
        }
    }

    private function registerRecorders(Settings $settings): void
    {
        // Always on (cheap): render stack for unnamed cache blocks and {% stethoscope %} context.
        Event::listen(PageTemplateRendering::class, [Recorders\Templates::class, 'pageRendering']);
        Event::listen(PageTemplateRendered::class, [Recorders\Templates::class, 'pageRendered']);
        Event::listen(TemplateRendering::class, [Recorders\Templates::class, 'rendering']);
        Event::listen(TemplateRendered::class, [Recorders\Templates::class, 'rendered']);

        if ($settings->recordElementSaves) {
            Event::listen(ElementSaving::class, [Recorders\ElementSaves::class, 'saving']);
            Event::listen(ElementSaved::class, [Recorders\ElementSaves::class, 'saved']);
        }

        if ($settings->recordElementQueries) {
            Event::listen(ElementsHydrated::class, [Recorders\ElementQueries::class, 'hydrated']);
            Event::listen(PageTemplateRendered::class, [Recorders\ElementQueries::class, 'pageRendered']);
        }

        if ($settings->recordTemplateCaches && ! $this->app->bound(CraftTemplateCaches::class)) {
            $this->app->scoped(CraftTemplateCaches::class, fn ($app) => $app->make(Recorders\TemplateCaches::class));
        }

        if ($settings->recordQueueJobs) {
            Event::listen(JobProcessing::class, [Recorders\QueueJobs::class, 'processing']);
            Event::listen(JobProcessed::class, [Recorders\QueueJobs::class, 'processed']);
            Event::listen(JobFailed::class, [Recorders\QueueJobs::class, 'failed']);
        }

        if ($settings->recordGraphql) {
            Event::listen(GqlQueryExecuting::class, [Recorders\Graphql::class, 'executing']);
            Event::listen(ExecutedGqlQuery::class, [Recorders\Graphql::class, 'executed']);
        }

        if ($settings->recordSearches) {
            Event::listen(SearchStarting::class, [Recorders\Searches::class, 'starting']);
            Event::listen(SearchPerformed::class, [Recorders\Searches::class, 'performed']);
            Event::listen(QueryExecuted::class, [Recorders\Searches::class, 'queryExecuted']);
            Event::listen(ElementsHydrated::class, [Recorders\Searches::class, 'hydrated']);
        }

        if ($settings->recordTransforms) {
            Event::listen(AfterGenerateTransform::class, [Recorders\Transforms::class, 'generated']);
        }
    }

    #[Override]
    protected function getPermissions(): array
    {
        return [
            new Permission(
                key: self::PERMISSION,
                label: t('View the Stethoscope dashboard', category: 'stethoscope'),
                info: t('Operational data: slow templates, queue jobs, GraphQL operations, cache keys. Also opens Pulse’s own /pulse page.', category: 'stethoscope'),
            ),
        ];
    }

    /** The sidebar link to the dashboard, for people allowed to open it. */
    #[Override]
    public function getCpNavItem(): NavItem|array|null
    {
        if (! Gate::check(self::PERMISSION)) {
            return null;
        }

        $settings = $this->getSettings();

        return new NavItem()
            ->label($settings->navLabel() ?? $this->name)
            ->href('stethoscope')
            ->icon($settings->navIcon() ?? $this->cpNavIconPath());
    }

    #[Override]
    protected static function createSettings(): Settings
    {
        return new Settings;
    }

    #[Override]
    public function settingsUi(UiContext $context = new UiContext): Ui
    {
        $toggle = fn (string $name, string $label, string $instructions): Field => Field::make(t($label, category: 'stethoscope'), Lightswitch::make($name))
            ->instructions(t($instructions, category: 'stethoscope'));

        return Ui::make([
            Heading::make('recording', t('Recording', category: 'stethoscope'))
                ->description(t('Stethoscope records into Laravel Pulse (config/pulse.php decides storage and Pulse’s own recorders). Set PULSE_ENABLED=false to stop all recording.', category: 'stethoscope')),
            Field::make(t('Sample rate', category: 'stethoscope'), Number::make('sampleRate')->min(1)->max(1000))
                ->instructions(t('Record 1 in N requests and queue jobs (1 = every one). Cards scale counts back up and mark them “~”.', category: 'stethoscope')),
            $toggle('recordElementSaves', 'Element saves', 'Per element type, section/volume and site, with save time.'),
            $toggle('recordElementQueries', 'Element queries', 'How many element queries each front-end page render runs, and of which element types/sections.'),
            $toggle('recordTemplates', 'Template renders', 'Front-end render times per template and site (Twig and Blade).'),
            Field::make(t('Slow template threshold (ms)', category: 'stethoscope'), Number::make('slowTemplateMs')->min(0))
                ->instructions(t('Renders at least this slow are also counted as slow.', category: 'stethoscope')),
            $toggle('recordTemplateCaches', 'Template caches', '`{% cache %}` and `@craftCache` hits and misses.'),
            $toggle('recordQueueJobs', 'Queue jobs', 'Runs, failures and durations per job class.'),
            $toggle('recordGraphql', 'GraphQL', 'Queries per schema and operation name (never the query text or variables).'),
            Field::make(t('Slow GraphQL threshold (ms)', category: 'stethoscope'), Number::make('slowGraphqlMs')->min(0))
                ->instructions(t('GraphQL queries at least this slow are also counted as slow.', category: 'stethoscope')),
            $toggle('recordSearches', 'Searches', 'Front-end `.search()` queries: count, duration, and how many found nothing.'),
            $toggle('recordTransforms', 'Image transforms', 'Transforms generated, per transform and volume.'),
            Heading::make('privacy', t('Privacy', category: 'stethoscope'))
                ->description(t('These also apply to Pulse’s own cards.', category: 'stethoscope')),
            $toggle('redactPersonalData', 'Scrub personal data', 'Replace email and IP addresses in every recorded key with [email] / [ip], and drop query strings from recorded URLs.'),
            $toggle('recordSearchTerms', 'Record search terms', 'Visitors type these, so they can contain personal data. Off by default.'),
            $toggle('showUserEmails', 'Show user emails', 'On Pulse’s user cards. Off: full name (or a username that isn’t an email address) and user ID only, and no Gravatar.'),
            Heading::make('access', t('Access', category: 'stethoscope')),
            $toggle('enableGraphql', 'GraphQL summary', 'Offer the `stethoscopeSummary` query to schemas given “View Stethoscope’s operational summary”. Operational data: off by default.'),
            $toggle('showInCpNav', 'Show in the sidebar', 'A sidebar link to the dashboard, for people with “View the Stethoscope dashboard”.'),
            Field::make(t('Sidebar label', category: 'stethoscope'), Text::make('cpNavLabel')->placeholder('Stethoscope'))
                ->instructions(t('What the sidebar link says. Leave blank for “Stethoscope”.', category: 'stethoscope')),
            Field::make(t('Sidebar icon', category: 'stethoscope'), IconPicker::make('cpNavIcon')->sets('*'))
                ->instructions(t('One of Craft’s icons, or one from a custom icon set. Leave blank for Stethoscope’s own icon.', category: 'stethoscope')),
        ]);
    }
}
