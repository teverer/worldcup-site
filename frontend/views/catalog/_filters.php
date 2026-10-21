<?php
/**
 * Team: 国足世界杯夺冠队
 * Coding by Codex (AI assistance), 20261021
 * 模块:球队与球员 —— 共用搜索表单。
 * @var yii\web\View $this
 * @var yii\base\Model $searchModel
 * @var array $teams
 */
use common\models\Team;
use common\models\TeamSearch;
use yii\helpers\Html;
?>
<?= Html::errorSummary($searchModel, ['class' => 'alert alert-danger']) ?>
<?= Html::beginForm(['/' . $this->context->id . '/index'], 'get', ['class' => 'catalog-filters']) ?>
<div class="form-group">
    <?= Html::activeLabel($searchModel, 'q', ['for' => 'catalog-q']) ?>
    <?= Html::activeTextInput($searchModel, 'q', ['name' => 'q', 'id' => 'catalog-q', 'class' => 'form-control', 'maxlength' => 100]) ?>
</div>
<?php if ($searchModel instanceof TeamSearch): ?>
<div class="form-group">
    <?= Html::activeLabel($searchModel, 'continent', ['for' => 'catalog-continent']) ?>
    <?= Html::activeDropDownList($searchModel, 'continent', array_combine(Team::CONTINENTS, Team::CONTINENTS),
        ['name' => 'continent', 'id' => 'catalog-continent', 'class' => 'form-control', 'prompt' => '全部大洲']) ?>
</div>
<?php else: ?>
<div class="form-group">
    <?= Html::activeLabel($searchModel, 'teamId', ['for' => 'catalog-team']) ?>
    <?= Html::activeDropDownList($searchModel, 'teamId', $teams,
        ['name' => 'teamId', 'id' => 'catalog-team', 'class' => 'form-control', 'prompt' => '全部球队']) ?>
</div>
<div class="form-group">
    <?= Html::activeLabel($searchModel, 'position', ['for' => 'catalog-position']) ?>
    <?= Html::activeTextInput($searchModel, 'position', ['name' => 'position', 'id' => 'catalog-position', 'class' => 'form-control', 'maxlength' => 20]) ?>
</div>
<?php endif ?>
<div class="form-group catalog-filter-actions">
    <?= Html::submitButton('筛选', ['class' => 'btn btn-success']) ?>
    <?= Html::a('重置', ['/' . $this->context->id . '/index'], ['class' => 'btn btn-default']) ?>
</div>
<?= Html::endForm() ?>
