<?php

namespace leeroy\awss3assetsversioning;

use Craft;
use Aws\S3\S3Client;
use craft\base\Element;
use craft\elements\Asset;
use craft\events\DefineHtmlEvent;
use craft\events\ModelEvent;
use craft\events\RegisterUrlRulesEvent;
use craft\events\TemplateEvent;
use craft\web\UrlManager;
use craft\web\View;
use leeroy\awss3assetsversioning\assetbundles\PluginAsset;
use leeroy\awss3assetsversioning\models\Settings;
use yii\base\Event;
use yii\base\InvalidConfigException;
use craft\base\Plugin;

class AwsS3AssetsVersioning extends Plugin
{
    public static $plugin;

    /**
     * To execute your plugin’s migrations, you’ll need to increase its schema version.
     *
     * @var string
     */
    public string $schemaVersion = '1.0.0';

    /**
     * Set to `true` if the plugin should have a settings view in the control panel.
     *
     * @var bool
     */
    public bool $hasCpSettings = true;

    /**
     * Set to `true` if the plugin should have its own section (main nav item) in the control panel.
     *
     * @var bool
     */
    public bool $hasCpSection = false;

    /**
     * Initializes the module.
     */
    public function init(): void
    {
        parent::init();
        self::$plugin = $this;

        if (Craft::$app->getRequest()->getIsCpRequest()) {
            Event::on(
                View::class,
                View::EVENT_BEFORE_RENDER_TEMPLATE,
                function (TemplateEvent $event) {
                    try {
                        Craft::$app->getView()->registerAssetBundle(PluginAsset::class);
                    } catch (InvalidConfigException $e) {
                        Craft::error(
                            'Error registering AssetBundle - '.$e->getMessage(),
                            __METHOD__
                        );
                    }
                }
            );
        }

        // Register our CP routes
        Event::on(
            UrlManager::class,
            UrlManager::EVENT_REGISTER_CP_URL_RULES,
            function (RegisterUrlRulesEvent $event) {
                $event->rules['change-version'] = 'aws-s3-assets-versioning/admin/change-version';
                $event->rules['version'] = 'aws-s3-assets-versioning/admin/version';
            }
        );

        Event::on(
            Asset::class,
            Element::EVENT_BEFORE_SAVE,
            function (ModelEvent $e)
            {
                if ($e->sender->newLocation) {
                    $newFileName = $e->sender->newLocation;
                    $newFileExt = pathinfo($newFileName, PATHINFO_EXTENSION);
                    $oldFileName = $e->sender->filename;
                    $oldFileExt = pathinfo($e->sender->filename, PATHINFO_EXTENSION);

                    if ($newFileExt !== $oldFileExt) {
                        throw new \Exception("File extension must be the same as the original file");
                        exit();
                    }

                    $newFileName = explode('}', $newFileName);

                    if ($newFileName[1] !== $oldFileName) {
                        $newNewFileName = str_replace($newFileName[1], pathinfo($oldFileName, PATHINFO_FILENAME), $e->sender->tempFilePath);
                        $e->sender->newLocation = $newFileName[0] . '}' . $oldFileName;
                        rename($e->sender->tempFilePath, $newNewFileName);
                        $e->sender->tempFilePath = $newNewFileName;
                    }
                }
            }
        );

        Event::on(Asset::class, Element::EVENT_DEFINE_META_FIELDS_HTML, function(DefineHtmlEvent $e) {
            $e->html = include('templates/_partials/warning.php');
        });

        Event::on(Asset::class, Element::EVENT_DEFINE_SIDEBAR_HTML, function(DefineHtmlEvent $e) {
            if (isset($_GET['revert']) && $_GET['revert'] === "success") {
                Craft::$app->getSession()->setNotice('Success');
            }

            $config = [
                'version' => 'latest',
                'credentials' => [
                    'key' => getenv('S3_KEY_ID'),
                    'secret' => getenv('S3_SECRET')
                ],
                'region' => getenv('S3_REGION')
            ];

            $aws = new S3Client($config);
            $filename = $e->sender->filename;
            $filepath = $e->sender->path;

            $file_version = $aws->listObjectVersions([
                'Bucket' => getenv('S3_BUCKET'),
                'MaxKeys' => 500,
                'KeyMarker' => preg_replace('/\.\w+$/', '', $filepath),
            ]);

            $content = "";

            if (in_array($filepath, array_column($file_version->get('Versions'), 'Key'), true)) {
                $content = $this->_listVersions($file_version->get('Versions'), $e->sender->folderId, $filepath, $filename, $aws);
            }

            if ($content) {
                $e->html .= include('templates/_partials/revision-group.php');
            }
        });
    }

    /**
     *
     * Return formatted list of versions
     *
     * @param array $versions
     * @param int|string $folderId
     * @param string $filepath
     * @param string $filename
     * @param S3Client $aws
     * @return string
     */
    private function _listVersions(array $versions, int|string $folderId, string $filepath, string $filename, S3Client $aws): string
    {
        $versionsList = '';

        $count = count(array_filter($versions,function($version) use ($filepath){
            if($version['Key'] === $filepath && !$version['IsLatest']){
                return $version;
            }
        }));

        foreach ($versions as $key => $version) {
            if ($version['Key'] === $filepath && !$version['IsLatest']) {
                $file = $aws->getObject([
                    'Bucket' => getenv('S3_BUCKET'),
                    'Key' => $filepath,
                    'VersionId' => $version['VersionId']
                ]);

                $filesizeFormat = ' KB';
                $filesizeLimit  = 1000;

                if ($file['ContentLength'] >= 1000000) {
                    $filesizeFormat = ' MB';
                    $filesizeLimit  = 1000000;
                }

                $filesize = round($file['ContentLength'] / $filesizeLimit);

                $date = strtotime($file['LastModified']->__toString());

                $versionsList .= '
                    <li role="option">
                        <a href="/admin/version?filename='. $filename .'&filepath='. $filepath .'&VersionId='. $version['VersionId'] .'&folderId='. $folderId . '">
                            <p>'. Craft::t('aws-s3-assets-versioning', 'Admin:Revision') . ' ' . $count .' - '. $filesize . $filesizeFormat .'</p>
                            <span class="smalltext">
                                '. Craft::t('aws-s3-assets-versioning', 'Admin:Saved') . ' ' . date('Y-m-d H:i:s', $date) .'
                            </span>
                        </a>
                    </li>
                ';

                $count--;
            }
        }

        return $versionsList;
    }

    // Protected Methods
    // =========================================================================

    /**
     * Creates and returns the model used to store the plugin’s settings.
     *
     * @return Settings
     */
    protected function createSettingsModel(): Settings
    {
        return new Settings();
    }

    /**
     * Returns the rendered settings HTML, which will be inserted into the content
     * block on the settings page.
     *
     * @return string The rendered settings HTML
     */
    protected function settingsHtml(): ?string
    {
        return Craft::$app->view->renderTemplate(
            'aws-s3-assets-versioning/settings',
            [
                'settings' => $this->getSettings()
            ]
        );
    }
}
