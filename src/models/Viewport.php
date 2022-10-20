<?php
namespace osim\craft\tenon\models;

use Craft;
use craft\base\Model;
use craft\helpers\UrlHelper;

use osim\craft\tenon\Plugin;

class Viewport extends Model
{
    public ?int $id = null;
    public ?int $accountId = null;
    public ?string $name = null;
    public ?int $width = null;
    public ?int $height = null;
    public ?string $uid = null;

    public function getOptionName(): string
    {
        return $this->name . ' [' . $this->width . ' × ' . $this->height . ']';
    }

    protected function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [['name', 'width', 'height'], 'required'];
        $rules[] = [['width', 'height'], 'number', 'integerOnly' => true, 'min' => 0];
        $rules[] = [['name'], 'string', 'max' => 250];

        return $rules;
    }

    public function attributeLabels()
    {
        $plugin = Plugin::getInstance();

        return [
            'accountId' => Plugin::t('Account'),
            'name' => Plugin::t('Name'),
            'width' => Plugin::t('Width'),
            'height' => Plugin::t('Height'),
        ];
    }

    public function getConfig(): array
    {
        return [
            'accountId' => $this->accountId,
            'name' => $this->name,
            'width' => $this->width,
            'height' => $this->height,
        ];
    }
}
