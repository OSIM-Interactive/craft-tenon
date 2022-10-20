<?php
namespace osim\craft\tenon\controllers;

use Craft;
use craft\web\Controller;
use osim\craft\tenon\Plugin;
use yii\web\HttpException;
use yii\web\Response;

class SettingsController extends Controller
{
    public function init(): void
    {
        parent::init();

        $this->requireCpRequest();
        $this->requireAdmin(true);
    }

    public function actionItem(): Response
    {
        $plugin = Plugin::getInstance();

        return $this->renderTemplate(
            Plugin::HANDLE . '/settings/general/_item',
            ['item' => $plugin->getSettings()]
        );
    }

    public function actionSave(): ?Response
    {
        $plugin = Plugin::getInstance();

        $this->requirePostRequest();

        $data = Craft::$app->getRequest()->getParam('data');

        if (!Craft::$app->getPlugins()->savePluginSettings($plugin, $data)) {
            Craft::$app->getSession()->setError(Plugin::t('Settings not saved.'));

            Craft::$app->getUrlManager()->setRouteParams([
                'item' => $plugin->getSettings(),
            ]);

            return null;
        }

        Craft::$app->getSession()->setNotice(Plugin::t('Settings saved.'));

        return $this->redirectToPostedUrl();
    }
}
