<?php
namespace osim\craft\tenon\elements\db;

use craft\elements\db\ElementQuery;
use craft\helpers\Db;

class PageQuery extends ElementQuery
{
    public ?int $projectId = null;
    public ?string $pageUrl = null;

    public function projectId(?int $value): static
    {
        $this->projectId = $value;
        return $this;
    }

    public function pageUrl(?string $value): static
    {
        $this->pageUrl = $value;
        return $this;
    }

    protected function beforePrepare(): bool
    {
        $this->joinElementTable('osim_tenon_pages');

        $this->query->select([
            'osim_tenon_pages.projectId',
            'osim_tenon_pages.pageTitle',
            'osim_tenon_pages.pageUrl',
            'osim_tenon_pages.levelAIssues',
            'osim_tenon_pages.levelAaIssues',
            'osim_tenon_pages.levelAaaIssues',
            'osim_tenon_pages.totalIssues',
        ]);

        if ($this->projectId) {
            $this->subQuery->andWhere(Db::parseParam('osim_tenon_pages.projectId', $this->projectId));
        }

        if ($this->pageUrl) {
            $this->subQuery->andWhere(Db::parseParam('osim_tenon_pages.pageUrl', $this->pageUrl));
        }

        return parent::beforePrepare();
    }
}
