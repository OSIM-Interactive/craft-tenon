<?php
namespace osim\craft\tenon\elements\actions;

use Craft;
use craft\base\Element;
use craft\base\ElementAction;
use craft\base\ElementInterface;
use craft\elements\db\ElementQueryInterface;
use osim\craft\tenon\elements\Issue as IssueElement;
use osim\craft\tenon\Plugin;

class SetStatus extends ElementAction
{
    public const RESOLVED = IssueElement::STATUS_RESOLVED;
    public const UNRESOLVED = IssueElement::STATUS_UNRESOLVED;

    public ?string $status = null;

    public function getTriggerLabel(): string
    {
        return Craft::t('app', 'Set Status');
    }

    protected function defineRules(): array
    {
        $rules = parent::defineRules();
        $rules[] = [['status'], 'required'];
        $rules[] = [['status'], 'in', 'range' => [self::RESOLVED, self::UNRESOLVED]];
        return $rules;
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

        return Craft::$app->getView()->renderTemplate(
            Plugin::HANDLE . '/issues/_elementactions/set-status'
        );
    }

    public function performAction(ElementQueryInterface $query): bool
    {
        $plugin = Plugin::getInstance();

        $elements = $query->all();
        $failCount = 0;

        $pageIds = [];

        foreach ($elements as $element) {
            switch ($this->status) {
                case self::RESOLVED:
                    // Skip if there's nothing to change
                    if ($element->resolved) {
                        continue 2;
                    }

                    $element->resolved = true;
                    $element->setScenario(Element::SCENARIO_LIVE);

                    if (!in_array($element->pageId, $pageIds)) {
                        $pageIds[] = $element->pageId;
                    }
                    break;
                case self::UNRESOLVED:
                    // Skip if there's nothing to change
                    if (!$element->resolved) {
                        continue 2;
                    }

                    $element->resolved = false;

                    if (!in_array($element->pageId, $pageIds)) {
                        $pageIds[] = $element->pageId;
                    }
                    break;
            }

            if (!$plugin->getIssues()->saveIssue($element)) {
                // Validation error
                $failCount++;
            }
        }

        // Update page issue counts
        foreach ($pageIds as $pageId) {
            $plugin->getPages()->updateIssueCount($pageId);
        }

        // Did all of them fail?
        if ($failCount === count($elements)) {
            if (count($elements) === 1) {
                $this->setMessage(Craft::t('app', 'Could not update status due to a validation error.'));
            } else {
                $this->setMessage(Craft::t('app', 'Could not update statuses due to validation errors.'));
            }

            return false;
        }

        if ($failCount !== 0) {
            $this->setMessage(Craft::t('app', 'Status updated, with some failures due to validation errors.'));
        } else {
            if (count($elements) === 1) {
                $this->setMessage(Craft::t('app', 'Status updated.'));
            } else {
                $this->setMessage(Craft::t('app', 'Statuses updated.'));
            }
        }

        return true;
    }
}
