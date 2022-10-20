<?php
namespace osim\craft\tenon\console\controllers;

use craft\console\Controller;
use craft\helpers\Console;
use craft\helpers\Queue;

use osim\craft\tenon\jobs\TenonTest;

class JobsController extends Controller
{
    private int $siteId;
    private int $projectId;
    private int $viewportId;

    public function options($actionID)
    {
        $options = parent::options($actionID);
        $options[] = 'siteId';
        $options[] = 'projectId';
        $options[] = 'viewportId';
    }

    public function actionIndex()
    {
        Queue::push(new TenonTest([
            'siteId' => $this->siteId,
            'projectId' => $this->projectId,
            'viewportId' => $this->viewportId,
        ]));

        return ExitCode::OK;
    }
}
