<?php
namespace leeroy\awss3assetsversioning\controllers;

use Craft;
use craft\awss3\S3Client;
use craft\commerce\elements\Product;
use craft\elements\Asset;
use craft\elements\Category;
use craft\errors\ElementNotFoundException;
use craft\helpers\StringHelper;
use craft\web\Controller;
use JetBrains\PhpStorm\NoReturn;
use leeroy\awss3assetsversioning\AwsS3AssetsVersioning;
use leeroy\awss3assetsversioning\models\Settings;
use Throwable;
use yii\base\Exception;
use yii\web\Response;

/**
 * @author    Antoine Chouinard
 * @package   AxisModule
 * @since     1.0.0
 */
class AdminController extends Controller
{
    /**
     * @var    bool|array Allows anonymous access to this controller's actions.
     *         The actions must be in 'kebab-case'
     * @access protected
     */
    protected array|bool|int $allowAnonymous = [];

    public function actionVersion():Response
    {
        $filename = $_GET['filename'];
        $versionId = $_GET['VersionId'];
        $folderId = $_GET['folderId'];
        $filepath = $_GET['filepath'];

        $config = [
            'version' => 'latest',
            'credentials' => [
                'key' => getenv('S3_KEY_ID'),
                'secret' => getenv('S3_SECRET')
            ],
            'region' => getenv('S3_REGION')
        ];

        $aws = new S3Client($config);

        $file = $aws->getObject([
            'Bucket' => getenv('S3_BUCKET'),
            'Key' => $filepath,
            'VersionId' => $versionId
        ]);

        $file_version = $aws->listObjectVersions([
            'Bucket' => getenv('S3_BUCKET'),
            'MaxKeys' => 500,
            'KeyMarker' => preg_replace('/\.\w+$/', '', $filepath),
        ]);

        $asset = Asset::find()
            ->filename($filename)
            ->one();

        $versions = AwsS3AssetsVersioning::listVersions($file_version->get('Versions'), $folderId, $filepath, $filename, $aws, $versionId);

        return $this->renderTemplate('aws-s3-assets-versioning/version', [
            'filename' => $filename,
            'filepath' => $filepath,
            'VersionId' => $versionId,
            'versions' => $versions,
            'asset' => $asset,
            'folderId' => $folderId,
            'file' => $file
        ]);
    }

    /**
     *
     *
     * @return void
     * @throws Throwable
     */
    public function actionChangeVersion():void
    {
        $filename = $_GET['filename'];
        $filepath = $_GET['filepath'];
        $versionId = $_GET['VersionId'];
        $folderId = $_GET['folderId'];

        $config = [
            'version' => 'latest',
            'credentials' => [
                'key' => getenv('S3_KEY_ID'),
                'secret' => getenv('S3_SECRET')
            ],
            'region' => getenv('S3_REGION')
        ];

        $aws = new S3Client($config);

        $file = $aws->getObject([
            'Bucket' => getenv('S3_BUCKET'),
            'Key' => $filepath,
            'VersionId' => $versionId
        ]);
        $filePath = Craft::$app->path->getTempAssetUploadsPath() . '/' . $filename;

        header("Content-Type: {$file['ContentType']}");
        file_put_contents($filePath, $file['Body']);

        $volume = Craft::$app->getVolumes()->getVolumeByHandle(getenv('VOLUME_NAME'));

        $asset = Asset::find()
            ->filename($filename)
            ->one();

        $asset->filename = $filename;
        $asset->tempFilePath = $filePath;
        $asset->setVolumeId($volume->id);
        $asset->newFolderId = $folderId;
        $asset->avoidFilenameConflicts = false;
        $asset->setScenario(Asset::SCENARIO_REPLACE);

        Craft::$app->elements->saveElement($asset);

        if (AwsS3AssetsVersioning::$plugin->settings->keepVersion) {
            $aws->deleteObject([
                'Bucket' => getenv('S3_BUCKET'),
                'Key' => $filename,
                'VersionId' => $versionId
            ]);
        }

        header("Location: " . $asset->cpEditUrl . '&revert=success');
        exit();
    }
}
