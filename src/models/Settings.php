<?php

namespace leeroy\awss3assetsversioning\models;

use leeroy\awss3assetsversioning\AwsS3AssetsVersioning;

use Craft;
use craft\base\Model;

class Settings extends Model
{
    // Public Properties
    // =========================================================================

    const KEEP_VERSION = false;

    /**
     * Token duration
     *
     * @var int
     */
    public bool $keepVersion = self::KEEP_VERSION;

    // Public Methods
    // =========================================================================

    /**
     * Returns the validation rules for attributes.
     *
     * Validation rules are used by [[validate()]] to check if attribute values are valid.
     * Child classes may override this method to declare different validation rules.
     *
     * More info: http://www.yiiframework.com/doc-2.0/guide-input-validation.html
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            ['keepVersion', 'boolean'],
            ['keepVersion', 'default', 'value' => self::KEEP_VERSION],
        ];
    }
}