<?php
namespace osim\craft\tenon\helpers;

use craft\helpers\UrlHelper;
use DateTime;
use DateTimeZone;
use osim\craft\tenon\models\TenonProject as TenonProjectModel;
use yii\base\InvalidArgumentException;

class TenonProjectApi
{
    use TenonApiTrait;

    public function getProjects(): array
    {
        $query = UrlHelper::buildQuery([
            'key' => $this->apiKey
        ]);

        $response = $this->request(
            '/projects?' . $query
        );

        if (!$response || $response['status'] !== 200) {
            return null;
        }

        $items = [];

        foreach ($response['projects'] as $project) {
            $items[] = $this->getTenonProjectModel($project);
        }

        return $items;
    }
    public function getProject(string $tenonProjectId): ?TenonProjectModel
    {
        $query = UrlHelper::buildQuery([
            'key' => $this->apiKey
        ]);

        $response = $this->request(
            '/projects/' . rawurlencode($tenonProjectId) . '?' . $query
        );

        if (!$response || $response['status'] !== 200) {
            return null;
        }

        return $this->getTenonProjectModel($response['project']);
    }
    public function postProject(TenonProjectModel $tenonProjectModel): ?TenonProjectModel
    {
        if (!$tenonProjectModel->validate()) {
            throw new InvalidArgumentException('Tenon project model is invalid.');
        }

        $data = $tenonProjectModel->getProjectApiData();
        unset($data['id']);

        $query = UrlHelper::buildQuery([
            'key' => $this->apiKey
        ]);

        $response = $this->request(
            '/projects' . '?' . $query,
            'POST',
            json_encode($data)
        );

        if (!$response || $response['status'] !== 200) {
            return null;
        }

        return $this->getTenonProjectModel($response['project']);
    }
    public function putProject(TenonProjectModel $tenonProjectModel): ?TenonProjectModel
    {
        if (!$tenonProjectModel->validate() || !$tenonProjectModel->id) {
            throw new InvalidArgumentException('Tenon project model is invalid.');
        }

        $data = $tenonProjectModel->getProjectApiData();

        $query = UrlHelper::buildQuery([
            'key' => $this->apiKey
        ]);

        $response = $this->request(
            '/projects/' . rawurlencode($tenonProjectModel->id) . '?' . $query,
            'PUT',
            json_encode($data)
        );

        if (!$response || $response['status'] !== 200) {
            return null;
        }

        return $this->getTenonProjectModel($response['project']);
    }
    public function deleteProject(string $tenonProjectId): bool
    {
        $query = UrlHelper::buildQuery([
            'key' => $this->apiKey
        ]);

        $response = $this->request(
            '/projects/' . rawurlencode($tenonProjectId) . '?' . $query,
            'DELETE'
        );

        if (!$response || $response['status'] !== 200) {
            return false;
        }

        return true;
    }

    private function getTenonProjectModel(array $project): TenonProjectModel
    {
        $data = [
            'id' => $project['id'],
            'type' => $project['type'],
            'name' => $project['name'],
            'description' => $project['description'],
            'defaultItem' => $project['defaultItem'],
            'certainty' => $project['apiDefaultCertainty'],
            'level' => $project['apiDefaultLevel'],
            'priority' => $project['apiDefaultPriority'],
            'store' => $project['apiDefaultStore'],
            'uaString' => $project['apiDefaultUAString'],
            'viewportWidth' => $project['apiDefaultViewportWidth'],
            'viewportHeight' => $project['apiDefaultViewportHeight'],
            'delay' => $project['apiDefaultDelay'],
        ];

        $dateCreated = new DateTime($project['createdAt']);
        $dateCreated->setTimezone(new DateTimeZone('UTC'));
        $data['dateCreated'] = $dateCreated;

        $dateUpdated = new DateTime($project['updatedAt']);
        $dateUpdated->setTimezone(new DateTimeZone('UTC'));
        $data['dateUpdated'] = $dateUpdated;

        return new TenonProjectModel($data);
    }
}
