<?php
namespace osim\craft\tenon\controllers;

use Craft;
use craft\web\Controller;
use osim\craft\tenon\Plugin;
use osim\craft\tenon\models\IgnoreRule as IgnoreRuleModel;
use yii\web\BadRequestHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class IgnoreRulesController extends Controller
{
    public function init(): void
    {
        parent::init();

        $this->requireCpRequest();
        $this->requireAdmin(true);
    }

    public function actionIndex(): Response
    {
        $plugin = Plugin::getInstance();

        return $this->renderTemplate(
            Plugin::HANDLE . '/settings/ignore-rules/_index',
            [
                'items' => $plugin->getIgnoreRules()->getAllIgnoreRules(),
            ]
        );
    }

    public function actionItem(?int $id = null, ?IgnoreRuleModel $item = null): Response
    {
        if ($id) {
            $plugin = Plugin::getInstance();

            $item = $plugin->getIgnoreRules()->getIgnoreRuleById($id);

            if (!$item) {
                throw new NotFoundHttpException('Ignore rule not found.');
            }
        } elseif ($item === null) {
            $item = new IgnoreRuleModel();
        }

        return $this->renderTemplate(
            Plugin::HANDLE . '/settings/ignore-rules/_item',
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
        $data = $plugin->getIgnoreRules()->typecastData($data);

        if ($data['id'] ?? null) {
            $item = $plugin->getIgnoreRules()->getIgnoreRuleById($data['id']);

            if (!$item) {
                throw new BadRequestHttpException('Ignore rule not found.');
            }
        } else {
            $item = new IgnoreRuleModel();
        }

        $item->setAttributes($data, false);

        if (!$plugin->getIgnoreRules()->saveIgnoreRule($item)) {
            Craft::$app->getSession()->setError(Plugin::t('Ignore rule not saved.'));

            Craft::$app->getUrlManager()->setRouteParams([
                'item' => $item,
            ]);

            return null;
        }

        Craft::$app->getSession()->setNotice(Plugin::t('Ignore rule saved.'));

        return $this->redirectToPostedUrl();
    }

    public function actionDelete(): Response
    {
        $plugin = Plugin::getInstance();

        $this->requirePostRequest();
        $this->requireAcceptsJson();

        $id = Craft::$app->getRequest()->getRequiredBodyParam('id');

        $plugin->getIgnoreRules()->deleteIgnoreRuleById($id);

        return $this->asSuccess();
    }
}
