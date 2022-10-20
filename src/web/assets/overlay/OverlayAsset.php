<?php
namespace osim\craft\tenon\web\assets\overlay;

use craft\web\AssetBundle;

class OverlayAsset extends AssetBundle
{
    public function init(): void
    {
        $this->sourcePath = '@osim/craft/tenon/web/assets/overlay/dist';

        $this->js = [
            'js/index.js',
        ];

        $this->css = [
            'css/index.css',
        ];

        parent::init();
    }
}
