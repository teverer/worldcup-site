<?php
/**
 * Team: 国足世界杯夺冠队
 * Coding by 李佳婕 2413745, 20261122
 * 模块:框架与团队主页 —— 后台团队成员增删改查。
 */

namespace backend\controllers;

use Yii;
use backend\components\TeamAdminController;
use common\models\Member;
use yii\data\ActiveDataProvider;
use yii\db\IntegrityException;
use yii\web\UploadedFile;

class MemberController extends TeamAdminController
{
    public function actionIndex()
    {
        $query = Member::find();
        $q = Yii::$app->request->get('q');
        if (is_string($q) && trim($q) !== '') {
            $q = trim($q);
            $query->andWhere([
                'or',
                ['like', 'name', $q],
                ['like', 'student_id', $q],
                ['like', 'duty', $q],
            ]);
        }

        return $this->render('index', [
            'dataProvider' => new ActiveDataProvider([
                'query' => $query,
                'pagination' => ['pageSize' => 20],
                'sort' => [
                    'defaultOrder' => ['id' => SORT_ASC],
                    'attributes' => ['id', 'name', 'student_id', 'role'],
                ],
            ]),
            'q' => (string) $q,
        ]);
    }

    public function actionView($id)
    {
        return $this->render('view', ['model' => $this->findMember($id)]);
    }

    public function actionCreate()
    {
        return $this->edit(new Member(), 'create');
    }

    public function actionUpdate($id)
    {
        return $this->edit($this->findMember($id), 'update');
    }

    public function actionDelete($id)
    {
        $model = $this->findMember($id);
        $avatar = $model->avatar;
        try {
            if ($model->delete() !== false) {
                $this->deleteImage($avatar);
                Yii::$app->session->setFlash('success', '成员已删除。');
            } else {
                Yii::$app->session->setFlash('error', implode('；', $model->getFirstErrors()));
            }
        } catch (IntegrityException $exception) {
            Yii::$app->session->setFlash('error', '该成员仍被其他数据引用，不能删除。');
        }
        return $this->redirect(['index']);
    }

    protected function edit(Member $model, $view)
    {
        $oldAvatar = $model->isNewRecord ? null : $model->getOldAttribute('avatar');
        if ($model->load(Yii::$app->request->post())) {
            $model->avatarFile = UploadedFile::getInstance($model, 'avatarFile');
            if ($model->validate()) {
                $newAvatar = null;
                try {
                    if ($model->avatarFile !== null) {
                        $newAvatar = $this->storeImage($model->avatarFile);
                        $model->avatar = $newAvatar;
                    }
                    if ($model->save(false)) {
                        if ($newAvatar !== null && $oldAvatar !== $newAvatar) {
                            $this->deleteImage($oldAvatar);
                        }
                        Yii::$app->session->setFlash('success', '成员资料已保存。');
                        return $this->redirect(['view', 'id' => $model->id]);
                    }
                } catch (IntegrityException $exception) {
                    $model->addError('student_id', '该学号已存在。');
                } catch (\Throwable $exception) {
                    if ($newAvatar !== null) {
                        $this->deleteImage($newAvatar);
                    }
                    Yii::error($exception, __METHOD__);
                    $model->addError('avatarFile', '照片保存失败，请检查上传目录权限。');
                }
            }
        }

        return $this->render($view, ['model' => $model]);
    }

    protected function findMember($id)
    {
        return $this->findModel($id, Member::class, '成员不存在。');
    }
}
