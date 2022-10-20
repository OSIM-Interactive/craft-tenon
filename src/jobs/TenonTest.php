<?php
namespace osim\craft\tenon\jobs;

use Craft;
use craft\db\Query;
use craft\queue\BaseJob;
use DateTime;
use osim\craft\tenon\Plugin;
use osim\craft\tenon\helpers\PageTester;
use osim\craft\tenon\models\Viewport as ViewportModel;
use osim\craft\tenon\records\Account as AccountRecord;
use osim\craft\tenon\records\History as HistoryRecord;
use osim\craft\tenon\records\Project as ProjectRecord;
use Xlient\Xml\Sitemap\SitemapIterator;

class TenonTest extends BaseJob
{
    const FAIL_THRESHOLD = 10;

    public ?int $siteId = null;
    public ?int $projectId = null;
    public ?int $pageId = null;
    public ?int $viewportId = null;

    private $dateTimeNow;

    public function execute($queue): void
    {
        $this->dateTimeNow = new DateTime();

        $projects = $this->getTenonProjects(
            $this->siteId,
            $this->projectId,
            $this->pageId,
            $this->viewportId
        );

        foreach ($projects as $project) {
            foreach ($project['viewports'] as $viewportModel) {
                if ($project['type'] === 'sitemap') {
                    $this->executeSitemapProject(
                        $project['projectId'],
                        $viewportModel,
                        $project['sitemapUrl'],
                    );
                } else {
                    $this->executePageProject(
                        $project['projectId'],
                        $viewportModel,
                        $project['pageUrl'],
                    );
                }
            }
        }
    }
    private function executeSitemapProject(
        int $projectId,
        ViewportModel $viewportModel,
        string $sitemapUrl
    ): void
    {
        $historyRecord = $this->getProjectHistory($projectId, $viewportModel->id);

        $pageTester = new PageTester($projectId);

        $sitemapIterator = new SitemapIterator(
            $sitemapUrl,
            [
                'modified_date_time' => $historyRecord->dateJob
            ]
        );

        $fails = 0;
        foreach ($sitemapIterator as $pageUrl => $data) {
            $status = $pageTester->testPageUrl($pageUrl, $viewportModel);

            if ($status === 500) {
                $historyRecord->status = 500;
                break;
            } elseif (in_array($status, [401, 402])) {
                $historyRecord->status = $status;
                break;
            } else if ($status !== 200) {
                $fails++;

                if ($fails > self::FAIL_THRESHOLD) {
                    $historyRecord->status = 500;
                    break;
                }

                continue;
            }

        }

        if ($historyRecord->status == null) {
            $historyRecord->dateJob = $this->dateTimeNow;
            $historyRecord->status = 200;
        }

        $historyRecord->save();
    }

    private function executePageProject(
        int $projectId,
        ViewportModel $viewportModel,
        string $pageUrl
    ): void
    {
        $pageTester = new PageTester($projectId);

        $pageTester->testPageUrl($pageUrl, $viewportModel);
    }

    private function getProjectHistory(int $projectId, int $viewportId): HistoryRecord
    {
        $historyRecord = HistoryRecord::findOne([
            'projectId' => $projectId,
            'viewportId' => $viewportId,
        ]);

        if (!$historyRecord) {
            $historyRecord = new HistoryRecord();
            $historyRecord->projectId = $projectId;
            $historyRecord->viewportId = $viewportId;
        }

        $historyRecord->dateLast = $this->dateTimeNow;
        $historyRecord->status = null;

        return $historyRecord;
    }

    private function getTenonProjects(
        ?int $siteId,
        ?int $projectId,
        ?int $pageId,
        ?int $viewportId
    ): array
    {
        $plugin = Plugin::getInstance();

        $page = null;
        if ($pageId !== null && $projectId === null) {
            $page = $plugin->getPages()->getPageById($pageId);
            if ($page) {
                $projectId = $page->projectId;
            }
        }

        $projects = [];

        $where = [];

        if ($siteId) {
            $where['siteId'] = $siteId;
        }

        if ($projectId) {
            $where['id'] = $projectId;
        }

        $query = (new Query())
            ->select([
                'id',
                'siteId',
                'accountId',
                'tenonProjectId',
                'sitemapUrl',
                'certainty',
                'priority',
                'level',
                'store',
                'uaString',
                'delay',
            ])
            ->from([ProjectRecord::TABLE]);

        if ($where) {
            $query->where($where);
        }

        foreach ($query->all() as $row) {
            $account = (new Query())
                ->select([
                    'tenonApiKey',
                    'certainty',
                    'priority',
                    'level',
                    'store',
                    'uaString',
                    'delay',
                ])
                ->from([AccountRecord::TABLE])
                ->where([
                    'id' => $row['accountId']
                ])
                ->one();

            if ($page) {
                $result = [
                    'type' => 'page',
                    'pageUrl' => $page->pageUrl,
                    'projectId' => $row['id'],
                    'viewports' => null,
                ];
            } else {
                $result = [
                    'type' => 'sitemap',
                    'sitemapUrl' => $row['sitemapUrl'],
                    'projectId' => $row['id'],
                    'viewports' => null,
                ];
            }

            $viewports = $plugin->getViewports()->getViewportsByProjectId($row['id']);

            foreach ($viewports as $key => $viewport) {
                if ($viewportId && $viewport->id !== $viewportId) {
                    unset($viewports[$key]);
                }
            }

            $result['viewports'] = array_values($viewports);

            $projects[] = $result;
        }

        return $projects;
    }

    protected function defaultDescription(): string
    {
        return Plugin::t('Processing site pages through Tenon.');
    }
}
