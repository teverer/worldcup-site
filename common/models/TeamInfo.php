<?php
/**
 * Team: 国足世界杯夺冠队
 * Coding by 李佳婕 2413745, 20261122
 * 模块:框架与团队主页 —— 团队介绍单例模型。
 */

namespace common\models;

use common\validators\AssetPathValidator;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;
use yii\db\Expression;

/**
 * @property int $id
 * @property string $name
 * @property string|null $logo
 * @property string|null $slogan
 * @property string|null $intro
 * @property string $create_time
 */
class TeamInfo extends ActiveRecord
{
    /**
     * 后台表单接收图片，不写入数据库。
     *
     * @var \yii\web\UploadedFile|null
     */
    public $logoFile;

    public static function tableName()
    {
        return '{{%team_info}}';
    }

    public function behaviors()
    {
        return [
            [
                'class' => TimestampBehavior::class,
                'createdAtAttribute' => 'create_time',
                'updatedAtAttribute' => false,
                'value' => new Expression('NOW()'),
            ],
        ];
    }

    public function rules()
    {
        return [
            [['name', 'logo', 'slogan', 'intro'], 'trim'],
            [['logo', 'slogan', 'intro'], 'default', 'value' => null],
            [['logo', 'slogan', 'intro'], 'filter', 'filter' => function ($value) {
                return $value === '' ? null : $value;
            }],
            ['name', 'required'],
            ['name', 'string', 'max' => 50],
            ['slogan', 'string', 'max' => 100],
            ['intro', 'string', 'max' => 10000],
            ['logo', 'string', 'max' => 255],
            [
                'logo',
                AssetPathValidator::class,
                'when' => function ($model) {
                    return is_string($model->logo) && $model->logo !== '';
                },
            ],
            [
                'logoFile',
                'file',
                'extensions' => ['jpg', 'jpeg', 'png', 'webp', 'gif', 'avif'],
                'mimeTypes' => ['image/jpeg', 'image/png', 'image/webp', 'image/gif', 'image/avif'],
                'checkExtensionByMimeType' => true,
                'maxSize' => 5 * 1024 * 1024,
                'skipOnEmpty' => true,
            ],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => '编号',
            'name' => '团队名称',
            'logo' => '团队 Logo 路径',
            'logoFile' => '团队 Logo',
            'slogan' => '团队口号',
            'intro' => '团队介绍',
            'create_time' => '创建时间',
        ];
    }

    /**
     * 获取全站唯一的团队介绍记录。
     *
     * @return static
     */
    public static function singleton()
    {
        $model = static::find()->orderBy(['id' => SORT_ASC])->one();
        return $model ?: new static(['id' => 1]);
    }
}
