<?php
namespace osim\craft\tenon\models;

use craft\base\Model;
use osim\craft\tenon\Plugin;

class Settings extends Model
{
    public ?string $pluginName = 'OSiM Tenon';
    public ?string $displayLevel = 'full';
    public ?string $displayViewport = 'full';
    public ?int $certainty = null;
    public ?int $priority = null;
    public ?string $level = null;
    public ?bool $store = null;
    public ?string $uaString = null;
    public ?int $delay = null;

    protected function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [['pluginName'], 'string', 'max' => 52];
        $rules[] = [['uaString'], 'string', 'max' => 250];

        return $rules;
    }

    public function attributeLabels()
    {
        $plugin = Plugin::getInstance();

        return [
            'pluginName' => Plugin::t('Custom Plugin Name'),
            'certainty' => Plugin::t('Certainty'),
            'priority' => Plugin::t('Priority'),
            'level' => Plugin::t('WCAG Level'),
            'store' => Plugin::t('Store Results'),
            'uaString' => Plugin::t('User-Agent String'),
            'delay' => Plugin::t('Delay'),
        ];
    }
}
