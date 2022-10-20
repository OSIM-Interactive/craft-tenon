<?php
namespace osim\craft\tenon\models;

use craft\base\Model;
use osim\craft\tenon\Plugin;
use osim\craft\tenon\validators\ComparatorValidator;

class IgnoreRule extends Model
{
    public ?int $id = null;
    public ?string $name = null;
    public ?int $accountId = null;
    public ?int $projectId = null;
    public ?int $viewportId = null;
    public ?string $pageUrlComparator = null;
    public ?string $pageUrlValue = null;
    public ?int $errorGroupId = null;
    public ?int $errorId = null;
    public ?string $errorXpathComparator = null;
    public ?string $errorXpathValue = null;
    public ?string $uid = null;

    protected function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [['name'], 'required'];
        $rules[] = [['name', 'pageUrlValue', 'errorXpathValue'], 'string', 'max' => 250];
        $rules[] = [['errorGroupId', 'errorId'], 'number', 'integerOnly' => true, 'min' => 0];
        $rules[] = [['pageUrlComparator', 'errorXpathComparator'], ComparatorValidator::class];

        return $rules;
    }

    public function attributeLabels()
    {
        $plugin = Plugin::getInstance();

        return [
            'name' => Plugin::t('Name'),
            'accountId' => Plugin::t('Account'),
            'projectId' => Plugin::t('Project'),
            'viewportId' => Plugin::t('Viewport'),
            'pageUrlComparator' => Plugin::t('Page URL Comparator'),
            'pageUrlValue' => Plugin::t('Page URL Value'),
            'errorGroupId' => Plugin::t('Best Practice ID'),
            'errorId' => Plugin::t('Test ID'),
            'errorXpathComparator' => Plugin::t('Error XPath Comparator'),
            'errorXpathValue' => Plugin::t('Error XPath Value'),
        ];
    }

    public function getConfig(): array
    {
        return [
            'name' => $this->name,
            'accountId' => $this->accountId,
            'projectId' => $this->projectId,
            'viewportId' => $this->viewportId,
            'pageUrlComparator' => $this->pageUrlComparator,
            'pageUrlValue' => $this->pageUrlValue,
            'errorGroupId' => $this->errorGroupId,
            'errorId' => $this->errorId,
            'errorXpathComparator' => $this->errorXpathComparator,
            'errorXpathValue' => $this->errorXpathValue,
        ];
    }
}
