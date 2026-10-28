<?php
/**
 * Team: 国足世界杯夺冠队
 * Coding by 石子川 2411966, 20261028
 * 模块:留言与资讯 —— 资讯发布、状态流转和前台发布查询。
 */

namespace common\models;

use common\validators\AssetPathValidator;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;
use yii\db\Expression;
use yii\helpers\HtmlPurifier;

/**
 * @property int $id
 * @property string $title
 * @property string|null $content
 * @property string|null $author
 * @property string|null $cover
 * @property int $status
 * @property string|null $publish_time
 * @property string $create_time
 */
class News extends ActiveRecord
{
    const STATUS_DRAFT = 0;
    const STATUS_PUBLISHED = 1;

    /**
     * 仅用于后台表单接收上传文件，不写入数据库。
     *
     * @var \yii\web\UploadedFile|null
     */
    public $coverFile;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%news}}';
    }

    /**
     * {@inheritdoc}
     */
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

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['title', 'content', 'author', 'cover', 'publish_time'], 'trim'],
            [['content', 'author', 'cover', 'publish_time'], 'default', 'value' => null],
            ['title', 'required'],
            ['title', 'string', 'max' => 100],
            ['content', 'string', 'max' => 20000],
            ['author', 'string', 'max' => 50],
            ['cover', 'string', 'max' => 255],
            [
                'cover',
                AssetPathValidator::class,
                'when' => function ($model) {
                    return is_string($model->cover);
                },
            ],
            [
                'coverFile',
                'file',
                'extensions' => ['jpg', 'jpeg', 'png', 'webp', 'gif'],
                'mimeTypes' => ['image/jpeg', 'image/png', 'image/webp', 'image/gif'],
                'checkExtensionByMimeType' => true,
                'maxSize' => 5 * 1024 * 1024,
                'skipOnEmpty' => true,
            ],
            ['status', 'default', 'value' => self::STATUS_DRAFT],
            ['status', 'required'],
            ['status', 'integer'],
            ['status', 'in', 'range' => [self::STATUS_DRAFT, self::STATUS_PUBLISHED]],
            ['publish_time', 'validatePublishTime'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => '编号',
            'title' => '标题',
            'content' => '正文',
            'author' => '作者',
            'cover' => '封面图路径',
            'coverFile' => '封面图',
            'status' => '状态',
            'publish_time' => '发布时间',
            'create_time' => '创建时间',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function beforeValidate()
    {
        if (!parent::beforeValidate()) {
            return false;
        }
        if ((int) $this->status === self::STATUS_PUBLISHED && empty($this->publish_time)) {
            $this->publish_time = date('Y-m-d H:i:s');
        }
        return true;
    }

    /**
     * @param string $attribute
     */
    public function validatePublishTime($attribute)
    {
        if ($this->$attribute === null || $this->$attribute === '') {
            return;
        }

        $value = $this->$attribute;
        if (!is_string($value)) {
            $this->addError($attribute, '发布时间格式不正确。');
            return;
        }
        $date = \DateTimeImmutable::createFromFormat('!Y-m-d H:i:s', $value);
        $errors = \DateTimeImmutable::getLastErrors();
        if ($date === false
            || $date->format('Y-m-d H:i:s') !== $value
            || (is_array($errors) && ($errors['warning_count'] > 0 || $errors['error_count'] > 0))) {
            $this->addError($attribute, '发布时间格式不正确。');
        }
    }

    /**
     * 资讯正文是后台发布内容，仍需要清理危险标签。
     *
     * {@inheritdoc}
     */
    public function beforeSave($insert)
    {
        if (!parent::beforeSave($insert)) {
            return false;
        }
        if ($this->content !== null) {
            $this->content = HtmlPurifier::process($this->content, [
                'Cache.DefinitionImpl' => null,
            ]);
        }
        return true;
    }

    /**
     * 资讯是多态留言目标，没有数据库外键，删除时需要主动清理。
     *
     * {@inheritdoc}
     */
    public function beforeDelete()
    {
        if (!parent::beforeDelete()) {
            return false;
        }
        Comment::deleteAll([
            'target_type' => \common\components\CommentTarget::NEWS,
            'target_id' => $this->id,
        ]);
        return true;
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public static function findPublished()
    {
        return static::find()
            ->andWhere(['status' => self::STATUS_PUBLISHED])
            ->orderBy(['publish_time' => SORT_DESC, 'id' => SORT_DESC]);
    }

    /**
     * @return array
     */
    public static function statusOptions()
    {
        return [
            self::STATUS_DRAFT => '草稿',
            self::STATUS_PUBLISHED => '已发布',
        ];
    }

    /**
     * @return bool
     */
    public function isPublished()
    {
        return (int) $this->status === self::STATUS_PUBLISHED;
    }
}
