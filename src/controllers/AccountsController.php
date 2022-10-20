<?php
namespace osim\craft\tenon\controllers;

use Craft;
use craft\web\Controller;
use osim\craft\tenon\Plugin;
use osim\craft\tenon\models\Account as AccountModel;
use yii\web\BadRequestHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class AccountsController extends Controller
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
            Plugin::HANDLE . '/settings/accounts/_index',
            [
                'items' => $plugin->getAccounts()->getAllAccounts(),
            ]
        );
    }

    public function actionItem(?int $id = null, ?AccountModel $item = null): Response
    {
        if ($id) {
            $plugin = Plugin::getInstance();
            $item = $plugin->getAccounts()->getAccountById($id);
            if (!$item) {
                throw new NotFoundHttpException('Account not found.');
            }
        } elseif ($item === null) {
            $item = new AccountModel();
        }

        return $this->renderTemplate(
            Plugin::HANDLE . '/settings/accounts/_item',
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
        $data = $plugin->getAccounts()->typecastData($data);

        if ($data['id'] ?? null) {
            $item = $plugin->getAccounts()->getAccountById($data['id']);

            if (!$item) {
                throw new BadRequestHttpException('Account not found.');
            }
        } else {
            $item = new AccountModel();
        }

        $item->setAttributes($data, false);

        if (!$plugin->getAccounts()->saveAccount($item)) {
            Craft::$app->getSession()->setError(Plugin::t('Account not saved.'));

            Craft::$app->getUrlManager()->setRouteParams([
                'item' => $item,
            ]);

            return null;
        }

        Craft::$app->getSession()->setNotice(Plugin::t('Account saved.'));

        return $this->redirectToPostedUrl();
    }

    public function actionDelete(): Response
    {
        $plugin = Plugin::getInstance();

        $this->requirePostRequest();
        $this->requireAcceptsJson();

        $id = Craft::$app->getRequest()->getRequiredBodyParam('id');

        $plugin->getAccounts()->deleteAccountById($id);

        return $this->asSuccess();
    }
}
