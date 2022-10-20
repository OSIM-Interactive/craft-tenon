<?php
namespace osim\craft\tenon\controllers;

use Craft;
use craft\web\Controller;
use osim\craft\tenon\helpers\TenonProjectApi;
use osim\craft\tenon\Plugin;
use yii\web\BadRequestHttpException;
use yii\web\Response;

class TenonController extends Controller
{
    public function init(): void
    {
        parent::init();

        $this->requireAcceptsJson();
        $this->requireAdmin(true);
    }

    public function actionProjectOptions(int $accountId): Response
    {
        $plugin = Plugin::getInstance();

        $account = $plugin->getAccounts()->getAccountById($accountId);

        if (!$account) {
            throw new BadRequestHttpException('Account ID not set or is invalid.');
        }

        $tenonProjectApi = new TenonProjectApi($account->tenonApiKey);
        $projects = $tenonProjectApi->getProjects();

        if ($projects === null) {
            throw new BadRequestHttpException('Account ID not set or is invalid.');
        }

        $projectOptions = [];

        foreach ($projects as $project) {
            if ($project->defaultItem) {
                $priority = 1;
            } else {
                $priority = 0;
            }

            $projectOptions[] = [
                'value' => $project->id,
                'name' => $project->name,
                'hint' => $project->id,
                'priority' => $priority
            ];
        }

        return $this->asJson([
            'options' => $projectOptions
        ]);
    }
}
