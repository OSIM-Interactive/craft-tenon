<?php
namespace osim\craft\tenon\services;

use Craft;
use craft\base\MemoizableArray;
use craft\db\Query;
use craft\events\ConfigEvent;
use craft\helpers\Db;
use craft\helpers\ArrayHelper;
use craft\helpers\StringHelper;
use osim\craft\tenon\Plugin;
use osim\craft\tenon\models\Project as ProjectModel;
use osim\craft\tenon\models\ProjectViewport as ProjectViewportModel;
use osim\craft\tenon\records\Project as ProjectRecord;
use osim\craft\tenon\records\ProjectViewport as ProjectViewportRecord;
use yii\base\Component;

class Projects extends Component
{
    const PROJECT_CONFIG_PATH = 'osim.tenon.projects';

    private ?MemoizableArray $items = null;

    private function items(): MemoizableArray
    {
        if (!isset($this->items)) {
            $items = [];

            $query = (new Query())
                ->select([
                    'id',
                    'name',
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
                    'uid',
                ])
                ->from([ProjectRecord::TABLE])
                ->orderBy(['name' => \SORT_ASC]);

            foreach ($query->all() as $row) {
                $items[] = new ProjectModel($row);
            }

            $this->items = new MemoizableArray($items);
        }

        return $this->items;
    }

    public function hasProjects(): bool
    {
        return (count($this->items()) > 0);
    }

    public function getAllProjects(): array
    {
        return $this->items()->all();
    }

    public function getProjectById(int $id): ?ProjectModel
    {
        return $this->items()->firstWhere('id', $id);
    }
    public function getProjectViewports(int $projectId): array
    {
        $items = [];

        $query = (new Query())
            ->select([
                'id',
                'projectId',
                'viewportId',
                'uid',
            ])
            ->from([ProjectViewportRecord::TABLE])
            ->where(['projectId' => $projectId]);

        foreach ($query->all() as $row) {
            $items[] = new ProjectViewportModel($row);
        }

        return $items;
    }
    public function deleteProjectById(int $id): bool
    {
        $model = $this->getProjectById($id);

        if (!$model) {
            return false;
        }

        return $this->deleteProject($model);

    }
    public function deleteProject(ProjectModel $model): bool
    {
        Craft::$app->getProjectConfig()->remove(
            self::PROJECT_CONFIG_PATH . '.' . $model->uid
        );

        return true;
    }

    public function saveProject(ProjectModel $model, bool $runValidation = true): bool
    {
        $isNew = !boolval($model->id);

        if ($runValidation && !$model->validate()) {
            Craft::info('Ignore rule not saved due to validation error.', __METHOD__);
            return false;
        }

        if ($isNew) {
            $model->uid = StringHelper::UUID();
        } elseif (!$model->uid) {
            $model->uid = Db::uidById(ProjectRecord::TABLE, $model->id);
        }

        Craft::$app->getProjectConfig()->set(
            self::PROJECT_CONFIG_PATH . '.' . $model->uid,
            $model->getConfig()
        );

        if ($isNew) {
            $model->id = Db::idByUid(ProjectRecord::TABLE, $model->uid);
        }

        return true;
    }

    public function handleDeleted(ConfigEvent $event): void
    {
        $uid = $event->tokenMatches[0];
        $record = $this->getProjectRecord($uid);

        if ($record->getIsNewRecord()) {
            return;
        }

        $id = $record->id;

        Craft::$app->db->createCommand()
            ->delete(ProjectViewportRecord::TABLE, ['projectId' => $id])
            ->execute();

        $record->delete();

        $this->items = null;
    }
    public function handleChanged(ConfigEvent $event): void
    {
        $uid = $event->tokenMatches[0];
        $data = $event->newValue;
        $data = $this->typecastData($data);

        $record = $this->getProjectRecord($uid);
        $isNew = $record->getIsNewRecord();

        $record->name = $data['name'];
        $record->siteId = $data['siteId'];
        $record->accountId = $data['accountId'];
        $record->tenonProjectId = $data['tenonProjectId'];
        $record->sitemapUrl = $data['sitemapUrl'];
        $record->certainty = $data['certainty'];
        $record->priority = $data['priority'];
        $record->level = $data['level'];
        $record->store = $data['store'];
        $record->uaString = $data['uaString'];
        $record->delay = $data['delay'];
        $record->uid = $uid;

        $record->save(false);

        $previousViewportsIds = $this->getProjectViewports($record->id);
        $previousViewportsIds = ArrayHelper::getColumn($previousViewportsIds, 'id');
        $newViewportIds = [];

        if (isset($data['viewports'])) {
            foreach ($data['viewports'] as $uid => $viewport) {
                $viewportRecord = $this->getProjectViewportRecord($uid);
                $viewportRecord->projectId = $record->id;
                $viewportRecord->viewportId = $viewport['viewportId'];
                $viewportRecord->uid = $uid;

                $viewportRecord->save(false);

                $newViewportIds[] = $viewportRecord->id;
            }
        }

        $deleteIds = array_diff($previousViewportsIds, $newViewportIds);

        Craft::$app->db->createCommand()
            ->delete(ProjectViewportRecord::TABLE, ['id' => $deleteIds])
            ->execute();

        $this->items = null;
    }
    private function getProjectRecord($uid)
    {
        $query = ProjectRecord::find()
            ->andWhere(['uid' => $uid]);

        return $query->one() ?? new ProjectRecord();
    }
    private function getProjectViewportRecord($uid)
    {
        $query = ProjectViewportRecord::find()
            ->andWhere(['uid' => $uid]);

        return $query->one() ?? new ProjectViewportRecord();
    }

    public function getProjectOptions($emptyOption = null)
    {
        $options = [];

        if ($emptyOption !== null) {
            $options[0] = strval($emptyOption);
        }

        foreach ($this->getAllProjects() as $model) {
            $options[$model->id] = $model->getOptionName();
        }

        return $options;
    }

    public function typecastData(array $data)
    {
        $data['name'] = $data['name'] ?? '';
        $data['siteId'] = intval($data['siteId'] ?? 0);
        $data['siteId'] = ($data['siteId'] ? $data['siteId'] : null);
        $data['accountId'] = intval($data['accountId'] ?? 0);
        $data['accountId'] = ($data['accountId'] ? $data['accountId'] : null);
        $data['tenonProjectId'] = (($data['tenonProjectId'] ?? '') !== '' ? $data['tenonProjectId'] : null);
        $data['sitemapUrl'] = $data['sitemapUrl'] ?? '';
        $data['certainty'] = (($data['certainty'] ?? '') !== '' ? intval($data['certainty']) : null);
        $data['priority'] = (($data['priority'] ?? '') !== '' ? intval($data['priority']) : null);
        $data['level'] = (($data['level'] ?? '') !== '' ? $data['level'] : null);
        $data['store'] = (($data['store'] ?? '') !== '' ? intval($data['store']) : null);
        $data['uaString'] = (($data['uaString'] ?? '') !== '' ? $data['uaString'] : null);
        $data['delay'] = (($data['delay'] ?? '') !== '' ? intval($data['delay']) : null);

        $data['viewportIds'] = array_map('intval', $data['viewportIds'] ?? []);
        foreach ($data['viewportIds'] as $key => $value) {
            if ($value <= 0) {
                unset($data['viewportIds'][$key]);
            }
        }
        $data['viewportIds'] = array_values($data['viewportIds']);

        return $data;
    }
}
