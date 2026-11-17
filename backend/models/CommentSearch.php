<?php
/**
 * Team: 国足世界杯夺冠队
 * Coding by 石子川 2411966, 20261117
 * 模块:留言与资讯 —— 后台留言按内容、用户、目标和状态筛选。
 */

namespace backend\models;

use common\components\CommentTarget;
use common\models\Comment;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use yii\db\Query;

class CommentSearch extends Model
{
    public $q;
    public $targetType;
    public $status;

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['q', 'targetType', 'status'], 'trim'],
            ['q', 'string', 'max' => 100],
            ['targetType', 'in', 'range' => CommentTarget::types()],
            ['status', 'in', 'range' => array_keys(self::statusOptions())],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'q' => '留言内容或用户',
            'targetType' => '留言目标',
            'status' => '留言状态',
        ];
    }

    /**
     * @return array
     */
    public static function statusOptions()
    {
        return [
            Comment::STATUS_ACTIVE => '正常',
            Comment::STATUS_DELETED => '已删除',
        ];
    }

    /**
     * @param array $params
     * @return ActiveDataProvider
     */
    public function search($params)
    {
        $query = Comment::find()
            ->alias('c')
            ->with('user');
        $provider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => ['pageSize' => 20],
            'sort' => [
                'defaultOrder' => ['id' => SORT_DESC],
                'attributes' => ['id', 'target_type', 'status', 'create_time'],
            ],
        ]);

        $this->load($params, '');
        if (!$this->validate()) {
            $query->where('0=1');
            return $provider;
        }

        $query->andFilterWhere(['c.target_type' => $this->targetType]);
        $query->andFilterWhere(['c.status' => $this->status]);
        if ($this->q !== null && $this->q !== '') {
            $userIds = (new Query())
                ->select('id')
                ->from('{{%user}}')
                ->where([
                    'or',
                    ['like', 'username', $this->q],
                    ['like', 'nickname', $this->q],
                ]);
            $query->andWhere([
                'or',
                ['like', 'c.content', $this->q],
                ['c.user_id' => $userIds],
            ]);
        }

        return $provider;
    }
}
