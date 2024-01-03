<?php
namespace leeroy\awss3assetsversioning\src\controllers;

use Craft;
use craft\awss3\S3Client;
use craft\commerce\elements\Product;
use craft\elements\Asset;
use craft\elements\Category;
use craft\errors\ElementNotFoundException;
use craft\helpers\StringHelper;
use craft\web\Controller;
use JetBrains\PhpStorm\NoReturn;
use Throwable;
use yii\base\Exception;

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

    /**
     *
     *
     * @return void
     * @throws Throwable
     */
    #[NoReturn] public function actionChangeVersion():void
    {
        $filename = $_GET['filename'];
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
            'Key' => $filename,
            'VersionId' => $versionId
        ]);
        $filePath = __DIR__ . '/temp/' . $filename;

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

        $aws->deleteObject([
            'Bucket' => getenv('S3_BUCKET'),
            'Key' => $filename,
            'VersionId' => $versionId
        ]);

        header("Location: " . $asset->cpEditUrl);
        exit();
    }
}
