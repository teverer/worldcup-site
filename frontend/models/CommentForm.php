<?php
/**
 * Team: 国足世界杯夺冠队
 * Coding by 石子川 2411966, 20261108
 * 模块:留言与资讯 —— 前台留言、回复输入校验与模型创建。
 */

namespace frontend\models;

use common\components\CommentTarget;
use common\models\Comment;
use common\models\News;
use yii\base\Model;

class CommentForm extends Model
{
    public $targetType;
    public $targetId;
    public $parentId;
    public $content;

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['targetType', 'content'], 'trim'],
            ['content', 'required'],
            ['content', 'string', 'max' => 5000],
            ['targetType', 'required'],
            ['targetType', 'in', 'range' => CommentTarget::types()],
            ['targetId', 'required'],
            ['targetId', 'integer', 'min' => 1],
            ['targetId', 'validateTarget'],
            ['parentId', 'default', 'value' => null],
            ['parentId', 'integer', 'min' => 1],
            ['parentId', 'validateParent'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'content' => '留言内容',
            'targetType' => '留言目标类型',
            'targetId' => '留言目标',
            'parentId' => '回复的留言',
        ];
    }

    /**
     * @param string $attribute
     */
    public function validateTarget($attribute)
    {
        if ($this->hasErrors('targetType') || $this->hasErrors('targetId')) {
            return;
        }

        $exists = CommentTarget::exists($this->targetType, $this->targetId);
        if ($exists && $this->targetType === CommentTarget::NEWS) {
            $exists = News::findPublished()
                ->andWhere(['id' => (int) $this->targetId])
                ->exists();
        }
        if (!$exists) {
            $this->addError($attribute, '留言目标不存在或尚未发布。');
        }
    }

    /**
     * @param string $attribute
     */
    public function validateParent($attribute)
    {
        if ($this->parentId === null || $this->parentId === '') {
            return;
        }
        if ($this->hasErrors('parentId') || $this->hasErrors('targetType') || $this->hasErrors('targetId')) {
            return;
        }

        $parent = Comment::findActive()->andWhere(['id' => (int) $this->parentId])->one();
        if ($parent === null) {
            $this->addError($attribute, '回复的留言不存在或已删除。');
            return;
        }
        if ($parent->target_type !== $this->targetType
            || (int) $parent->target_id !== (int) $this->targetId) {
            $this->addError($attribute, '回复必须属于同一个留言目标。');
        }
    }

    /**
     * @param int $userId
     * @return Comment|null
     */
    public function createComment($userId)
    {
        $comment = new Comment([
            'user_id' => (int) $userId,
            'content' => $this->content,
            'target_type' => $this->targetType,
            'target_id' => (int) $this->targetId,
            'parent_id' => $this->parentId === null || $this->parentId === ''
                ? null
                : (int) $this->parentId,
        ]);

        if ($comment->save()) {
            return $comment;
        }

        foreach ($comment->getErrors() as $attribute => $errors) {
            $formAttribute = $attribute === 'parent_id'
                ? 'parentId'
                : ($attribute === 'target_type' ? 'targetType' : $attribute);
            foreach ($errors as $error) {
                $this->addError($formAttribute, $error);
            }
        }
        return null;
    }
}
