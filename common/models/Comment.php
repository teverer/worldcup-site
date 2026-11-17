<?php
/**
 * Team: 国足世界杯夺冠队
 * Coding by 石子川 2411966, 20261028
 * 模块:留言与资讯 —— 多态留言、回复关系和删除状态。
 */

namespace common\models;

use common\components\CommentTarget;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;
use yii\db\Expression;

/**
 * @property int $id
 * @property int $user_id
 * @property string $content
 * @property string $target_type
 * @property int $target_id
 * @property int|null $parent_id
 * @property int $status
 * @property string $create_time
 */
class Comment extends ActiveRecord
{
    const STATUS_ACTIVE = 0;
    const STATUS_DELETED = 1;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%comment}}';
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
            [['content', 'target_type'], 'trim'],
            ['content', 'required'],
            ['content', 'string', 'max' => 5000],
            ['user_id', 'required'],
            ['user_id', 'integer', 'min' => 1],
            ['user_id', 'exist', 'targetClass' => User::class, 'targetAttribute' => 'id'],
            ['target_type', 'required'],
            ['target_type', 'in', 'range' => CommentTarget::types()],
            ['target_id', 'required'],
            ['target_id', 'integer', 'min' => 1],
            ['target_id', 'validateTarget'],
            ['parent_id', 'default', 'value' => null],
            ['parent_id', 'integer', 'min' => 1],
            ['parent_id', 'validateParent'],
            ['status', 'default', 'value' => self::STATUS_ACTIVE],
            ['status', 'required'],
            ['status', 'integer'],
            ['status', 'in', 'range' => [self::STATUS_ACTIVE, self::STATUS_DELETED]],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => '编号',
            'user_id' => '用户',
            'content' => '留言内容',
            'target_type' => '挂载实体类型',
            'target_id' => '挂载实体',
            'parent_id' => '回复的留言',
            'status' => '状态',
            'create_time' => '留言时间',
        ];
    }

    /**
     * @param string $attribute
     */
    public function validateTarget($attribute)
    {
        if ($this->hasErrors('target_type') || $this->hasErrors('target_id')) {
            return;
        }
        if (!CommentTarget::exists($this->target_type, $this->target_id)) {
            $this->addError($attribute, '留言目标不存在。');
        }
    }

    /**
     * @param string $attribute
     */
    public function validateParent($attribute)
    {
        if ($this->parent_id === null || $this->parent_id === '') {
            return;
        }
        if ($this->hasErrors('parent_id')) {
            return;
        }
        if ($this->hasErrors('target_type') || $this->hasErrors('target_id')) {
            return;
        }

        $parent = static::findOne($this->parent_id);
        if ($parent === null || (int) $parent->status !== self::STATUS_ACTIVE) {
            $this->addError($attribute, '回复的留言不存在或已删除。');
            return;
        }
        if ($parent->target_type !== $this->target_type
            || (int) $parent->target_id !== (int) $this->target_id) {
            $this->addError($attribute, '回复必须属于同一个留言目标。');
            return;
        }
        if ($this->id !== null && (int) $parent->id === (int) $this->id) {
            $this->addError($attribute, '留言不能回复自身。');
            return;
        }

        // 更新父级时阻止形成循环引用。
        $visited = [];
        $current = $parent;
        while ($current !== null && count($visited) < 100) {
            if ($this->id !== null && (int) $current->id === (int) $this->id) {
                $this->addError($attribute, '回复关系不能形成循环。');
                return;
            }
            if (isset($visited[$current->id])) {
                $this->addError($attribute, '原留言回复关系存在循环。');
                return;
            }
            $visited[$current->id] = true;
            $current = $current->parent_id === null ? null : static::findOne($current->parent_id);
        }
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public static function findActive()
    {
        return static::find()->andWhere(['status' => self::STATUS_ACTIVE]);
    }

    /**
     * @param string $targetType
     * @param int $targetId
     * @return \yii\db\ActiveQuery
     */
    public static function findForTarget($targetType, $targetId)
    {
        return static::findActive()
            ->andWhere(['target_type' => $targetType, 'target_id' => $targetId])
            ->orderBy(['create_time' => SORT_ASC, 'id' => SORT_ASC]);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getParent()
    {
        return $this->hasOne(static::class, ['id' => 'parent_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getReplies()
    {
        return $this->hasMany(static::class, ['parent_id' => 'id'])
            ->andWhere(['status' => self::STATUS_ACTIVE])
            ->orderBy(['create_time' => SORT_ASC, 'id' => SORT_ASC]);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getLikes()
    {
        return $this->hasMany(CommentLike::class, ['comment_id' => 'id']);
    }

    /**
     * @param int|null $userId
     * @return bool
     */
    public function isOwnedBy($userId)
    {
        return $userId !== null && (int) $this->user_id === (int) $userId;
    }

    /**
     * @param int|null $userId
     * @return bool
     */
    public function isLikedBy($userId)
    {
        if ($userId === null) {
            return false;
        }
        return CommentLike::find()
            ->where(['comment_id' => $this->id, 'user_id' => $userId])
            ->exists();
    }

    /**
     * 将留言及其全部后代标记为已删除。
     *
     * @return int 受影响行数
     */
    public function softDeleteTree()
    {
        if ((int) $this->status === self::STATUS_DELETED) {
            return 0;
        }

        $ids = [(int) $this->id];
        $frontier = $ids;
        $visited = array_fill_keys($ids, true);
        $depth = 0;

        while ($frontier && $depth < 100) {
            $children = static::find()
                ->select('id')
                ->where(['parent_id' => $frontier])
                ->column();
            $frontier = [];
            foreach ($children as $childId) {
                $childId = (int) $childId;
                if (!isset($visited[$childId])) {
                    $visited[$childId] = true;
                    $ids[] = $childId;
                    $frontier[] = $childId;
                }
            }
            $depth++;
        }

        $transaction = static::getDb()->beginTransaction();
        try {
            $affected = static::updateAll(
                ['status' => self::STATUS_DELETED],
                ['id' => $ids]
            );
            $transaction->commit();
            return $affected;
        } catch (\Throwable $exception) {
            $transaction->rollBack();
            throw $exception;
        }
    }
}
