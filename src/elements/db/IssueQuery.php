<?php
namespace osim\craft\tenon\elements\db;

use craft\elements\db\ElementQuery;
use craft\helpers\Db;
use osim\craft\tenon\elements\Issue as IssueElement;
use osim\craft\tenon\records\Page as PageRecord;
use osim\craft\tenon\records\Viewport as ViewportRecord;

class IssueQuery extends ElementQuery
{
    public ?int $pageId = null;
    public ?int $projectId = null;
    public ?int $viewportId = null;
    public ?int $certainty = null;
    public ?int $priority = null;
    public ?int $errorGroupId = null;
    public ?int $errorGroupTitle = null;
    public ?int $errorId = null;
    public ?int $errorTitle = null;
    public ?string $errorXpath = null;
    public ?bool $levelA = null;
    public ?bool $levelAa = null;
    public ?bool $levelAaa = null;

    public function pageId(?int $value): static
    {
        $this->pageId = $value;
        return $this;
    }

    public function projectId(?int $value): static
    {
        $this->projectId = $value;
        return $this;
    }

    public function viewportId(?int $value): static
    {
        $this->viewportId = $value;
        return $this;
    }

    public function certainty(?int $value): static
    {
        $this->certainty = $value;
        return $this;
    }

    public function priority(?int $value): static
    {
        $this->priority = $value;
        return $this;
    }

    public function errorGroupId(?int $value): static
    {
        $this->errorGroupId = $value;
        return $this;
    }

    public function errorGroupTitle(?int $value): static
    {
        $this->errorGroupTitle = $value;
        return $this;
    }

    public function errorId(?int $value): static
    {
        $this->errorId = $value;
        return $this;
    }

    public function errorTitle(?int $value): static
    {
        $this->errorTitle = $value;
        return $this;
    }

    public function errorXpath(?string $value): static
    {
        $this->errorXpath = $value;
        return $this;
    }

    public function levelA(?bool $value): static
    {
        $this->levelA = $value;
        return $this;
    }

    public function levelAa(?bool $value): static
    {
        $this->levelAa = $value;
        return $this;
    }

    public function levelAaa(?bool $value): static
    {
        $this->levelAaa = $value;
        return $this;
    }

    protected function beforePrepare(): bool
    {
        $this->joinElementTable('osim_tenon_issues');

        $this->query->select([
            'osim_tenon_pages.projectId',

            'osim_tenon_issues.pageId',
            'osim_tenon_pages.pageTitle',
            'osim_tenon_pages.pageUrl',

            'osim_tenon_issues.viewportId',
            'osim_tenon_viewports.name AS viewportName',
            'osim_tenon_viewports.width AS viewportWidth',
            'osim_tenon_viewports.height AS viewportHeight',

            'osim_tenon_issues.certainty',
            'osim_tenon_issues.priority',
            'osim_tenon_issues.errorGroupId',
            'osim_tenon_issues.errorGroupTitle',
            'osim_tenon_issues.errorId',
            'osim_tenon_issues.errorTitle',
            'osim_tenon_issues.errorDescription',
            'osim_tenon_issues.errorSnippet',
            'osim_tenon_issues.errorXpath',
            'osim_tenon_issues.levelA',
            'osim_tenon_issues.levelAa',
            'osim_tenon_issues.levelAaa',
            'osim_tenon_issues.resolved',
        ]);

        // Page
        $this->query
            ->innerJoin(
                ['osim_tenon_pages' => PageRecord::TABLE],
                '[[osim_tenon_pages.id]] = [[osim_tenon_issues.pageId]]'
            );
        $this->subQuery
            ->innerJoin(
                ['osim_tenon_pages' => PageRecord::TABLE],
                '[[osim_tenon_pages.id]] = [[osim_tenon_issues.pageId]]'
            );

        // Viewport
        $this->query
            ->innerJoin(
                ['osim_tenon_viewports' => ViewportRecord::TABLE],
                '[[osim_tenon_viewports.id]] = [[osim_tenon_issues.viewportId]]'
            );
        $this->subQuery
            ->innerJoin(
                ['osim_tenon_viewports' => ViewportRecord::TABLE],
                '[[osim_tenon_viewports.id]] = [[osim_tenon_issues.viewportId]]'
            );

        if ($this->pageId) {
            $this->subQuery->andWhere(Db::parseParam('osim_tenon_issues.pageId', $this->pageId));
        }

        if ($this->projectId) {
            $this->subQuery->andWhere(Db::parseParam('osim_tenon_pages.projectId', $this->projectId));
        }

        if ($this->viewportId) {
            $this->subQuery->andWhere(Db::parseParam('osim_tenon_issues.viewportId', $this->viewportId));
        }

        if ($this->certainty) {
            $this->subQuery->andWhere(Db::parseParam('osim_tenon_issues.certainty', $this->certainty));
        }

        if ($this->priority) {
            $this->subQuery->andWhere(Db::parseParam('osim_tenon_issues.priority', $this->priority));
        }

        if ($this->errorGroupId) {
            $this->subQuery->andWhere(Db::parseParam('osim_tenon_issues.errorGroupId', $this->errorGroupId));
        }

        if ($this->errorGroupTitle) {
            $this->subQuery->andWhere(Db::parseParam('osim_tenon_issues.errorGroupTitle', $this->errorGroupTitle));
        }

        if ($this->errorId) {
            $this->subQuery->andWhere(Db::parseParam('osim_tenon_issues.errorId', $this->errorId));
        }

        if ($this->errorTitle) {
            $this->subQuery->andWhere(Db::parseParam('osim_tenon_issues.errorTitle', $this->errorTitle));
        }

        if ($this->errorXpath) {
            $this->subQuery->andWhere(Db::parseParam('osim_tenon_issues.errorXpath', $this->errorXpath));
        }

        if ($this->levelA) {
            $this->subQuery->andWhere(Db::parseParam('osim_tenon_issues.levelA', $this->levelA));
        }

        if ($this->levelAa) {
            $this->subQuery->andWhere(Db::parseParam('osim_tenon_issues.levelAa', $this->levelAa));
        }

        if ($this->levelAaa) {
            $this->subQuery->andWhere(Db::parseParam('osim_tenon_issues.levelAaa', $this->levelAaa));
        }

        return parent::beforePrepare();
    }

    protected function statusCondition(string $status): mixed
    {
        return match ($status) {
            IssueElement::STATUS_RESOLVED => [
                'osim_tenon_issues.resolved' => true
            ],
            IssueElement::STATUS_UNRESOLVED => [
                'osim_tenon_issues.resolved' => false
            ],
            default => parent::statusCondition($status),
        };
    }
}
