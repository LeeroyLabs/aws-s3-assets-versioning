<?php
namespace leeroy\awss3assetsversioning\assetbundles;

use Craft;
use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;

/**
 * @author    Antoine Chouinard
 * @package   Axis Module
 * @since     1.0.0
 */
class PluginAsset extends AssetBundle
{
    // Public Methods
    // =========================================================================

    /**
     * Initializes the bundle.
     */
    public function init()
    {
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
