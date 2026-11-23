<?php
/**
 * Team: 国足世界杯夺冠队
 * Coding by 李佳婕 2413745, 20261122
 * 模块:框架与团队主页 —— 作业文件模型与安全路径解析。
 */

namespace common\models;

use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;
use yii\db\Expression;

/**
 * @property int $id
 * @property string $title
 * @property string|null $type
 * @property string $file_path
 * @property string|null $description
 * @property string $create_time
 */
class Work extends ActiveRecord
{
    const TYPE_TEAM = '团队';
    const TYPE_PERSONAL = '个人';

    /**
     * 后台表单接收上传文件，不写入数据库。
     *
     * @var \yii\web\UploadedFile|null
     */
    public $workFile;

    public static function tableName()
    {
        return '{{%work}}';
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
            [['title', 'type', 'file_path', 'description'], 'trim'],
            ['description', 'default', 'value' => null],
            ['type', 'default', 'value' => self::TYPE_TEAM],
            [['title', 'type'], 'required'],
            ['title', 'string', 'max' => 100],
            ['type', 'in', 'range' => [self::TYPE_TEAM, self::TYPE_PERSONAL]],
            ['description', 'string', 'max' => 255],
            ['file_path', 'string', 'max' => 255],
            ['file_path', 'validateStoredFile'],
            ['workFile', 'validateFilePresence'],
            [
                'workFile',
                'file',
                'extensions' => [
                    'pdf', 'doc', 'docx', 'ppt', 'pptx', 'xls', 'xlsx',
                    'zip', 'rar', '7z', 'txt', 'md',
                ],
                'maxSize' => 20 * 1024 * 1024,
                'skipOnEmpty' => true,
            ],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => '编号',
            'title' => '作业名称',
            'type' => '类型',
            'file_path' => '文件路径',
            'workFile' => '作业文件',
            'description' => '说明',
            'create_time' => '上传时间',
        ];
    }

    public function validateFilePresence($attribute)
    {
        if ($this->isNewRecord && $this->$attribute === null && $this->file_path === '') {
            $this->addError($attribute, '请选择要上传的作业文件。');
        }
    }

    public function validateStoredFile($attribute)
    {
        if ($this->workFile !== null || $this->$attribute === null || $this->$attribute === '') {
            return;
        }
        if ($this->resolveFilePath() === null) {
            $this->addError($attribute, '文件路径不合法或文件不存在。');
        }
    }

    public static function typeOptions()
    {
        return [
            self::TYPE_TEAM => self::TYPE_TEAM,
            self::TYPE_PERSONAL => self::TYPE_PERSONAL,
        ];
    }

    public static function storageRoot()
    {
        return dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR . 'data';
    }

    /**
     * 返回允许下载的真实文件路径，拒绝绝对路径和目录穿越。
     *
     * @return string|null
     */
    public function resolveFilePath()
    {
        if (!is_string($this->file_path)
            || !preg_match('~^(team|personal)/[A-Za-z0-9][A-Za-z0-9._-]*$~D', $this->file_path)) {
            return null;
        }

        $root = realpath(self::storageRoot());
        $path = realpath(self::storageRoot() . DIRECTORY_SEPARATOR . $this->file_path);
        if ($root === false || $path === false || !is_file($path)) {
            return null;
        }

        return strpos($path, $root . DIRECTORY_SEPARATOR) === 0 ? $path : null;
    }

    public function downloadName()
    {
        if (!is_string($this->file_path)
            || !preg_match('~^(team|personal)/[A-Za-z0-9][A-Za-z0-9._-]*$~D', $this->file_path)) {
            return 'work-download';
        }

        $extension = pathinfo($this->file_path, PATHINFO_EXTENSION);
        $base = preg_replace('/[\\\\\\/:*?"<>|\\x00-\\x1F]+/u', '-', trim($this->title));
        $base = $base !== '' ? $base : 'work-download';
        return $extension !== '' ? $base . '.' . $extension : $base;
    }

    public function fileSize()
    {
        $path = $this->resolveFilePath();
        return $path === null ? null : filesize($path);
    }

    public function beforeDelete()
    {
        if (!parent::beforeDelete()) {
            return false;
        }
        $path = $this->resolveFilePath();
        if ($path !== null) {
            @unlink($path);
        }
        return true;
    }
}
