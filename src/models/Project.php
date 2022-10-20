<?php
namespace osim\craft\tenon\models;

use Craft;
use craft\base\Model;
use craft\helpers\StringHelper;
use craft\validators\UrlValidator;
use osim\craft\tenon\Plugin;
use osim\craft\tenon\records\ProjectViewport as ProjectViewportRecord;

class Project extends Model
{
    public ?int $id = null;
    public ?string $name = null;
    public ?int $siteId = null;
    public ?int $accountId = null;
    public ?string $tenonProjectId = null;
    public ?string $sitemapUrl = null;
    public ?int $certainty = null;
    public ?int $priority = null;
    public ?string $level = null;
    public ?bool $store = null;
    public ?string $uaString = null;
    public ?int $delay = null;
    private ?array $viewports = null;
    public ?string $uid = null;

    public function getViewports(): array
    {
        if ($this->viewports !== null) {
            return $this->viewports;
        }

        if (!$this->id) {
            return [];
        }

        $plugin = Plugin::getInstance();
        $this->setViewports($plugin->getProjects()->getProjectViewports($this->id));

        return $this->viewports;
    }
    public function setViewports(array $viewports)
    {
        $this->viewports = $viewports;
    }

    public function getViewportIds(): array
    {
        $viewportIds = [];

        foreach ($this->getViewports() as $viewport) {
            $viewportIds[] = $viewport['viewportId'];
        }

        return $viewportIds;
    }

    public function getOptionName(): string
    {
        return $this->name;
    }

    protected function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [['name', 'siteId', 'sitemapUrl', 'accountId'], 'required'];
        $rules[] = [['name', 'sitemapUrl', 'tenonProjectId', 'uaString'], 'trim'];
        $rules[] = [['name', 'sitemapUrl', 'tenonProjectId', 'uaString'], 'string', 'max' => 250];
        $rules[] = [['sitemapUrl'], UrlValidator::class, 'defaultScheme' => 'https'];
        $rules[] = [['level'], 'string', 'max' => 3];
        $rules[] = [['certainty', 'priority'], 'number', 'integerOnly' => true, 'min' => 0, 'max' => 100];
        $rules[] = [['delay'], 'number', 'integerOnly' => true, 'min' => 0];
        $rules[] = [['store'], 'boolean'];

        return $rules;
    }

    public function attributeLabels()
    {
        $plugin = Plugin::getInstance();

        return [
            'name' => Plugin::t('Name'),
            'siteId' => Plugin::t('Site'),
            'accountId' => Plugin::t('Account'),
            'tenonProjectId' => Plugin::t('Tenon Project ID'),
            'sitemapUrl' => Plugin::t('Sitemap URL'),
            'certainty' => Plugin::t('Certainty'),
            'priority' => Plugin::t('Priority'),
            'level' => Plugin::t('WCAG Level'),
            'store' => Plugin::t('Store Results'),
            'uaString' => Plugin::t('User-Agent String'),
            'delay' => Plugin::t('Delay'),
        ];
    }

    public function getConfig(): array
    {
        $viewports = [];

        if ($this->viewports) {
            foreach ($this->viewports as $model) {
                if (!$model->uid) {
                    if ($model->id) {
                        $model->uid = Db::uidById(ProjectViewportRecord::TABLE, $model->id);
                    } else {
                        $model->uid = StringHelper::UUID();
                    }

                }
                $viewports[$model->uid] = $model->getConfig();
            }
        }

        return [
            'name' => $this->name,
            'siteId' => $this->siteId,
            'accountId' => $this->accountId,
            'tenonProjectId' => $this->tenonProjectId,
            'sitemapUrl' => $this->sitemapUrl,
            'certainty' => $this->certainty,
            'priority' => $this->priority,
            'level' => $this->level,
            'store' => $this->store,
            'uaString' => $this->uaString,
            'viewports' => $viewports,
            'delay' => $this->delay,
        ];
    }
}
