<?php
namespace osim\craft\tenon\models;

use Craft;
use craft\base\Model;
use craft\helpers\UrlHelper;

use osim\craft\tenon\Plugin;

class Account extends Model
{
    public ?int $id = null;
    public ?string $name = null;
    public ?string $tenonApiKey= null;
    public ?int $certainty = null;
    public ?int $priority = null;
    public ?string $level = null;
    public ?bool $store = null;
    public ?string $uaString = null;
    public ?int $delay = null;
    public ?string $uid = null;

    public function getOptionName(): string
    {
        return $this->name;
    }

    protected function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [['name', 'tenonApiKey'], 'required'];
        $rules[] = [['name', 'tenonApiKey', 'uaString'], 'string', 'max' => 250];
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
            'tenonApiKey' => Plugin::t('Tenon API Key'),
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
        return [
            'name' => $this->name,
            'tenonApiKey' => $this->tenonApiKey,
            'certainty' => $this->certainty,
            'priority' => $this->priority,
            'level' => $this->level,
            'store' => $this->store,
            'uaString' => $this->uaString,
            'delay' => $this->delay,
        ];
    }
}
