<?php
namespace osim\craft\tenon\models;

use Craft;
use craft\base\Model;
use craft\helpers\UrlHelper;
use DateTime;
use osim\craft\tenon\Plugin;

class TenonProject extends Model
{
    public ?string $id = null;
    public ?string $type = null;
    public ?string $name = null;
    public ?string $description = null;
    public ?bool $defaultItem = null;
    public ?int $certainty = null;
    public ?string $level = null;
    public ?int $priority = null;
    public ?bool $store = null;
    public ?string $uaString = null;
    public ?int $viewportWidth = null;
    public ?int $viewportHeight = null;
    public ?int $delay = null;
    public ?DateTime $dateCreated = null;
    public ?DateTime $dateUpdated = null;

    public function getOptionName(): string
    {
        return $this->name . ' [' . $this->id . ']';
    }

    protected function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [['type', 'name'], 'required'];

        return $rules;
    }

    public function getTestApiData(): array
    {
        $data = [
            'projectID' => $this->id,
            'certainty' => $this->certainty,
            'level' => $this->level,
            'priority' => $this->priority,
            'store' => $this->store,
            'uaString' => $this->uaString,
            'viewportWidth' => $this->viewportWidth,
            'viewportHeight' => $this->viewportHeight,
            'delay' => $this->delay,
        ];

        foreach ($data as $key => $value) {
            if ($value === null) {
                unset($data[$key]);
            }
        }

        return $data;
    }
    public function getProjectApiData(): array
    {
        $data = [
            'type' => $this->type,
            'name' => $this->name,
            'description' => $this->description,
            'defaultItem' => $this->defaultItem,
            'apiDefaultCertainty' => $this->certainty,
            'apiDefaultLevel' => $this->level,
            'apiDefaultPriority' => $this->priority,
            'apiDefaultStore' => $this->store,
            'apiDefaultUAString' => $this->uaString,
            'apiDefaultViewportWidth' => $this->viewportWidth,
            'apiDefaultViewportHeight' => $this->viewportHeight,
            'apiDefaultDelay' => $this->delay,
        ];

        foreach ($data as $key => $value) {
            if ($value === null) {
                unset($data[$key]);
            }
        }

        return $data;
    }
}
