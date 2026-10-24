<?php
/**
 * Team: 国足世界杯夺冠队
 * Coding by Codex (AI assistance), 20261021
 * 模块:球队与球员 —— 后台球队增删改查。
 */

namespace backend\controllers;

use backend\components\CatalogController;
use common\models\Team;
use common\models\TeamSearch;

class TeamController extends CatalogController {
    protected $modelClass = Team::class;
    protected $searchClass = TeamSearch::class;
}
