<?php
namespace osim\craft\tenon\services;

use Craft;
use craft\db\Query;
use osim\craft\tenon\records\Issue as IssueRecord;
use osim\craft\tenon\records\Page as PageRecord;
use osim\craft\tenon\elements\Issue as IssueElement;
use yii\base\Component;

class Issues extends Component
{

    public function getIssueById(int $issueId): ?IssueElement
    {
        return IssueElement::find()
            ->id($issueId)
            ->one();
    }
    public function getIssueByError(
        int $pageId,
        int $viewportId,
        int $errorGroupId,
        int $errorId,
        string $errorXpath
    ): ?IssueElement {
        return IssueElement::find()
            ->pageId($pageId)
            ->viewportId($viewportId)
            ->errorGroupId($errorGroupId)
            ->errorId($errorId)
            ->errorXpath($errorXpath)
            ->one();
    }

    public function deleteIssueById(int $issueId): bool
    {
        $issueElement = $this->getIssueById($issueId);

        if (!$issueElement) {
            return false;
        }

        return $this->deleteIssue($issueElement);
    }
    public function deleteIssue(IssueElement $issueElement): bool
    {
        return Craft::$app->getElements()->deleteElement($issueElement, true);
    }
    public function resolveIssuesByPageId(int $pageId): void
    {
        Craft::$app->db->createCommand()
            ->update(
                IssueRecord::TABLE,
                ['resolved' => true],
                ['pageId' => $pageId]
            )
            ->execute();
    }
    public function deleteIssuesByPageId(int $pageId): void
    {
        $query = (new Query())
            ->select([
                'id',
            ])
            ->from([IssueRecord::TABLE])
            ->where(['pageId' => $pageId]);

        foreach ($query->all() as $row) {
            $this->deleteIssueById($row['id']);
        }
    }

    public function saveIssue(IssueElement $issueElement, bool $runValidation = true): bool
    {
        return Craft::$app->getElements()->saveElement($issueElement, $runValidation);
    }

    public function getIssueCount(?bool $resolved = null, ?string $level = null): int
    {
        $where = [];

        if ($resolved !== null) {
            $where['resolved'] = $resolved;
        }

        if ($level === 'A') {
            $where['levelA'] = true;
        } elseif ($level === 'AA') {
            $where['levelAa'] = true;
        } elseif ($level === 'AAA') {
            $where['levelAaa'] = true;
        }

        return (new Query())
            ->from([IssueRecord::TABLE])
            ->where($where)
            ->count();
    }
}
