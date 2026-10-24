<?php
/**
 * Team: 国足世界杯夺冠队
 * Coding by Codex (AI assistance), 20261021
 * 模块:球队与球员 —— 校验本站图片的相对路径。
 */

namespace common\validators;

use yii\validators\Validator;

class AssetPathValidator extends Validator {
    public function validateAttribute($model, $attribute) {
        $value = $model->$attribute;
        if (!preg_match('~^(images|uploads)/[a-zA-Z0-9_./-]+\.(png|jpe?g|gif|webp|avif)$~iD', $value)
            || strpos($value, '..') !== false || strpos($value, '//') !== false) {
            $this->addError($model, $attribute, '请填写 images/ 或 uploads/ 下的图片相对路径，不能包含 ..。');
        }
    }
}
