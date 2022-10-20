<?php
namespace osim\craft\tenon\services;

use Craft;
use craft\db\Query;
use osim\craft\tenon\elements\Page as PageElement;
use yii\base\Component;

class Pages extends Component
{
    public function getPageById(int $pageId): PageElement
    {
        return PageElement::find()
            ->id($pageId)
            ->one();
    }

    public function deletePageById(int $pageId): bool
    {
        $pageElement = $this->getPageById($pageId);

        if (!$pageElement) {
            return false;
        }

        return $this->deletePage($pageElement);
    }
    public function deletePage(PageElement $pageElement): bool
    {
        return Craft::$app->getElements()->deleteElement($pageElement, true);
    }

    public function savePage(PageElement $pageElement, bool $runValidation = true): bool
    {
        return Craft::$app->getElements()->saveElement($pageElement, $runValidation);
    }

    public function getPageCount(): int
    {
        return (new Query())
            ->from('osim_tenon_pages')
            ->count();
    }

    public function updateIssueCount(int $pageId)
    {
        $pageElement = PageElement::find()
            ->id($pageId)
            ->one();

        $levelAIssues = (new Query())
            ->from('osim_tenon_issues')
            ->where([
                'pageId' => $pageId,
                'levelA' => true,
                'resolved' => false,
            ])
            ->count();

        $levelAaIssues = (new Query())
            ->from('osim_tenon_issues')
            ->where([
                'pageId' => $pageId,
                'levelAa' => true,
                'resolved' => false,
            ])
            ->count();

        $levelAaaIssues = (new Query())
            ->from('osim_tenon_issues')
            ->where([
                'pageId' => $pageId,
                'levelAaa' => true,
                'resolved' => false,
            ])
            ->count();

        $totalIssues = (new Query())
            ->from('osim_tenon_issues')
            ->where([
                'pageId' => $pageId,
                'resolved' => false,
            ])
            ->count();

        $pageElement->levelAIssues = $levelAIssues;
        $pageElement->levelAaIssues = $levelAaIssues;
        $pageElement->levelAaaIssues = $levelAaaIssues;
        $pageElement->totalIssues = $totalIssues;

        $this->savePage($pageElement);
    }
}
