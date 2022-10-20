<?php
namespace osim\craft\tenon\elements\actions;

use Craft;
use craft\elements\actions\View as CraftView;

class View extends CraftView
{
    public function getTriggerHtml(): ?string
    {
        Craft::$app->getView()->registerJsWithVars(fn($type) => <<<JS
(() => {
    new Craft.ElementActionTrigger({
        type: $type,
        batch: false,
        activate: \$selectedItems => {
            window.open(\$selectedItems.find('.element').data('url'));
        },
    });
})();
JS, [static::class]);

        return null;
    }
}
