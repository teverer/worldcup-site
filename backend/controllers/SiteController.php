<?php
/**
 * Team: 国足世界杯夺冠队
 * Coding by 李行阳 2411353, 20261123
 * 模块:界面设计 —— 后台入口、登录与工作台概览。
 */

namespace backend\controllers;

use Yii;
use backend\models\LoginForm;
use common\models\Comment;
use common\models\Game;
use common\models\Goal;
use common\models\Member;
use common\models\News;
use common\models\Player;
use common\models\Team;
use common\models\Tournament;
use common\models\Work;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\Controller;

/**
 * Site controller
 */
class SiteController extends Controller
{
    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'rules' => [
                    [
                        'actions' => ['login', 'error'],
                        'allow' => true,
                    ],
                    [
                        'actions' => ['logout', 'index'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'logout' => ['post'],
                ],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function actions()
    {
        return [
            'error' => [
                'class' => 'yii\web\ErrorAction',
            ],
        ];
    }

    /**
     * Displays homepage.
     *
     * @return string
     */
    public function actionIndex()
    {
        $counts = [
            'tournament' => (int) Tournament::find()->count(),
            'team' => (int) Team::find()->count(),
            'player' => (int) Player::find()->count(),
            'game' => (int) Game::find()->count(),
            'goal' => (int) Goal::find()->count(),
            'news' => (int) News::find()->count(),
            'comment' => (int) Comment::find()->count(),
            'member' => (int) Member::find()->count(),
            'work' => (int) Work::find()->count(),
        ];

        $playersMissingPosition = (int) Player::find()
            ->where(['or', ['position' => null], ['position' => '']])
            ->count();
        $membersMissingIntro = (int) Member::find()
            ->where(['or', ['intro' => null], ['intro' => '']])
            ->count();

        // 待补清单只放「有明确动作、能点进去补」的项;没有缺口时显示下一步建议,不留空白。
        $gaps = [];
        if ($counts['work'] === 0) {
            $gaps[] = [
                'title' => '作业文件还没有上传',
                'detail' => '课程要求提交需求、设计、实现、用户手册、部署文档与展示 PPT。',
                'url' => ['/work/index'],
                'action' => '上传文件',
            ];
        }
        if ($membersMissingIntro > 0) {
            $gaps[] = [
                'title' => $membersMissingIntro . ' 位成员没有个人简介',
                'detail' => '团队页会显示「暂无个人简介」,补一句分工或方向即可。',
                'url' => ['/member/index'],
                'action' => '补简介',
            ];
        }
        if ($playersMissingPosition > 0) {
            $gaps[] = [
                'title' => $playersMissingPosition . ' 名球员缺少位置',
                'detail' => '球员页与阵容表按位置排序,缺失会排到最后。',
                'url' => ['/player/index'],
                'action' => '去补位置',
            ];
        }
        if ($counts['news'] < 6) {
            $gaps[] = [
                'title' => '资讯只有 ' . $counts['news'] . ' 篇',
                'detail' => '用户可以按标题或作者检索,建议先补赛事回顾与数据观察。',
                'url' => ['/news/create'],
                'action' => '写资讯',
            ];
        }

        return $this->render('index', [
            'counts' => $counts,
            'gaps' => $gaps,
            'today' => date('Y-m-d'),
        ]);
    }

    /**
     * Login action.
     *
     * @return string
     */
    public function actionLogin()
    {
        if (!Yii::$app->user->isGuest) {
            return $this->goHome();
        }

        $model = new LoginForm();
        if ($model->load(Yii::$app->request->post()) && $model->login()) {
            return $this->goBack();
        } else {
            $model->password = '';

            return $this->render('login', [
                'model' => $model,
            ]);
        }
    }

    /**
     * Logout action.
     *
     * @return string
     */
    public function actionLogout()
    {
        Yii::$app->user->logout();

        return $this->goHome();
    }
}
