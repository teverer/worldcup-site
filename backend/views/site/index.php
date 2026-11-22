<?php
/**
 * Team: 国足世界杯夺冠队
 * Coding by 李佳婕 2413745, 20261122
 * 模块:框架与团队主页 —— 后台管理首页。
 *
 * @var yii\web\View $this
 */

use common\models\Member;
use common\models\TeamInfo;
use common\models\Work;
use yii\helpers\Html;

$this->title = '后台管理首页';
$this->params['breadcrumbs'][] = $this->title;
$team = TeamInfo::singleton();
?>
<div class="site-index">
    <div class="jumbotron">
        <h1>网站后台管理</h1>
        <p class="lead">
            <?= Html::encode($team->name ?: '世界杯数据门户') ?>
            的内容维护中心：管理团队资料、赛事数据、资讯和留言。
        </p>
    </div>

    <div class="row">
        <div class="col-md-4">
            <div class="card card-hover">
                <div class="card-body">
                    <h2>团队主页</h2>
                    <p>维护团队介绍、成员资料和作业下载文件。</p>
                    <p>成员 <?= (int) Member::find()->count() ?> 人 · 作业文件 <?= (int) Work::find()->count() ?> 个</p>
                    <?= Html::a('团队信息', ['/team-info/index'], ['class' => 'btn btn-primary']) ?>
                    <?= Html::a('成员管理', ['/member/index'], ['class' => 'btn btn-default']) ?>
                    <?= Html::a('作业管理', ['/work/index'], ['class' => 'btn btn-default']) ?>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card card-hover">
                <div class="card-body">
                    <h2>世界杯数据</h2>
                    <p>维护届次、参赛队、名单、比赛和进球记录。</p>
                    <?= Html::a('届次管理', ['/tournament/index'], ['class' => 'btn btn-primary']) ?>
                    <?= Html::a('比赛管理', ['/game/index'], ['class' => 'btn btn-default']) ?>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card card-hover">
                <div class="card-body">
                    <h2>内容与社区</h2>
                    <p>发布资讯并处理球队、球员、比赛等实体下的留言。</p>
                    <?= Html::a('资讯管理', ['/news/index'], ['class' => 'btn btn-primary']) ?>
                    <?= Html::a('留言管理', ['/comment/index'], ['class' => 'btn btn-default']) ?>
                </div>
            </div>
        </div>
    </div>
</div>
