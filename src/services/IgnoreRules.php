<?php
namespace osim\craft\tenon\services;

use Craft;
use craft\base\MemoizableArray;
use craft\db\Query;
use craft\events\ConfigEvent;
use craft\helpers\Db;
use craft\helpers\StringHelper;
use osim\craft\tenon\Plugin;
use osim\craft\tenon\models\IgnoreRule as IgnoreRuleModel;
use osim\craft\tenon\records\IgnoreRule as IgnoreRuleRecord;
use yii\base\Component;

class IgnoreRules extends Component
{
    const PROJECT_CONFIG_PATH = 'osim.tenon.ignoreRules';

    private ?MemoizableArray $items = null;

    private function items(): MemoizableArray
    {
        if (!isset($this->items)) {
            $items = [];

            foreach ($this->createItemsQuery()->all() as $result) {
                $items[] = new IgnoreRuleModel($result);
            }

            $this->items = new MemoizableArray($items);
        }

        return $this->items;
    }
    private function createItemsQuery(): Query
    {
        $query = (new Query())
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
                'uid',
            ])
            ->from(['{{%osim_tenon_ignore_rules}}'])
            ->orderBy(['name' => \SORT_ASC]);

        return $query;
    }

    public function hasIgnoreRules(): bool
    {
        return (count($this->items()) > 0);
    }

    public function getAllIgnoreRules(): array
    {
        return $this->items()->all();
    }

    public function getIgnoreRuleById(int $id): ?IgnoreRuleModel
    {
        return $this->items()->firstWhere('id', $id);
    }
    public function getExistingIgnoreRule(IgnoreRuleModel $model): ?IgnoreRuleModel
    {
        return $this->items()
            ->where('accountId', $model->accountId)
            ->where('projectId', $model->projectId)
            ->where('viewportId', $model->viewportId)
            ->where('pageUrlComparator', $model->pageUrlComparator)
            ->where('pageUrlValue', $model->pageUrlValue)
            ->where('errorGroupId', $model->errorGroupId)
            ->where('errorId', $model->errorId)
            ->where('errorXpathComparator', $model->errorXpathComparator)
            ->firstWhere('errorXpathValue', $model->errorXpathValue);
    }

    public function deleteIgnoreRuleById(int $id): bool
    {
        $model = $this->getIgnoreRuleById($id);

        if (!$model) {
            return false;
        }

        return $this->deleteIgnoreRule($model);

    }
    public function deleteIgnoreRule(IgnoreRuleModel $model): bool
    {
        Craft::$app->getProjectConfig()->remove(
            self::PROJECT_CONFIG_PATH . '.' . $model->uid
        );

        return true;
    }

    public function saveIgnoreRule(IgnoreRuleModel $model, bool $runValidation = true): bool
    {
        $isNew = !boolval($model->id);

        if ($runValidation && !$model->validate()) {
            Craft::info('Ignore rule not saved due to validation error.', __METHOD__);
            return false;
        }

        $existingIgnoreRule = $this->getExistingIgnoreRule($model);
        if ($existingIgnoreRule && $existingIgnoreRule->id !== $model->id) {
            $model->addError('general', Plugin::t('A matching ignore rule already exists.'));
            Craft::info('Ignore rule not saved because it already exists.', __METHOD__);
            return false;
        }

        if ($model->pageUrlValue === null &&
            $model->errorGroupId === null &&
            $model->errorId === null &&
            $model->errorXpathValue === null
        ) {
            $model->addError('general', Plugin::t('At least one ignore criteria must be set.'));
            Craft::info('Ignore rule not saved because it was empty.', __METHOD__);
            return false;
        }

        if ($isNew) {
            $model->uid = StringHelper::UUID();
            $model->id = Db::idByUid(IgnoreRuleRecord::TABLE, $model->uid);
        } elseif (!$model->uid) {
            $model->uid = Db::uidById(IgnoreRuleRecord::TABLE, $model->id);
        }

        Craft::$app->getProjectConfig()->set(
            self::PROJECT_CONFIG_PATH . '.' . $model->uid,
            $model->getConfig()
        );

        if ($isNew) {
            $model->id = Db::idByUid(IgnoreRuleRecord::TABLE, $model->uid);
        }

        return true;
    }

    public function handleDeleted(ConfigEvent $event): void
    {
        $uid = $event->tokenMatches[0];
        $record = $this->getRecord($uid);

        if ($record->getIsNewRecord()) {
            return;
        }

        $record->delete();

        $this->items = null;
    }
    public function handleChanged(ConfigEvent $event): void
    {
        $uid = $event->tokenMatches[0];
        $data = $event->newValue;
        $data = $this->typecastData($data);

        $record = $this->getRecord($uid);
        $isNew = $record->getIsNewRecord();

        $record->name = $data['name'];
        $record->accountId = $data['accountId'];
        $record->projectId = $data['projectId'];
        $record->viewportId = $data['viewportId'];
        $record->pageUrlComparator = $data['pageUrlComparator'];
        $record->pageUrlValue = $data['pageUrlValue'];
        $record->errorGroupId = $data['errorGroupId'];
        $record->errorId = $data['errorId'];
        $record->errorXpathComparator = $data['errorXpathComparator'];
        $record->errorXpathValue = $data['errorXpathValue'];
        $record->uid = $uid;

        $record->save(false);

        // Clear caches
        $this->items = null;
    }
    private function getRecord($uid)
    {
        $query = IgnoreRuleRecord::find()
            ->andWhere(['uid' => $uid]);

        return $query->one() ?? new IgnoreRuleRecord();
    }

    public function typecastData(array $data)
    {
        $data['name'] = $data['name'] ?? '';
        $data['accountId'] = (intval($data['accountId'] ?? 0) !== 0 ? intval($data['accountId']) : null);
        $data['projectId'] = (intval($data['projectId'] ?? 0) !== 0 ? intval($data['projectId']) : null);
        $data['viewportId'] = (intval($data['viewportId'] ?? 0) !== 0 ? intval($data['viewportId']) : null);
        $data['pageUrlComparator'] = (($data['pageUrlComparator'] ?? '') !== '' ? $data['pageUrlComparator'] : null);
        $data['pageUrlValue'] = (($data['pageUrlValue'] ?? '') !== '' ? $data['pageUrlValue'] : null);
        if ($data['pageUrlValue'] === null) {
            $data['pageUrlComparator'] = null;
        }
        $data['errorGroupId'] = (intval($data['errorGroupId'] ?? 0) !== 0 ? intval($data['errorGroupId']) : null);
        $data['errorId'] = (intval($data['errorId'] ?? 0) !== 0 ? intval($data['errorId']) : null);
        $data['errorXpathComparator'] = (($data['errorXpathComparator'] ?? '') !== '' ? $data['errorXpathComparator'] : null);
        $data['errorXpathValue'] = (($data['errorXpathValue'] ?? '') !== '' ? $data['errorXpathValue'] : null);
        if ($data['errorXpathValue'] === null) {
            $data['errorXpathComparator'] = null;
        }

        return $data;
    }
}
