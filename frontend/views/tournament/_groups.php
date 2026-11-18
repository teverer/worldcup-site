<?php
/**
 * Team: 国足世界杯夺冠队
 * Coding by 李行阳 2411353, 20261118
 * 模块:届次与比赛 —— 届次详情页小组赛积分榜
 *
 * @var yii\web\View $this
 * @var array $groups 每组包含 group 与 rows
 */
use yii\helpers\Html;
?>
<section class="td-section td-section--groups" id="groups" tabindex="-1"
         aria-labelledby="td-groups-title">
    <header class="td-section-head">
        <h2 class="section-title" id="td-groups-title">小组赛 <small>积分榜</small></h2>
        <p class="td-section-note">仅统计已结束的小组赛;每组前 2 名直接出线,24 / 48 队制另取成绩最好的小组第 3。</p>
    </header>

    <?php if ($groups === []): ?>
        <div class="empty-state">
            <h3>暂无小组赛数据</h3>
            <p>该届参赛队或小组赛结果尚未录入。</p>
        </div>
    <?php else: ?>
        <div class="td-groups-grid">
            <?php foreach ($groups as $group): ?>
                <?php
                $groupName = (string) $group['group'];
                $groupTitle = $groupName === '未分组' ? '未分组' : $groupName . ' 组';
                $rows = $group['rows'];
                ?>
                <article class="td-group-card">
                    <header class="td-group-head">
                        <h3><?= Html::encode($groupTitle) ?></h3>
                        <span class="td-group-count"><?= Html::encode((string) count($rows)) ?> 队</span>
                    </header>
                    <div class="table-responsive td-group-table-wrap">
                        <table class="table table-compact td-group-table">
                            <caption class="sr-only"><?= Html::encode($groupTitle) ?>积分榜</caption>
                            <thead>
                            <tr>
                                <th scope="col" class="num">#</th>
                                <th scope="col">球队</th>
                                <th scope="col" class="num">场次</th>
                                <th scope="col" class="num">净胜球</th>
                                <th scope="col" class="num">积分</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($rows as $index => $row): ?>
                                <?php
                                $qualified = !empty($row['qualified']);
                                $difference = (int) $row['goal_difference'];
                                $differenceText = $difference > 0 ? '+' . $difference : (string) $difference;
                                ?>
                                <tr class="<?= $qualified ? 'is-qualified' : '' ?>">
                                    <td class="num td-rank-cell">
                                        <span class="td-rank"><?= Html::encode((string) ($index + 1)) ?></span>
                                        <?php if ($qualified): ?>
                                            <span class="td-out-dot" aria-hidden="true"></span>
                                            <span class="sr-only">出线</span>
                                        <?php endif ?>
                                    </td>
                                    <th scope="row" class="td-team">
                                        <span class="td-team-name"><?= Html::encode((string) $row['name']) ?></span>
                                        <?php if ($row['code'] !== null && $row['code'] !== ''): ?>
                                            <span class="td-team-code"><?= Html::encode((string) $row['code']) ?></span>
                                        <?php endif ?>
                                    </th>
                                    <td class="num"><?= Html::encode((string) $row['played']) ?></td>
                                    <td class="num"><?= Html::encode($differenceText) ?></td>
                                    <td class="num td-points"><?= Html::encode((string) $row['points']) ?></td>
                                </tr>
                            <?php endforeach ?>
                            </tbody>
                        </table>
                    </div>
                </article>
            <?php endforeach ?>
        </div>
        <p class="td-groups-legend">
            <span class="td-out-dot" aria-hidden="true"></span>
            绿色标记为出线球队;最佳第 3 名是否出线取决于该届赛制与成绩比较。
        </p>
    <?php endif ?>
</section>
