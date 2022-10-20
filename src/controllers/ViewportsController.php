<?php
namespace osim\craft\tenon\controllers;

use Craft;
use craft\web\Controller;
use osim\craft\tenon\Plugin;
use osim\craft\tenon\models\Viewport as ViewportModel;
use yii\web\BadRequestHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class ViewportsController extends Controller
{
    public function init(): void
    {
        parent::init();

        $this->requireCpRequest();
        $this->requireAdmin(true);
    }

    public function actionIndex(): Response
    {
        /* $job = new \osim\craft\tenon\jobs\TenonTest();
        $job->execute(null);
        exit; */
        $plugin = Plugin::getInstance();

        return $this->renderTemplate(
            Plugin::HANDLE . '/settings/viewports/_index',
            [
                'items' => $plugin->getViewports()->getAllViewports(),
            ]
        );
    }

    public function actionItem(?int $id = null, ?ViewportModel $item = null): Response
    {
        if ($id) {
            $plugin = Plugin::getInstance();

            $item = $plugin->getViewports()->getViewportById($id);

            if (!$item) {
                throw new NotFoundHttpException('Viewport not found.');
            }
        } elseif ($item === null) {
            $item = new ViewportModel();
        }

        return $this->renderTemplate(
            Plugin::HANDLE . '/settings/viewports/_item',
            [
                'id' => $id,
                'item' => $item,
            ]
        );
    }

    public function actionSave(): ?Response
    {
        $plugin = Plugin::getInstance();

    	$this->requirePostRequest();

        $data = Craft::$app->getRequest()->getParam('data');
        $data = $plugin->getViewports()->typecastData($data);

        if ($data['id'] ?? null) {
            $item = $plugin->getViewports()->getViewportById($data['id']);

            if (!$item) {
                throw new BadRequestHttpException('Viewport not found.');
            }
        } else {
            $item = new ViewportModel();
        }

        $item->setAttributes($data, false);

        if (!$plugin->getViewports()->saveViewport($item)) {
            Craft::$app->getSession()->setError(Plugin::t('Viewport not saved.'));

            Craft::$app->getUrlManager()->setRouteParams([
                'item' => $item,
            ]);

            return null;
        }

        Craft::$app->getSession()->setNotice(Plugin::t('Viewport saved.'));

        return $this->redirectToPostedUrl();
    }

    public function actionDelete(): Response
    {
        $plugin = Plugin::getInstance();

        $this->requirePostRequest();
        $this->requireAcceptsJson();

        $id = Craft::$app->getRequest()->getRequiredBodyParam('id');

        $plugin->getViewports()->deleteViewportById($id);

        return $this->asSuccess();
    }
}
