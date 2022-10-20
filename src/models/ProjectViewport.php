<?php
namespace osim\craft\tenon\models;

use craft\base\Model;
use osim\craft\tenon\Plugin;

class ProjectViewport extends Model
{
    public ?int $id = null;
    public ?int $projectId = null;
    public ?int $viewportId = null;
    public ?string $uid = null;

    protected function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [['projectId', 'viewportId'], 'required'];

        return $rules;
    }

    public function attributeLabels()
    {
        $plugin = Plugin::getInstance();

        return [
            'projectId' => Plugin::t('Project'),
            'viewportId' => Plugin::t('Viewport'),
        ];
    }

    public function getConfig(): array
    {
        return [
            'viewportId' => $this->viewportId,
        ];
    }
}
