<?php
/**
 * Team: 国足世界杯夺冠队
 * Coding by 李行阳 2411353, 20261122
 * 模块:界面设计 —— 根页记分牌墙
 *
 * @var yii\web\View $this
 * @var array $editions 按年份倒序的届次数据
 * @var array $latestComments 首页“最新留言”摘要
 */

use common\components\CommentTarget;
use common\components\TeamFlag;
use frontend\widgets\CommentSection;
use yii\helpers\Html;
use yii\helpers\Url;

$this->title = '世界杯档案 · 1986—2026';
$this->registerCssFile('@web/css/root-wall.css');
$this->registerJsFile('@web/js/ledger.js', ['position' => \yii\web\View::POS_END]);

$identity = Yii::$app->user->identity;
$bookUrl = Url::to(['/site/discussion']);
// 从首页去登录/注册，回来仍落在这一段的输入框旁，不用再找一次。
$hereUrl = Url::to(['/site/index']) . '#comments';
$loginUrl = Url::to(['/site/login', 'returnUrl' => $hereUrl]);
?>
<div class="root-wall">
    <header class="wall-head">
        <div class="wall-head-main">
            <h1 class="wall-title">
                <span class="wall-range">1986—2026</span>
                <span class="wall-subtitle">十一届世界杯</span>
            </h1>
            <p class="wall-lead">
                从墨西哥到美加墨，四十年，十一届。选一届，进入那一届的晋级图、积分与射手。
            </p>
        </div>

    </header>

    <section class="wall" aria-label="历届世界杯">
        <?php if (!$editions): ?>
            <div class="wall-empty">
                <strong>暂无届次数据</strong>
                <p>数据库中还没有可展示的世界杯届次。</p>
            </div>
        <?php else: ?>
            <?php foreach ($editions as $index => $edition): ?>
                <?php
                $final = $edition['final'];
                $isLit = $index === 0;
                $hasChampion = $final !== null && $final['champion'] !== null;
                $boardLabel = $edition['year'] . ' ' . $edition['name'];
                if ($hasChampion) {
                    $boardLabel .= '，冠军 ' . $final['champion'];
                }
                ?>
                <a class="wall-board<?= $isLit ? ' is-lit' : '' ?>"
                   href="<?= Html::encode(Url::to(['/tournament/view', 'id' => $edition['id']])) ?>"
                   aria-label="<?= Html::encode($boardLabel . '，进入本届') ?>">
                    <span class="board-year"><?= Html::encode((string) $edition['year']) ?></span>

                    <span class="board-main">
                        <span class="board-name"><?= Html::encode($edition['name']) ?></span>
                        <span class="board-meta">
                            <span class="board-host">
                                东道主 <?= Html::encode($edition['host']) ?>
                                <span aria-hidden="true">·</span>
                                第 <?= Html::encode((string) $edition['ordinal']) ?> 届
                            </span>
                            <span class="board-volume">
                                <?= Html::encode((string) $edition['games']) ?> 场 /
                                <?= Html::encode((string) $edition['goals']) ?> 球
                            </span>
                        </span>
                    </span>

                    <?php if ($hasChampion): ?>
                        <span class="board-final">
                            <span class="board-side board-side--champion">
                                <?= TeamFlag::html($final['champion_code'], $final['champion'], 'board-flag') ?>
                                <span class="board-side-text">
                                    <span class="board-role">冠军</span>
                                    <span class="board-team"><?= Html::encode($final['champion']) ?></span>
                                </span>
                            </span>
                            <span class="board-score-wrap">
                                <span class="board-score"><?= Html::encode($final['champion_score'] . '–' . $final['opponent_score']) ?></span>
                                <?php if ($final['has_penalty']): ?>
                                    <span class="board-badge">点球 <?= Html::encode(str_replace('-', '–', $final['penalty_text'])) ?></span>
                                <?php elseif ($final['extra_time']): ?>
                                    <span class="board-badge">加时</span>
                                <?php endif ?>
                            </span>
                            <span class="board-side board-side--runner">
                                <span class="board-side-text">
                                    <span class="board-role">亚军</span>
                                    <span class="board-team"><?= Html::encode($final['opponent']) ?></span>
                                </span>
                                <?= TeamFlag::html($final['opponent_code'], $final['opponent'], 'board-flag') ?>
                            </span>
                        </span>
                    <?php else: ?>
                        <span class="board-final board-final--pending">决赛数据待补</span>
                    <?php endif ?>

                    <span class="board-enter">进入</span>
                </a>
            <?php endforeach ?>
        <?php endif ?>
    </section>

    <?php // 收场不是墙尾的“更多入口”：墙看完了，这里只做一件事——把记忆留下。 ?>
    <section class="wall-closing" id="comments" aria-labelledby="wall-closing-title">
        <div class="wall-coda-inner">
            <header class="wall-coda-inner-head">
                <h2 id="wall-closing-title">继续看，或者留一句</h2>
                <p>十一届看完了。挑一届接着看，或者把记住的那一夜写进留言簿。</p>
            </header>

            <section class="wall-book" data-ledger-scope aria-labelledby="wall-book-title">
                <header class="wall-book-head">
                    <h3 class="wall-book-title" id="wall-book-title">留言簿</h3>
                    <span class="wall-book-note">来自各届、资讯与全站</span>
                    <?= Html::a('翻整本留言簿', $bookUrl, ['class' => 'wall-book-all']) ?>
                </header>

                <div class="wall-book-compose">
                    <?php if ($identity === null): ?>
                        <p class="wall-book-guest">
                            你和世界杯的故事，从哪一届开始？
                            <?= Html::a('登录后写一句', $loginUrl) ?>
                            <span class="wall-book-guest-sub">还没有账号？<?= Html::a('注册', Url::to(['/site/signup', 'returnUrl' => $hereUrl])) ?></span>
                        </p>
                    <?php else: ?>
                        <?= Html::beginForm(['/comment/create'], 'post', ['class' => 'wall-book-form']) ?>
                            <?= Html::hiddenInput('CommentForm[targetType]', CommentTarget::SITE) ?>
                            <?= Html::hiddenInput('CommentForm[targetId]', (string) CommentTarget::SITE_ID) ?>
                            <span class="seal seal--lg"
                                  style="background:<?= Html::encode(CommentSection::sealColor((int) $identity->id)) ?>"
                                  aria-hidden="true"><?= Html::encode(CommentSection::sealInitial($identity->nickname ?: $identity->username)) ?></span>
                            <div class="wall-book-field">
                                <label class="sr-only" for="wall-book-content">留言内容</label>
                                <?= Html::textarea('CommentForm[content]', '', [
                                    'id' => 'wall-book-content',
                                    'class' => 'composer-input',
                                    'rows' => 1,
                                    'maxlength' => 5000,
                                    'required' => true,
                                    'placeholder' => '写下你和世界杯的一件小事……',
                                    'data-ledger-input' => '1',
                                ]) ?>
                                <div class="composer-foot">
                                    <span class="composer-hint">Ctrl + Enter 记下</span>
                                    <?= Html::submitButton('记下', ['class' => 'btn-record']) ?>
                                </div>
                            </div>
                        <?= Html::endForm() ?>
                    <?php endif ?>
                </div>

                <?php if (!empty($latestComments)): ?>
                    <ol class="wall-book-list">
                        <?php foreach ($latestComments as $item): ?>
                            <li>
                                <a class="wall-book-row" href="<?= Html::encode($item['url']) ?>">
                                    <span class="seal wall-book-seal"
                                          style="background:<?= Html::encode(CommentSection::sealColor($item['userId'])) ?>"
                                          aria-hidden="true"><?= Html::encode(CommentSection::sealInitial($item['author'])) ?></span>
                                    <span class="wall-book-body">
                                        <span class="wall-book-meta">
                                            <span class="wall-book-name"><?= Html::encode($item['author']) ?></span>
                                            <span class="wall-book-source"><?= Html::encode($item['source']) ?></span>
                                            <time class="wall-book-time"
                                                  datetime="<?= Html::encode($item['datetime']) ?>"><?= Html::encode($item['date']) ?></time>
                                        </span>
                                        <span class="wall-book-text"><?= Html::encode($item['excerpt']) ?></span>
                                    </span>
                                </a>
                            </li>
                        <?php endforeach ?>
                    </ol>
                <?php else: ?>
                    <div class="wall-book-empty">
                        <p class="wall-book-empty-title">留言簿还是空的。</p>
                        <?php if ($identity === null): ?>
                            <p class="wall-book-empty-sub">十一届看下来，总有一场你想说两句。<?= Html::a('登录后写第一句', $loginUrl) ?></p>
                        <?php else: ?>
                            <p class="wall-book-empty-sub">十一届看下来，总有一场你想说两句。上面就是第一行。</p>
                        <?php endif ?>
                    </div>
                <?php endif ?>
            </section>
        </div>
    </section>
</div>
