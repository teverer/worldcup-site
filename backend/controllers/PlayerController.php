<?php
/**
 * Team: 国足世界杯夺冠队
 * Coding by 高家铭 2412267, 20261021
 * 模块:球队与球员 —— 后台球员增删改查。
 */

namespace backend\controllers;

use backend\components\CatalogController;
use common\models\Player;
use common\models\PlayerSearch;

class PlayerController extends CatalogController {
    protected $modelClass = Player::class;
    protected $searchClass = PlayerSearch::class;
}
