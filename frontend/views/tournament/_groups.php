<?php
/**
 * Team: 国足世界杯夺冠队
 * Coding by 李行阳 2411353, 20261122
 * 模块:届次与比赛 —— 小组赛视图
 *
 * 12 / 8 / 6 组按可用宽度 3 列 / 2 列排布;每张表 4 队,
 * 列出场 / 胜 / 平 / 负 / 净胜球 / 积分,队名链到球队页。
 *
 * @var yii\web\View $this
 * @var array $groups 每组包含 group 与 rows
 * @var common\models\Tournament $model
 */

use yii\helpers\Html;
use yii\helpers\Url;
?>
<section class="td-panel td-panel--groups" id="groups" tabindex="-1"
         aria-labelledby="td-groups-title">
    <header class="td-section-head">
        <h2 class="section-title" id="td-groups-title">小组积分榜</h2>
        <p class="td-section-note">仅统计已结束的小组赛；每组前两名出线，24 / 48 队制另取最佳小组第三。</p>
    </header>

    <?php if ($groups === []): ?>
        <div class="empty-state">
            <h3>小组赛数据待补</h3>
            <p>该届参赛队或小组赛结果尚未录入。</p>
        </div>
    <?php else: ?>
        <div class="group-grid">
            <?php foreach ($groups as $group): ?>
                <?php
                $groupName = (string) $group['group'];
                $groupTitle = $groupName === '未分组' ? '未分组' : $groupName . ' 组';
                $rows = isset($group['rows']) && is_array($group['rows']) ? $group['rows'] : [];
                ?>
                <article class="group-card">
                    <header class="group-card__head">
                        <h3><?= Html::encode($groupTitle) ?></h3>
                    </header>
                    <div class="group-card__table-wrap">
                        <table class="table table-compact group-table">
                            <caption class="sr-only"><?= Html::encode($groupTitle) ?>积分榜</caption>
                            <thead>
                            <tr>
                                <th scope="col" class="num">#</th>
                                <th scope="col">球队</th>
                                <th scope="col" class="num group-col-result">胜</th>
                                <th scope="col" class="num group-col-result">平</th>
                                <th scope="col" class="num group-col-result">负</th>
                                <th scope="col" class="num">净胜</th>
                                <th scope="col" class="num">积分</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($rows as $index => $row): ?>
                                <?php
                                $qualification = isset($row['qualification']) ? $row['qualification'] : null;
                                $qualified = !empty($row['qualified']);
                                $rowClasses = [];
                                if ($qualified) {
                                    $rowClasses[] = 'is-qualified';
                                }
                                if ($qualification === 'best_third') {
                                    $rowClasses[] = 'is-best-third';
                                }
                                $difference = (int) $row['goal_difference'];
                                $differenceText = $difference > 0 ? '+' . $difference : (string) $difference;
                                $teamId = (int) $row['team_id'];
                                $teamName = (string) $row['name'];
                                ?>
                                <tr class="<?= Html::encode(implode(' ', $rowClasses)) ?>">
                                    <td class="num group-rank-cell">
                                        <span class="group-rank"><?= Html::encode((string) ($index + 1)) ?></span>
                                    </td>
                                    <th scope="row" class="group-team">
                                        <a href="<?= Html::encode(Url::to(['/team/view', 'id' => $teamId])) ?>"
                                           title="<?= Html::encode($teamName) ?>"><?= Html::encode($teamName) ?></a>
                                        <?php if ($qualification === 'top2'): ?>
                                            <span class="group-qual group-qual--top" aria-hidden="true"></span>
                                            <span class="sr-only">出线</span>
                                        <?php elseif ($qualification === 'best_third'): ?>
                                            <span class="group-qual group-qual--third" aria-hidden="true"></span>
                                            <span class="sr-only">最佳第三名出线</span>
                                        <?php endif ?>
                                    </th>
                                    <td class="num group-col-result"><?= Html::encode((string) $row['won']) ?></td>
                                    <td class="num group-col-result"><?= Html::encode((string) $row['drawn']) ?></td>
                                    <td class="num group-col-result"><?= Html::encode((string) $row['lost']) ?></td>
                                    <td class="num"><?= Html::encode($differenceText) ?></td>
                                    <td class="num group-points"><?= Html::encode((string) $row['points']) ?></td>
                                </tr>
                            <?php endforeach ?>
                            </tbody>
                        </table>
                    </div>
                </article>
            <?php endforeach ?>
        </div>

        <p class="group-legend">
            <span class="group-qual group-qual--top" aria-hidden="true"></span>
            小组前两名出线
            <span class="group-qual group-qual--third" aria-hidden="true"></span>
            最佳第三名出线
        </p>

        <p class="td-section-more">
            <?= Html::a('查看小组赛全部比赛', [
                '/game/index',
                'tournamentId' => $model->id,
                'stage' => '小组赛',
            ]) ?>
        </p>
    <?php endif ?>
</section>
