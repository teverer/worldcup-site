<?php
/**
 * Team: 国足世界杯夺冠队
 * Coding by 李行阳 2411353, 20261124
 * 模块:界面设计 —— 首页“最新留言”摘要。
 *
 * 只取仍然有留言簿入口的目标：届次、全站、资讯。
 * 比赛 / 球队 / 球员的本地留言区已移除，不再进入首页摘要。
 */

namespace frontend\models;

use common\components\CommentTarget;
use common\models\Comment;
use common\models\Tournament;
use Yii;
use yii\helpers\Url;

class LatestComments
{
    /**
     * @param int $limit
     * @return array
     */
    public static function latest($limit = 3)
    {
        $limit = max(1, (int) $limit);
        $comments = Comment::findActive()
            ->with('user')
            ->andWhere([
                'target_type' => [
                    CommentTarget::TOURNAMENT,
                    CommentTarget::NEWS,
                    CommentTarget::SITE,
                ],
            ])
            ->orderBy(['create_time' => SORT_DESC, 'id' => SORT_DESC])
            ->limit($limit)
            ->all();

        if ($comments === []) {
            return [];
        }

        $tournamentIds = [];
        foreach ($comments as $comment) {
            if ($comment->target_type === CommentTarget::TOURNAMENT) {
                $tournamentIds[(int) $comment->target_id] = true;
            }
        }

        $tournamentYears = [];
        if ($tournamentIds !== []) {
            $rows = Tournament::find()
                ->select(['id', 'year'])
                ->where(['id' => array_keys($tournamentIds)])
                ->asArray()
                ->all();
            foreach ($rows as $row) {
                $tournamentYears[(int) $row['id']] = (string) $row['year'];
            }
        }

        $items = [];
        foreach ($comments as $comment) {
            $user = $comment->user;
            $author = $user ? ($user->nickname ?: $user->username) : '未知用户';

            $source = '全站';
            if ($comment->target_type === CommentTarget::TOURNAMENT) {
                $source = isset($tournamentYears[(int) $comment->target_id])
                    ? $tournamentYears[(int) $comment->target_id]
                    : '届次';
            } elseif ($comment->target_type === CommentTarget::NEWS) {
                $source = '资讯';
            }

            $route = CommentTarget::route($comment->target_type, (int) $comment->target_id);
            $url = $route ? Url::to($route) : Yii::$app->homeUrl;
            $url .= '#comment-' . (int) $comment->id;

            $content = trim((string) preg_replace('/\s+/u', ' ', (string) $comment->content));
            $excerpt = mb_substr($content, 0, 72, 'UTF-8');
            if (mb_strlen($content, 'UTF-8') > 72) {
                $excerpt .= '…';
            }

            $items[] = [
                'id' => (int) $comment->id,
                'userId' => (int) $comment->user_id,
                'author' => $author,
                'source' => $source,
                'date' => Yii::$app->formatter->asDate($comment->create_time, 'php:m-d'),
                'datetime' => Yii::$app->formatter->asDatetime($comment->create_time, 'php:Y-m-d H:i'),
                'excerpt' => $excerpt,
                'url' => $url,
            ];
        }

        return $items;
    }
}
