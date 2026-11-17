<?php
/**
 * Team: 国足世界杯夺冠队
 * Coding by 石子川 2411966, 20261028
 * 模块:留言与资讯 —— 登录用户对有效留言点赞。
 */

namespace common\models;

use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;
use yii\db\Expression;

/**
 * @property int $id
 * @property int $comment_id
 * @property int $user_id
 * @property string $create_time
 */
class CommentLike extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%comment_like}}';
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
            [['comment_id', 'user_id'], 'required'],
            [['comment_id', 'user_id'], 'integer', 'min' => 1],
            ['comment_id', 'exist', 'targetClass' => Comment::class, 'targetAttribute' => 'id'],
            ['comment_id', 'validateComment'],
            ['user_id', 'exist', 'targetClass' => User::class, 'targetAttribute' => 'id'],
            [['comment_id', 'user_id'], 'unique', 'targetAttribute' => ['comment_id', 'user_id'],
                'message' => '你已经点赞过这条留言。'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => '编号',
            'comment_id' => '留言',
            'user_id' => '用户',
            'create_time' => '点赞时间',
        ];
    }

    /**
     * @param string $attribute
     */
    public function validateComment($attribute)
    {
        if ($this->hasErrors('comment_id')) {
            return;
        }
        $comment = Comment::findOne($this->comment_id);
        if ($comment === null || (int) $comment->status !== Comment::STATUS_ACTIVE) {
            $this->addError($attribute, '已删除或不存在的留言不能点赞。');
        }
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getComment()
    {
        return $this->hasOne(Comment::class, ['id' => 'comment_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }
}
