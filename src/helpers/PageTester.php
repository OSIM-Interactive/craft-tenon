<?php
namespace osim\craft\tenon\helpers;

use Craft;
use craft\db\Query;
use craft\helpers\StringHelper;
use osim\craft\tenon\Plugin;
use osim\craft\tenon\helpers\TenonTestApi;
use osim\craft\tenon\elements\Issue as IssueElement;
use osim\craft\tenon\elements\Page as PageElement;
use osim\craft\tenon\models\Account as AccountModel;
use osim\craft\tenon\models\Project as ProjectModel;
use osim\craft\tenon\models\TenonProject as TenonProjectModel;
use osim\craft\tenon\models\Viewport as ViewportModel;
use osim\craft\tenon\records\IgnoreRule as IgnoreRuleRecord;

class PageTester
{
    private ProjectModel $project;
    private AccountModel $account;
    private TenonProjectModel $tenonProjectModel;
    private TenonTestApi $tenonTestApi;

    private ?array $ignoreRules = null;
    private array $updatedViewports = [];

    public function __construct(int $projectId)
    {
        $plugin = Plugin::getInstance();
        $this->project = $plugin->getProjects()->getProjectById($projectId);
        $this->account = $plugin->getAccounts()->getAccountById($this->project->accountId);
        $this->tenonProjectModel = $this->getTenonProjectModel(
            $this->project,
            $this->account
        );

        $this->tenonTestApi = new TenonTestApi($this->account->tenonApiKey);
    }

    public function testPageUrl($pageUrl, ViewportModel $viewportModel): int
    {
        $plugin = Plugin::getInstance();

        if ($this->isIgnorablePage($pageUrl, $viewportModel->id)) {
            return 422;
        }

        $pageTitle = $this->getPageTitle($pageUrl);

        $savePage = false;

        $pageElement = PageElement::find()
            ->projectId($this->project->id)
            ->pageUrl($pageUrl)
            ->one();

        if ($pageElement) {
            if ($pageElement->pageTitle !== $pageTitle) {
                $pageElement->pageTitle = $pageTitle;

                $savePage = true;
            }
        } else {
            $pageElement = new PageElement();
            $pageElement->projectId = $this->project->id;
            $pageElement->pageTitle = $pageTitle;
            $pageElement->pageUrl = $pageUrl;
            $pageElement->levelAIssues = 0;
            $pageElement->levelAaIssues = 0;
            $pageElement->levelAaaIssues = 0;
            $pageElement->totalIssues = 0;

            $savePage = true;
        }

        $pageElement->siteId = $this->project->siteId;

        if ($savePage && !$plugin->getPages()->savePage($pageElement)) {
            return 500;
        }

        $pageElement->levelAIssues = 0;
        $pageElement->levelAaIssues = 0;
        $pageElement->levelAaaIssues = 0;
        $pageElement->totalIssues = 0;

        if (!$viewportModel->accountId) {
            $this->tenonProjectModel->viewportWidth = $viewportModel->width;
            $this->tenonProjectModel->viewportHeight = $viewportModel->height;
        }

        $result = $this->tenonTestApi->testUrl($pageUrl, $this->tenonProjectModel);

        $status = $result['status'] ?? 500;

        if ($status !== 200) {
            return $status;
        }

        $width = $result['request']['viewport']['width'];
        $height = $result['request']['viewport']['height'];

        $this->updateDefaultViewport($viewportModel, $width, $height);

        $this->saveIssues(
            $this->project->siteId,
            $pageElement->id,
            $viewportModel->id,
            $result['resultSet']
        );

        $plugin->getPages()->updateIssueCount($pageElement->id);

        return $status;
    }

    private function getTenonProjectModel(ProjectModel $project, AccountModel $account): TenonProjectModel
    {
        $settings = Plugin::getInstance()->getSettings();

        $model = new TenonProjectModel();

        $model->id = $project->tenonProjectId;
        $model->certainty = $project->certainty ?? $account->certainty ?? $settings->certainty;
        $model->priority = $project->priority ?? $account->priority ?? $settings->priority;
        $model->level = $project->level ?? $account->level ?? $settings->level;
        $model->store = $project->store ?? $account->store ?? $settings->store;
        $model->uaString = $project->uString ?? $account->uString ?? $settings->uaString;
        $model->delay = $project->delay ?? $account->delay ?? $settings->delay;

        return $model;
    }

    private function getPageTitle($pageUrl)
    {
        $title = 'Unknown';

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $pageUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        if ($this->tenonProjectModel->uaString) {
            curl_setopt($ch, CURLOPT_USERAGENT, $this->tenonProjectModel->uaString);
        }

        $html = curl_exec($ch);

        curl_close($ch);

        $pos = strpos($html, '<title>');
        if ($pos !== false) {
            $pos += 7;
            $pos2 = strpos($html, '</title>', $pos);

            if ($pos2 !== false) {
                $title = substr($html, $pos, $pos2 - $pos);
            }
        }

        // Remove trailing ' - Site Name'
        $site = Craft::$app->getSites()->getSiteById($this->project->siteId, true);
        $name = $site->getName();
        if (StringHelper::endsWith($title, $name, false)) {
            $newTitle = substr($title, 0, -strlen($name));
            $newTitle = trim($newTitle, ' -');
            if ($newTitle !== '') {
                $title = $newTitle;
            }
        }

        return $title;
    }

    private function isIgnorablePage(string $pageUrl, int $viewportId): bool
    {
        foreach ($this->getIgnoreRules() as $rule) {
            if ($rule['accountId'] !== null && $rule['accountId'] !== $this->account->id) {
                continue;
            }

            if ($rule['projectId'] !== null && $rule['projectId'] !== $this->project->id) {
                continue;
            }

            if ($rule['viewportId'] !== null && $rule['viewportId'] !== $viewportId) {
                continue;
            }

            if ($rule['pageUrlValue'] !== null &&
                !ComparatorHelper::matchAgainst(
                    $rule['pageUrlComparator'],
                    $rule['pageUrlValue'],
                    $pageUrl
                )
            ) {
                continue;
            }

            return true;
        }

        return false;
    }
    private function isIgnorableIssue(array $issue, int $viewportId): bool
    {
        foreach ($this->getIgnoreRules() as $rule) {
            if ($rule['accountId'] !== null && $rule['accountId'] !== $this->account->id) {
                continue;
            }

            if ($rule['projectId'] !== null && $rule['projectId'] !== $this->project->id) {
                continue;
            }

            if ($rule['viewportId'] !== null && $rule['viewportId'] !== $viewportId) {
                continue;
            }

            if ($rule['error_group_id'] !== null && $rule['error_group_id'] !== $issue['bpID']) {
                continue;
            }

            if ($rule['error_id'] !== null && $rule['error_id'] !== $issue['tID']) {
                continue;
            }

            if ($rule['errorXpathValue'] !== null &&
                !ComparatorHelper::matchAgainst(
                    $rule['errorXpathComparator'],
                    $rule['errorXpathValue'],
                    $issue['xpath']
                )
            ) {
                continue;
            }

            return true;
        }

        return false;
    }

    private function getIgnoreRules(): array
    {
        if ($this->ignoreRules === null) {
            $this->ignoreRules = (new Query())
                ->select([
                    'id',
                    'name',
                    'accountId',
                    'projectId',
                    'viewportId',
                    'pageUrlComparator',
                    'pageUrlValue',
                    'errorGroupId',
                    'errorId',
                    'errorXpathComparator',
                    'errorXpathValue',
                ])
                ->from([IgnoreRuleRecord::TABLE])
                ->all();
        }

        return $this->ignoreRules;
    }

    // If querying the default viewport, then update internal
    // width and height
    private function updateDefaultViewport(
        ViewportModel $viewportModel,
        int $width,
        int $height
    ) {
        if (!$viewportModel->accountId ||
            array_key_exists($viewportModel->accountId, $this->updatedViewports)
        ) {
            return;
        }

        if ($viewportModel->width !== $width || $viewportModel->height !== $height) {
            $viewportModel->width = $width;
            $viewportModel->height = $height;

            $plugin = Plugin::getInstance();
            $plugin->getViewports()->saveViewport($viewportModel);
        }

        $this->updatedViewports[$viewportModel->accountId] = true;
    }

    private function saveIssues(
        int $siteId,
        int $pageId,
        int $viewportId,
        array $issues
    ) {
        $plugin = Plugin::getInstance();

        $plugin->getIssues()->resolveIssuesByPageId($pageId);

        foreach ($issues as $issue) {
            if ($this->isIgnorableIssue($issue, $viewportId)) {
                continue;
            }

            $issueElement = $plugin->getIssues()->getIssueByError(
                $pageId,
                $viewportId,
                $issue['bpID'],
                $issue['tID'],
                $issue['xpath']
            );

            if (!$issueElement) {
                $issueElement = new IssueElement();
                $issueElement->pageId = $pageId;
                $issueElement->viewportId = $viewportId;
            }

            $issueElement->siteId = $siteId;

            $hasA = 0;
            $hasAA = 0;
            $hasAAA = 0;

            foreach ($issue['standards'] as $standard) {
                $hasA = (strpos($standard, 'Level A:') !== false ? 1 : $hasA);
                $hasAA = (strpos($standard, 'Level AA:') !== false ? 1 : $hasAA);
                $hasAAA = (strpos($standard, 'Level AAA:') !== false ? 1 : $hasAAA);
            }

            $issueElement->certainty = $issue['certainty'];
            $issueElement->priority = $issue['priority'];
            $issueElement->errorGroupId = $issue['bpID'];
            $issueElement->errorGroupTitle = $issue['resultTitle'];
            $issueElement->errorId = $issue['tID'];
            $issueElement->errorTitle = $issue['errorTitle'];
            $issueElement->errorDescription = $issue['errorDescription'];
            $issueElement->errorSnippet = html_entity_decode($issue['errorSnippet'], ENT_QUOTES, 'UTF-8');
            $issueElement->errorXpath = $issue['xpath'];
            $issueElement->levelA = $hasA;
            $issueElement->levelAa = $hasAA;
            $issueElement->levelAaa = $hasAAA;
            $issueElement->resolved = false;

            $plugin->getIssues()->saveIssue($issueElement);
        }
    }
}
