<?php
/**
 * Team: 国足世界杯夺冠队
 * Coding by 李佳婕 2413745, 20261122
 * 模块:框架与团队主页 —— 团队成员模型。
 */

namespace common\models;

use common\validators\AssetPathValidator;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;
use yii\db\Expression;

/**
 * @property int $id
 * @property string $name
 * @property string|null $student_id
 * @property string|null $role
 * @property string|null $duty
 * @property string|null $avatar
 * @property string|null $intro
 * @property string $create_time
 */
class Member extends ActiveRecord
{
    const ROLE_LEADER = '组长';
    const ROLE_MEMBER = '组员';

    /**
     * 后台表单接收照片，不写入数据库。
     *
     * @var \yii\web\UploadedFile|null
     */
    public $avatarFile;

    public static function tableName()
    {
        return '{{%member}}';
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
            [['name', 'student_id', 'role', 'duty', 'avatar', 'intro'], 'trim'],
            [['student_id', 'avatar', 'intro'], 'default', 'value' => null],
            [['student_id', 'avatar', 'intro'], 'filter', 'filter' => function ($value) {
                return $value === '' ? null : $value;
            }],
            [['name', 'role', 'duty'], 'required'],
            ['name', 'string', 'max' => 50],
            ['student_id', 'string', 'max' => 20],
            ['student_id', 'unique'],
            ['role', 'in', 'range' => [self::ROLE_LEADER, self::ROLE_MEMBER]],
            ['duty', 'string', 'max' => 100],
            ['intro', 'string', 'max' => 10000],
            ['avatar', 'string', 'max' => 255],
            [
                'avatar',
                AssetPathValidator::class,
                'when' => function ($model) {
                    return is_string($model->avatar) && $model->avatar !== '';
                },
            ],
            [
                'avatarFile',
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
            'name' => '姓名',
            'student_id' => '学号',
            'role' => '团队身份',
            'duty' => '负责内容',
            'avatar' => '照片路径',
            'avatarFile' => '成员照片',
            'intro' => '个人简介',
            'create_time' => '创建时间',
        ];
    }

    public static function roleOptions()
    {
        return [
            self::ROLE_LEADER => self::ROLE_LEADER,
            self::ROLE_MEMBER => self::ROLE_MEMBER,
        ];
    }
}
