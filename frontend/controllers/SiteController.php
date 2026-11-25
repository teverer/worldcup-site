<?php
/**
 * Team: 国足世界杯夺冠队
 * Coding by 李行阳 2411353, 20261122
 * 模块:界面设计 —— 前台站点入口与根页
 */
namespace frontend\controllers;

use frontend\models\EditionWall;
use Yii;
use yii\helpers\Url;
use yii\web\Controller;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use common\models\LoginForm;
use frontend\models\SignupForm;

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
                'only' => ['logout', 'signup'],
                'rules' => [
                    [
                        'actions' => ['signup'],
                        'allow' => true,
                        'roles' => ['?'],
                    ],
                    [
                        'actions' => ['logout'],
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
     * 根页:夜场记分牌墙。
     *
     * 一次取回全部届次与全站概览,视图只按固定结构渲染。
     *
     * @return string
     */
    public function actionIndex()
    {
        $wall = EditionWall::all();

        return $this->render('index', [
            'editions' => $wall['editions'],
        ]);
    }

    /**
     * 旧的全站留言簿地址：留言区已并入首页收场，301 回首页 #comments。
     *
     * @return \yii\web\Response
     */
    public function actionDiscussion()
    {
        return $this->redirect(['/site/index', '#' => 'comments'], 301);
    }

    /**
     * Logs in a user.
     *
     * @return mixed
     */
    public function actionLogin()
    {
        if (!Yii::$app->user->isGuest) {
            return $this->goHome();
        }

        $model = new LoginForm();
        if ($model->load(Yii::$app->request->post()) && $model->login()) {
            // 从留言簿登录后回到原来的记录附近。
            $returnUrl = $this->safeReturnUrl();
            if ($returnUrl !== null) {
                return $this->redirect($returnUrl);
            }
            return $this->goBack();
        } else {
            $model->password = '';

            return $this->render('login', [
                'model' => $model,
            ]);
        }
    }

    /**
     * Logs out the current user.
     *
     * @return mixed
     */
    public function actionLogout()
    {
        Yii::$app->user->logout();

        return $this->goHome();
    }

    /**
     * Displays about page.
     *
     * @return mixed
     */
    public function actionAbout()
    {
        return $this->redirect(['/team-info/index']);
    }

    /**
     * Signs user up.
     *
     * @return mixed
     */
    public function actionSignup()
    {
        $model = new SignupForm();
        if ($model->load(Yii::$app->request->post()) && $model->signup()) {
            Yii::$app->session->setFlash('success', '注册成功,请登录。');
            // 从留言簿来注册的，登录后回到同一页。
            $returnUrl = $this->safeReturnUrl();
            if ($returnUrl !== null) {
                return $this->redirect(['/site/login', 'returnUrl' => $returnUrl]);
            }
            return $this->goHome();
        }

        return $this->render('signup', [
            'model' => $model,
        ]);
    }

    /**
     * 只接受站内相对地址，避免登录 / 注册后的开放跳转。
     *
     * @return string|null
     */
    private function safeReturnUrl()
    {
        $returnUrl = Yii::$app->request->get('returnUrl');
        if (is_string($returnUrl)
            && $returnUrl !== ''
            && $returnUrl[0] === '/'
            && strpos($returnUrl, '\\') === false
            && Url::isRelative($returnUrl)) {
            return $returnUrl;
        }
        return null;
    }
}
