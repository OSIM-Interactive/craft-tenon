<?php
namespace osim\craft\tenon\helpers;

use osim\craft\tenon\models\TenonProject as TenonProjectModel;

class TenonTestApi
{
    use TenonApiTrait;

    public function testUrl(
        string $url,
        TenonProjectModel $tenonProjectModel
    ) {
        $data = array_merge(
            [
                'key' => $this->apiKey,
                'url' => $url,
            ],
            $tenonProjectModel->getTestApiData()
        );

        return $this->request('', 'POST', $data);
    }

    public function testSource(
        string $source,
        TenonProjectModel $tenonProjectModel
    ) {
        $data = array_merge(
            [
                'key' => $this->apiKey,
                'src' => $src,
            ],
            $tenonProjectModel->getTestApiData()
        );

        return $this->request('', 'POST', $data);
    }

    public function testFragment(
        string $fragment,
        TenonProjectModel $tenonProjectModel
    ) {
        $data = array_merge(
            [
                'key' => $this->apiKey,
                'src' => $fragment,
                'fragment' => 1,
            ],
            $tenonProjectModel->getTestApiData()
        );

        return $this->request('', 'POST', $data);
    }
}
