<?php
namespace osim\craft\tenon\elements;

use Craft;
use craft\base\Element;
use craft\db\Query;
use craft\elements\actions\Edit as EditAction;
use craft\elements\actions\Restore as RestoreAction;
use craft\elements\actions\SetStatus as CraftSetStatusAction;
use craft\elements\User;
use craft\helpers\Html;
use craft\helpers\UrlHelper;
use osim\craft\tenon\Plugin;
use osim\craft\tenon\elements\actions\SetStatus as SetStatusAction;
use osim\craft\tenon\elements\actions\View as ViewAction;
use osim\craft\tenon\elements\db\IssueQuery;
use osim\craft\tenon\models\Project as ProjectModel;
use osim\craft\tenon\records\Issue as IssueRecord;
use osim\craft\tenon\records\Page as PageRecord;
use osim\craft\tenon\records\Project as ProjectRecord;
use yii\base\InvalidConfigException;

class Issue extends Element
{
    const STATUS_RESOLVED = 'resolved';
    const STATUS_UNRESOLVED = 'unresolved';

    public ?int $projectId = null;

    public ?int $pageId = null;
    public ?string $pageTitle = null;
    public ?string $pageUrl = null;

    public ?int $viewportId = null;
    public ?string $viewportName = null;
    public ?int $viewportWidth = null;
    public ?int $viewportHeight = null;

    public ?int $certainty = null;
    public ?int $priority = null;
    public ?int $errorGroupId = null;
    public ?string $errorGroupTitle = null;
    public ?int $errorId = null;
    public ?string $errorTitle = null;
    public ?string $errorDescription = null;
    public ?string $errorSnippet = null;
    public ?string $errorXpath = null;
    public ?bool $levelA = null;
    public ?bool $levelAa = null;
    public ?bool $levelAaa = null;
    public ?bool $resolved = null;

    public function init(): void
    {
        parent::init();

        $this->title = $this->pageTitle;
        $this->uri = $this->pageUrl;
        if ($this->uri) {
            $this->uri = UrlHelper::urlWithParams($this->uri, [
                'tenon-xpath' => $this->errorXpath
            ]);
        }
        $this->setUiLabel(Plugin::t('Details'));
    }

    protected function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [
            [
                'pageId', 'viewportId', 'certainty', 'priority',
                'errorGroupId', 'errorGroupTitle', 'errorId', 'errorTitle',
                'errorDescription', 'errorSnippet', 'errorXpath',
                'levelA', 'levelAa', 'levelAaa', 'resolved'
            ],
            'required'
        ];
        $rules[] = [['certainty', 'priority'], 'number', 'integerOnly' => true, 'min' => 0, 'max' => 100];
        $rules[] = [['errorGroupId', 'errorId'], 'number', 'integerOnly' => true, 'min' => 0];
        $rules[] = [['errorGroupTitle', 'errorTitle'], 'string', 'max' => 250];
        $rules[] = [['levelA', 'levelAa', 'levelAaa', 'resolved'], 'boolean'];

        return $rules;
    }

    public function attributeLabels(): array
    {
        return [
            'pageId' => Plugin::t('Page'),
            'viewportId' => Plugin::t('Viewport'),
            'certainty' => Plugin::t('Certainty'),
            'priority' => Plugin::t('Priority'),
            'errorGroupId' => Plugin::t('Best Practice ID'),
            'errorGroupTitle' => Plugin::t('Best Practice Title'),
            'errorId' => Plugin::t('Test ID'),
            'errorTitle' => Plugin::t('Test Title'),
            'errorDescription' => Plugin::t('Error Description'),
            'errorSnippet' => Plugin::t('Error Snippet'),
            'errorXpath' => Plugin::t('Error XPath'),
            'levelA' => Plugin::t('WCAG A'),
            'levelAa' => Plugin::t('WCAG AA'),
            'levelAaa' => Plugin::t('WCAG AAA'),
            'resolved' => Plugin::t('Resolved'),
        ];
    }

    public static function displayName(): string
    {
        return 'Issue';
    }

    public static function pluralDisplayName(): string
    {
        return 'Issues';
    }

    public static function hasTitles(): bool
    {
        return false;
    }

    public static function hasContent(): bool
    {
        return false;
    }

    public static function hasUris(): bool
    {
        return false;
    }

    public static function isLocalized(): bool
    {
        return true;
    }

    public static function hasStatuses(): bool
    {
        return true;
    }

    public static function statuses(): array
    {
        return [
            'resolved' => ['label' => Plugin::t('Resolved'), 'color' => 'green'],
            'unresolved' => ['label' => Plugin::t('Unresolved'), 'color' => 'red'],
        ];
    }

    public static function find(): IssueQuery
    {
        return new IssueQuery(static::class);
    }

    public function canView(User $user): bool
    {
        return true;
    }
    public function canSave(User $user): bool
    {
        return false;
    }

    public function getFieldLayout(): ?\craft\models\FieldLayout
    {
        // Only show preview on desktop
        $preview = '';
        if (!Craft::$app->getRequest()->isMobileBrowser()) {
            $preview = '<div class="osim-tenon-preview">' . "\n" .
                '<div class="osim-tenon-frame" style="width: ' . $this->viewportWidth . 'px; height: ' . $this->viewportHeight . 'px;">' . "\n" .
                    '<iframe src="' . Html::encode($this->uri) . '"/>' . "\n" .
                '</div>' . "\n" .
            '</div>' . "\n";
        }

        $levels = [];
        if ($this->levelA) {
            $levels[] = 'A';
        }

        if ($this->levelAa) {
            $levels[] = 'AA';
        }

        if ($this->levelAaa) {
            $levels[] = 'AAA';
        }

        if (count($levels) == 1) {
            $levelsLabel = 'Level';
        } else {
            $levelsLabel = 'Levels';
        }

        $levels = implode(', ', $levels);

        $layoutElements = [
            new \craft\fieldlayoutelements\Html(
                '<h2>' . Plugin::t('Issue') . '</h2>' . "\n" .
                '<p><b>' . Plugin::t('Test') . ': (' . $this->errorId . ')</b><br>' . Html::encode($this->errorTitle) . '</p>' . "\n" .
                '<p><b>' . Plugin::t('Best Practice') . ': (' . $this->errorGroupId . ')</b><br>' . Html::encode($this->errorGroupTitle) . '</p>' . "\n" .
                '<p><b>' . Plugin::t($levelsLabel) . ': </b>' . $levels . '</p>' . "\n" .
                '<p><b>' . Plugin::t('Certainty') . ': </b>' . $this->certainty . '</p>' . "\n" .
                '<p><b>' . Plugin::t('Priority') . ': </b>' . $this->priority . '</p>' . "\n" .
                '<p>' . "\n" .
                    '<a class="go" href="' . Html::encode($this->uri) . '" rel="noopener" target="_blank">' . "\n" .
                        '<span dir="ltr">View Issue</span>' . "\n" .
                    '</a>' . "\n" .
                '</p>' . "\n" .

                '<h2>' . Plugin::t('Description') . '</h2>' . "\n" .
                '<p>' . Html::encode($this->errorDescription) . '</p>' . "\n" .

                '<h2>' . Plugin::t('Snippet') . '</h2>' . "\n" .
                '<p>' . Html::encode($this->errorSnippet) . '</p>' . "\n" .

                $preview
            )
        ];

        $fieldLayout = new \craft\models\FieldLayout();

        $tab = new \craft\models\FieldLayoutTab();
        $tab->name = 'Content';
        $tab->setLayout($fieldLayout);
        $tab->setElements($layoutElements);

        $fieldLayout->setTabs([ $tab ]);

        return $fieldLayout;
    }

    public function canDelete(User $user): bool
    {
        return Craft::$app->getUser()->checkPermission(Plugin::PERMISSION_DELETE_ISSUES);
    }
    protected static function defineActions(string $source): array
    {
        $actions = [];
        $elementsService = Craft::$app->getElements();

        $actions[] = $elementsService->createAction([
            'type' => ViewAction::class,
            'label' => Craft::t('app', 'View {type}', [
                'type' => static::lowerDisplayName(),
            ]),
        ]);

        if (Craft::$app->getUser()->checkPermission(Plugin::PERMISSION_DELETE_ISSUES)) {
            $actions[] = $elementsService->createAction([
                'type' => RestoreAction::class,
                'successMessage' => Craft::t('app', 'Entries restored.'),
                'partialSuccessMessage' => Craft::t('app', 'Some entries restored.'),
                'failMessage' => Craft::t('app', 'Entries not restored.'),
            ]);
        }

        if (Craft::$app->getUser()->checkPermission(Plugin::PERMISSION_RESOLVE_ISSUES)) {
            $actions[] = $elementsService->createAction([
                'type' => SetStatusAction::class,
            ]);
        }

        return $actions;
    }
    public static function actions(string $source): array
    {
        $actions = parent::actions($source);

        // Remove edit and default set status option
        foreach ($actions as $key => $value) {
            if (is_array($value) && $value['type'] === EditAction::class) {
                unset($actions[$key]);
            } elseif ($value === CraftSetStatusAction::class) {
                unset($actions[$key]);
            }
        }

        $actions = array_values($actions);

        return $actions;
    }

    protected function cpEditUrl(): ?string
    {
        return UrlHelper::cpUrl('osim-tenon/issues/view/' . $this->id);
    }

    public function afterSave(bool $isNew): void
    {
        if (!$isNew) {
            $record = IssueRecord::findOne($this->id);

            if (!$record) {
                throw new InvalidConfigException('Invalid issue ID: ' . $this->id);
            }
        } else {
            $record = new IssueRecord();
            $record->id = intval($this->id);
        }

        $record->pageId = $this->pageId;
        $record->viewportId = $this->viewportId;
        $record->certainty = $this->certainty;
        $record->priority = $this->priority;
        $record->errorGroupId = $this->errorGroupId;
        $record->errorGroupTitle = $this->errorGroupTitle;
        $record->errorId = $this->errorId;
        $record->errorTitle = $this->errorTitle;
        $record->errorDescription = $this->errorDescription;
        $record->errorSnippet = $this->errorSnippet;
        $record->errorXpath = $this->errorXpath;
        $record->levelA = $this->levelA;
        $record->levelAa = $this->levelAa;
        $record->levelAaa = $this->levelAaa;
        $record->resolved = $this->resolved;

        $record->save(false);

        parent::afterSave($isNew);
    }

    public function getSupportedSites(): array
    {
        if ($this->pageId) {
            $projectId = (new Query())
                ->select(['projectId'])
                ->from([PageRecord::TABLE])
                ->where(['id' => $this->pageId])
                ->scalar();

            if ($projectId) {
                $siteId = (new Query())
                    ->select(['siteId'])
                    ->from([ProjectRecord::TABLE])
                    ->where(['id' => $projectId])
                    ->scalar();

                if ($siteId) {
                    return [$siteId];
                }
            }
        }

        if ($this->siteId) {
            return [$this->siteId];
        }

        return [Craft::$app->getSites()->getPrimarySite()->id];
    }

    public function getStatus(): ?string
    {
        if ($this->resolved) {
            return self::STATUS_RESOLVED;
        }

        return self::STATUS_UNRESOLVED;
    }

    protected static function defineSources(string $context = null): array
    {
        $plugin = Plugin::getInstance();

        $pageId = static::getPageIdFromRequest();

        if ($pageId) {
            $projectId = (new Query())
                ->select(['projectId'])
                ->from([PageRecord::TABLE])
                ->where(['id' => $pageId])
                ->scalar();

            $project = $plugin->getProjects()->getProjectById($projectId);

            $sources = [
                [
                    'key' => 'page:' . $pageId,
                    'label' => Plugin::t('Page Issues'),
                    'criteria' => [
                        'pageId' => $pageId,
                        'projectId' => $projectId
                    ],
                    'data' => [
                        'pageId' => $pageId,
                        'projectId' => $projectId
                    ],
                    'nested' => static::defineViewportSources($project, $pageId)
                ],
            ];

            return $sources;
        }

        $sources = [
            [
                'key' => '*',
                'label' => Plugin::t('All Issues'),
                'criteria' => []
            ],
        ];

        $projects = $plugin->getProjects()->getAllProjects();

        $siteProjects = [];

        foreach ($projects as $project) {
            $siteProjects[$project->siteId][] = [
                'key' => 'project:' . $project->id,
                'label' => $project->getOptionName(),
                'criteria' => [
                    'projectId' => $project->id
                ],
                'data' => [
                    'projectId' => $project->id
                ],
                'sites' => [$project->siteId],
                'nested' => static::defineViewportSources($project)
            ];
        }

        foreach ($siteProjects as $projects) {
            // if (count($projects) > 1) {
                $sources = array_merge($sources, $projects);
            // }
        }
        return $sources;
    }

    protected static function getPageIdFromRequest(): ?int
    {
        // Get-elements action request
        $source = Craft::$app->getRequest()->getParam('source');
        if ($source && substr($source, 0, 5) === 'page:') {
            $source = explode('page:', $source, 2);
            return intval($source[1]);
        }

        // Initial cp page request
        $pageUrl = Craft::$app->getRequest()->getParam('p');
        if (strpos($pageUrl, '/pages/') !== false) {
            $pageUrl = explode('/pages/', $pageUrl, 2);
            return intval($pageUrl[1]);
        }

        return null;
    }

    protected static function defineViewportSources(ProjectModel $project, int $pageId = null): array
    {
        $plugin = Plugin::getInstance();

        $viewports = $plugin->getViewports()->getViewportsByProjectId($project->id);

        $sources = [];

        foreach ($viewports as $viewport) {
            if ($pageId) {
                $key = 'page:' . $pageId . ':' . $viewport->id;
            } else {
                $key = 'project:' . $project->id . ':' . $viewport->id;
            }
            $sources[] = [
                'key' => $key,
                'label' => $viewport->getOptionName(),
                'criteria' => [
                    'pageId' => $pageId,
                    'projectId' => $project->id,
                    'viewportId' => $viewport->id
                ],
                'data' => [
                    'pageId' => $pageId,
                    'projectId' => $project->id,
                    'viewportId' => $viewport->id
                ],
            ];
        }

        return $sources;
    }

    protected static function defineTableAttributes(): array
    {
        $settings = Plugin::getInstance()->getSettings();

        $attributes = [
            'pageTitle' => Plugin::t('Page Title'),
            'pageUrl' => [
                'label' => Plugin::t('View'),
                'icon' => 'world',
            ],
            'viewport' => Plugin::t('Viewport'),
            'certainty' => Plugin::t('Certainty'),
            'priority' => Plugin::t('Priority'),
            'errorGroupId' => Plugin::t('Best Practice'),
            'errorId' => Plugin::t('Test'),
        ];

        if ($settings->displayLevel === 'minimum') {
            $attributes['level'] = Plugin::t('Level');
        } else {
            $attributes['level'] = Plugin::t('Levels');
        }

        return $attributes;
    }

    protected static function defineDefaultTableAttributes(string $source): array
    {
        return [
            'errorGroupId',
            'errorId',
            'level',
            'pageTitle',
            'viewport',
            'pageUrl',
        ];
    }

    protected static function defineSortOptions(): array
    {
        return [
            'pageTitle' => Plugin::t('Page Title'),
            'certainty' => Plugin::t('Certainty'),
            'priority' => Plugin::t('Priority'),
            'errorGroupId' => Plugin::t('Best Practice'),
            'errorId' => Plugin::t('Test'),
        ];
    }

    protected function tableAttributeHtml(string $attribute): string
    {
        $settings = Plugin::getInstance()->getSettings();

        if ($attribute === 'pageUrl') {
            return parent::tableAttributeHtml('link');
        }

        if ($attribute === 'viewport') {
            if ($settings->displayViewport === 'name') {
                return $this->viewportName;
            } elseif ($settings->displayViewport === 'size') {
                return $this->viewportWidth . ' × ' . $this->viewportHeight;
            } else {
                return $this->viewportName . ' [' . $this->viewportWidth . ' × ' . $this->viewportHeight . ']';
            }
        }

        if ($attribute === 'viewportSize') {
            return $this->viewportWidth . ' × ' . $this->viewportHeight;
        }

        if ($attribute === 'errorGroupId') {
            return $this->errorGroupId . ': ' . $this->errorGroupTitle;
        }

        if ($attribute === 'errorId') {
            return $this->errorId . ': ' . $this->errorTitle;
        }

        if ($attribute === 'level') {
            if ($settings->displayLevel === 'minimum') {
                if ($this->levelA) {
                    return 'A';
                }

                if ($this->levelAa) {
                    return 'AA';
                }

                if ($this->levelAaa) {
                    return 'AAA';
                }
            }

            $level = [];

            if ($this->levelA) {
                $level[] = 'A';
            }

            if ($this->levelAa) {
                $level[] = 'AA';
            }

            if ($this->levelAaa) {
                $level[] = 'AAA';
            }

            return implode(', ', $level);
        }

        return parent::tableAttributeHtml($attribute);
    }

    protected static function defineSearchableAttributes(): array
    {
        return [
            // 'pageTitle',
            // 'pageUrl',
            'errorGroupTitle',
            'errorTitle',
        ];
    }
}
