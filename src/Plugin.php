<?php
namespace osim\craft\tenon;

use Craft;
use craft\base\Model;
use craft\events\RegisterComponentTypesEvent;
use craft\events\RegisterUrlRulesEvent;
use craft\events\RegisterUserPermissionsEvent;
use craft\events\TemplateEvent;
use craft\helpers\StringHelper;
use craft\services\Elements;
use craft\services\Gc;
use craft\services\UserPermissions;
use craft\web\twig\variables\Cp;
use craft\web\twig\variables\CraftVariable;
use craft\web\UrlManager;
use craft\web\View;
use osim\craft\tenon\controllers\AccountsController;
use osim\craft\tenon\controllers\IgnoreRulesController;
use osim\craft\tenon\controllers\IssuesController;
use osim\craft\tenon\controllers\PagesController;
use osim\craft\tenon\controllers\ProjectsController;
use osim\craft\tenon\controllers\SettingsController;
use osim\craft\tenon\controllers\ViewportsController;
use osim\craft\tenon\elements\Page as PageElement;
use osim\craft\tenon\elements\Issue as IssueElement;
use osim\craft\tenon\models\Settings as SettingsModel;
use osim\craft\tenon\records\Page as PageRecord;
use osim\craft\tenon\records\Issue as IssueRecord;
use osim\craft\tenon\services\Accounts;
use osim\craft\tenon\services\IgnoreRules;
use osim\craft\tenon\services\Issues;
use osim\craft\tenon\services\Pages;
use osim\craft\tenon\services\Projects;
use osim\craft\tenon\services\Settings;
use osim\craft\tenon\services\Viewports;
use yii\base\Event;

class Plugin extends \craft\base\Plugin
{
    public const HANDLE = 'osim-tenon';

    public const PROJECT_CONFIG_PATH = 'osim.tenon';

    public const PERMISSION_TEST = 'osimTenon-test';
    public const PERMISSION_VIEW_PAGES = 'osimTenon-viewPages';
    public const PERMISSION_DELETE_PAGES = 'osimTenon-deletePages';
    public const PERMISSION_VIEW_ISSUES = 'osimTenon-view';
    public const PERMISSION_DELETE_ISSUES = 'osimTenon-delete';
    public const PERMISSION_RESOLVE_ISSUES = 'osimTenon-resolve';
    public const PERMISSION_SETTINGS = 'osimTenon-settings';

    public bool $hasCpSection = true;
    public bool $hasCpSettings = true;

    public function init()
    {
        parent::init();

        if (Craft::$app->getRequest()->getIsCpRequest()) {
            $this->initCp();
        } elseif (Craft::$app->getRequest()->getIsSiteRequest()) {
            $this->initSite();
        } elseif (Craft::$app->getRequest()->getIsConsoleRequest()) {
            $this->initConsole();
        }

        $this->initServices();

        $pluginName = $this->getSettings()->pluginName;
        if (strval($pluginName) !== '') {
            $this->name = $pluginName;
        }

        $this->registerPermissions();
    }
    private function initCp()
    {
        $this->view->registerAssetBundle(\osim\craft\tenon\web\assets\cp\CpAsset::class);

        Event::on(
            UrlManager::class,
            UrlManager::EVENT_REGISTER_CP_URL_RULES,
            function (RegisterUrlRulesEvent $event) {
                $event->rules = array_merge($event->rules, [
                    self::HANDLE . '/pages' => self::HANDLE . '/pages/index',
                    self::HANDLE . '/pages/projects/<projectId:\d+>' => self::HANDLE . '/pages/index',
                    self::HANDLE . '/pages/<pageId:\d+>/issues' => self::HANDLE . '/pages/issue-index',
                    self::HANDLE . '/pages/<pageId:\d+>/issues/viewports/<viewportId:\d+>' => self::HANDLE . '/pages/issue-index',
                    self::HANDLE . '/issues' => self::HANDLE . '/issues/index',
                    self::HANDLE . '/issues/projects/<projectId:\d+>' => self::HANDLE . '/issues/index',
                    self::HANDLE . '/issues/projects/<projectId:\d+>/viewports/<viewportId:\d+>' => self::HANDLE . '/issues/index',
                    self::HANDLE . '/issues/view/<id:\d+>' => self::HANDLE . '/issues/item',
                    self::HANDLE . '/settings/general' => self::HANDLE . '/settings/item',
                    self::HANDLE . '/settings/accounts' => self::HANDLE . '/accounts/index',
                    self::HANDLE . '/settings/accounts/new' => self::HANDLE . '/accounts/item',
                    self::HANDLE . '/settings/accounts/edit/<id:\d+>' => self::HANDLE . '/accounts/item',
                    self::HANDLE . '/settings/projects' => self::HANDLE . '/projects/index',
                    self::HANDLE . '/settings/projects/new' => self::HANDLE . '/projects/item',
                    self::HANDLE . '/settings/projects/edit/<id:\d+>' => self::HANDLE . '/projects/item',
                    self::HANDLE . '/settings/viewports' => self::HANDLE . '/viewports/index',
                    self::HANDLE . '/settings/viewports/new' => self::HANDLE . '/viewports/item',
                    self::HANDLE . '/settings/viewports/edit/<id:\d+>' => self::HANDLE . '/viewports/item',
                    self::HANDLE . '/settings/ignore-rules' => self::HANDLE . '/ignore-rules/index',
                    self::HANDLE . '/settings/ignore-rules/new' => self::HANDLE . '/ignore-rules/item',
                    self::HANDLE . '/settings/ignore-rules/edit/<id:\d+>' => self::HANDLE . '/ignore-rules/item',
                    self::HANDLE . '/options/tenon-projects/<accountId:\d+>' => self::HANDLE . '/tenon/project-options',
                ]);
            }
        );

        $this->controllerMap = [
            'accounts' => AccountsController::class,
            'ignore-rules' => IgnoreRulesController::class,
            'issues' => IssuesController::class,
            'pages' => PagesController::class,
            'projects' => ProjectsController::class,
            'settings' => SettingsController::class,
            'viewports' => ViewportsController::class,
        ];

        // Add twig variables
        Event::on(
            CraftVariable::class,
            CraftVariable::EVENT_INIT,
            function (Event $event) {
                $handle = StringHelper::toCamelCase(self::HANDLE);
                $event->sender->set($handle, Variables::class);
            }
        );

        // Register elements
        Event::on(Elements::class,
            Elements::EVENT_REGISTER_ELEMENT_TYPES,
            function(RegisterComponentTypesEvent $event) {
                $event->types[] = PageElement::class;
                $event->types[] = IssueElement::class;
            }
        );

        // Elements garbage collection
        Event::on(
            Gc::class,
            Gc::EVENT_RUN,
            function (Event $event) {
                Craft::$app->getGc()->deletePartialElements(
                    PageElement::class,
                    PageRecord::TABLE,
                    'id'
                );

                Craft::$app->getGc()->deletePartialElements(
                    IssueElement::class,
                    IssueRecord::TABLE,
                    'id'
                );
            }
        );

        /* Event::on(
            \craft\models\FieldLayout::class,
            \craft\models\FieldLayout::EVENT_DEFINE_NATIVE_FIELDS,
            static function(\craft\events\DefineFieldLayoutFieldsEvent $event) {
                $fieldLayout = $event->sender;

                if ($fieldLayout->type === IssueElement::class) {
                    $event->fields[] = new \craft\fieldlayoutelements\TextField([
                        'label' => 'My Description',
                        'attribute' => 'description',
                        'mandatory' => true,
                        'instructions' => 'Enter a description.',
                    ]);
                }
            }
        ); */

        // Hide default 'title' attribute that always shows up on
        // OSiM Tenon page and issue element indexes
        /* Event::on(
            View::class,
            View::EVENT_BEFORE_RENDER_TEMPLATE,
            function (TemplateEvent $event) {
                if ($event->template === '_elements/tableview/container') {
                    if ($event->variables['attributes'][1][1]['osimTenonHideFirst'] ?? false) {
                        unset($event->variables['attributes'][0]);
                        $event->variables['attributes'] = array_values($event->variables['attributes']);
                    }
                }
            }
        ); */
    }
    private function initConsole()
    {
        $this->controllerNamespace = 'osim\\craft\\tenon\\console\\controllers';
    }
    private function initSite()
    {
        if (Craft::$app->getRequest()->getParam('tenon-xpath')) {
            $this->view->registerAssetBundle(\osim\craft\tenon\web\assets\overlay\OverlayAsset::class);
        }
    }
    private function initServices()
    {
        $this->setComponents([
            'accounts' => Accounts::class,
            'ignoreRules' => IgnoreRules::class,
            'issues' => Issues::class,
            'pages' => Pages::class,
            'projects' => Projects::class,
            'settings' => Settings::class,
            'viewports' => Viewports::class,
        ]);

        $projectConfig = Craft::$app->getProjectConfig();

        $service = $this->getAccounts();
        $projectConfig
            ->onAdd(Accounts::PROJECT_CONFIG_PATH . '.{uid}', [$service, 'handleChanged'])
            ->onUpdate(Accounts::PROJECT_CONFIG_PATH . '.{uid}', [$service, 'handleChanged'])
            ->onRemove(Accounts::PROJECT_CONFIG_PATH . '.{uid}', [$service, 'handleDeleted']);

        $service = $this->getProjects();
        $projectConfig
            ->onAdd(Projects::PROJECT_CONFIG_PATH . '.{uid}', [$service, 'handleChanged'])
            ->onUpdate(Projects::PROJECT_CONFIG_PATH . '.{uid}', [$service, 'handleChanged'])
            ->onRemove(Projects::PROJECT_CONFIG_PATH . '.{uid}', [$service, 'handleDeleted']);

        $service = $this->getViewports();
        $projectConfig
            ->onAdd(Viewports::PROJECT_CONFIG_PATH . '.{uid}', [$service, 'handleChanged'])
            ->onUpdate(Viewports::PROJECT_CONFIG_PATH . '.{uid}', [$service, 'handleChanged'])
            ->onRemove(Viewports::PROJECT_CONFIG_PATH . '.{uid}', [$service, 'handleDeleted']);

        $service = $this->getIgnoreRules();
        $projectConfig
            ->onAdd(IgnoreRules::PROJECT_CONFIG_PATH . '.{uid}', [$service, 'handleChanged'])
            ->onUpdate(IgnoreRules::PROJECT_CONFIG_PATH . '.{uid}', [$service, 'handleChanged'])
            ->onRemove(IgnoreRules::PROJECT_CONFIG_PATH . '.{uid}', [$service, 'handleDeleted']);
    }
    private function registerPermissions()
    {
        if (Craft::$app->getEdition() !== Craft::Pro) {
            return;
        }

        Event::on(
            UserPermissions::class,
            UserPermissions::EVENT_REGISTER_PERMISSIONS,
            function(RegisterUserPermissionsEvent $event) {
                $event->permissions[] = [
                    'heading' => $this->name,
                    'permissions' => [
                        self::PERMISSION_TEST => [
                            'label' => self::t('Run Test'),
                        ],

                        self::PERMISSION_VIEW_PAGES => [
                            'label' => self::t('View Pages'),
                            'nested' => [
                                self::PERMISSION_DELETE_PAGES => [
                                    'label' => self::t('Delete Pages'),
                                ],
                            ]
                        ],

                        self::PERMISSION_VIEW_ISSUES => [
                            'label' => self::t('View Issues'),
                            'nested' => [
                                self::PERMISSION_DELETE_ISSUES => [
                                    'label' => self::t('Delete Issues'),
                                ],
                                self::PERMISSION_RESOLVE_ISSUES => [
                                    'label' => self::t('Resolve Issues'),
                                ],
                            ]
                        ],

                        self::PERMISSION_SETTINGS => [
                            'label' => self::t('Access Settings'),
                        ],
                    ]
                ];
            }
        );
    }

    public function getCpNavItem(): ?array
    {
        $item = parent::getCpNavItem();
        $item['label'] = self::t($this->name);

        $item['subnav'] = [];

        $item['subnav']['overview'] = [
            'label' => self::t('Overview'),
            'url' => self::HANDLE,
        ];

        if (Craft::$app->getUser()->checkPermission(self::PERMISSION_VIEW_PAGES)) {
            $item['subnav']['pages'] = [
                'label' => self::t('Pages'),
                'url' => self::HANDLE . '/pages',
            ];
        }

        if (Craft::$app->getUser()->checkPermission(self::PERMISSION_VIEW_ISSUES)) {
            $item['subnav']['issues'] = [
                'label' => self::t('Issues'),
                'url' => self::HANDLE . '/issues',
            ];

            $badgeCount = $this->getIssues()->getIssueCount(false);
            if ($badgeCount) {
                $item['subnav']['issues']['badgeCount'] = $badgeCount;
            }
        }

        if (Craft::$app->getUser()->checkPermission(self::PERMISSION_SETTINGS) &&
            Craft::$app->getConfig()->getGeneral()->allowAdminChanges
        ) {
            $item['subnav']['settings'] = [
                'label' => self::t('Settings'),
                'url' => self::HANDLE . '/settings',
            ];
        }

        // Don't show overview item if only item
        if (count($item['subnav']) === 1) {
            unset($item['subnav']['overview']);
        }

        return $item;
    }

    public static function t(string $message, array $params = [], string $language = null): string
    {
        return Craft::t(self::HANDLE, $message, $params, $language);
    }

    protected function createSettingsModel(): ?Model
    {
        return new SettingsModel();
    }

    protected function settingsHtml(): ?string
    {
        return Craft::$app->getView()->renderTemplate(
            self::HANDLE . '/settings',
            ['settings' => $this->getSettings()]
        );
    }

    public function getPluginName(): ?string
    {
        $pluginName = $this->getSettings()->pluginName;

        if ($pluginName !== null) {
            return $pluginName;
        }

        return $this->name;
    }

    public function getAccounts(): Accounts
    {
        return $this->get('accounts');
    }

    public function getIgnoreRules(): IgnoreRules
    {
        return $this->get('ignoreRules');
    }

    public function getIssues(): Issues
    {
        return $this->get('issues');
    }

    public function getPages(): Pages
    {
        return $this->get('pages');
    }

    public function getProjects(): Projects
    {
        return $this->get('projects');
    }

    public function getViewports(): Viewports
    {
        return $this->get('viewports');
    }
}
