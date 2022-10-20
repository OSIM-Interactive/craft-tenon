<?php
namespace osim\craft\tenon\elements\actions;

use Craft;
use craft\base\ElementAction;
use craft\base\ElementInterface;
use craft\helpers\Queue;
use craft\elements\db\ElementQueryInterface;
use osim\craft\tenon\elements\Page as PageElement;
use osim\craft\tenon\jobs\TenonTest;
use osim\craft\tenon\Plugin;

class Test extends ElementAction
{
    public function getTriggerLabel(): string
    {
        return Plugin::t('Test page');
    }

    public function getTriggerHtml(): ?string
    {
        Craft::$app->getView()->registerJsWithVars(fn($type) => <<<JS
(() => {
    new Craft.ElementActionTrigger({
        type: $type
    });
})();
JS, [static::class]);

        return null;
    }

    public function performAction(ElementQueryInterface $query): bool
    {
        $plugin = Plugin::getInstance();

        $elements = $query->all();

        foreach ($elements as $element) {
            Queue::push(new TenonTest([
                'pageId' => $element->id,
                'projectId' => $element->projectId,
            ]));
        }

        if (count($elements) === 1) {
            $this->setMessage(Plugin::t('Test job queued.'));
        } else {
            $this->setMessage(Plugin::t('Test jobs queued.'));
        }

        return true;
    }
}
