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

    // TODO - ADD MORE COMMENTS

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

                    if ($newFileName !== $oldFileName) {
                        $newFileName = explode('}', pathinfo($newFileName, PATHINFO_FILENAME));
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
                'Key' => $filepath,
            ]);

            $content = "";

//            print_r('<pre>');
//            print_r($file_version);
//            print_r('</pre>');
//            die();

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
     * @param string $filename
     * @param S3Client $aws
     * @return string
     */
    private function _listVersions(array $versions, int|string $folderId, string $filepath, string $filename, S3Client $aws): string
    {
        $versionsList = '';

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

                $asset = '<img src="'. $file['@metadata']['effectiveUri'] .'" alt="">';
                if (str_contains($file['@metadata']['effectiveUri'], '.pdf')) {
                    $asset = '<svg xmlns="http://www.w3.org/2000/svg" shape-rendering="geometricPrecision" text-rendering="geometricPrecision" image-rendering="optimizeQuality" fill-rule="evenodd" clip-rule="evenodd" viewBox="0 0 399 511.66"><path fill-rule="nonzero" d="M71.1 0h190.92c5.22 0 9.85 2.5 12.77 6.38L394.7 136.11c2.81 3.05 4.21 6.92 4.21 10.78l.09 293.67c0 19.47-8.02 37.23-20.9 50.14l-.09.08c-12.9 12.87-30.66 20.88-50.11 20.88H71.1c-19.54 0-37.33-8.01-50.22-20.9C8.01 477.89 0 460.1 0 440.56V71.1c0-19.56 8-37.35 20.87-50.23C33.75 8 51.54 0 71.1 0zm45.78 254.04c-8.81 0-15.96-7.15-15.96-15.95 0-8.81 7.15-15.96 15.96-15.96h165.23c8.81 0 15.96 7.15 15.96 15.96 0 8.8-7.15 15.95-15.96 15.95H116.88zm0 79.38c-8.81 0-15.96-7.15-15.96-15.96 0-8.8 7.15-15.95 15.96-15.95h156.47c8.81 0 15.96 7.15 15.96 15.95 0 8.81-7.15 15.96-15.96 15.96H116.88zm0 79.39c-8.81 0-15.96-7.15-15.96-15.96s7.15-15.95 15.96-15.95h132.7c8.81 0 15.95 7.14 15.95 15.95 0 8.81-7.14 15.96-15.95 15.96h-132.7zm154.2-363.67v54.21c1.07 13.59 5.77 24.22 13.99 31.24 8.63 7.37 21.65 11.52 38.95 11.83l36.93-.05-89.87-97.23zm96.01 129.11-43.31-.05c-25.2-.4-45.08-7.2-59.39-19.43-14.91-12.76-23.34-30.81-25.07-53.11l-.15-2.22V31.91H71.1c-10.77 0-20.58 4.42-27.68 11.51-7.09 7.1-11.51 16.91-11.51 27.68v369.46c0 10.76 4.43 20.56 11.52 27.65 7.11 7.12 16.92 11.53 27.67 11.53h256.8c10.78 0 20.58-4.4 27.65-11.48 7.13-7.12 11.54-16.93 11.54-27.7V178.25z"/></svg>';
                }

                $versionsList .= '
                    <li role="option">
                        <a href="/admin/change-version?filename='. $filename .'&VersionId='. $version['VersionId'] .'&folderId='. $folderId . '">
                            '. $asset .'
                            <span class="smalltext">
                                '. date('Y-m-d H:i:s', $date) .' - '. $filesize . $filesizeFormat .'
                            </span>
                        </a>
                    </li>
                ';
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
