<?php
namespace leeroy\awss3assetsversioning\src\assetbundles;

use Craft;
use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;

/**
 * @author    Antoine Chouinard
 * @package   Axis Module
 * @since     1.0.0
 */
class AxisModuleAsset extends AssetBundle
{
    // Public Methods
    // =========================================================================

    /**
     * Initializes the bundle.
     */
    public function init()
    {
        // define the path that your publishable resources live
        $this->sourcePath = "@assetbundles/assets";

        // define the dependencies
        $this->depends = [
            CpAsset::class,
        ];

        $this->js = [
            'js/admin.js'
        ];

        $this->css = [
            'css/style.css'
        ];

        parent::init();
    }
}
