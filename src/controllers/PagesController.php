<?php
namespace osim\craft\tenon\controllers;

use Craft;
use craft\web\Controller;
use osim\craft\tenon\Plugin;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class PagesController extends Controller
{
    public function init(): void
    {
        parent::init();

        $this->requireCpRequest();
        $this->requireAdmin(true);
    }

    public function actionIndex(?int $projectId = null): Response
    {
        return $this->renderTemplate(
            Plugin::HANDLE . '/pages/_index',
            [
                'projectId' => $projectId
            ]
        );
    }

    public function actionIssueIndex(?int $pageId = null, ?int $viewportId = null): Response
    {
        $plugin = Plugin::getInstance();

        $page = $plugin->getPages()->getPageById($pageId);

        if (!$page) {
            throw new NotFoundHttpException('Page not found.');
        }

        return $this->renderTemplate(
            Plugin::HANDLE . '/issues/_index',
            [
                'pageId' => $pageId,
                'projectId' => $page->projectId,
                'viewportId' => $viewportId,
            ]
        );
    }
}
