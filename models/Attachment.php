<?php
/**
 * Created by PhpStorm.
 * User: dkinev
 * Date: 27.04.17
 * Time: 0:01
 */

namespace app\models;


class Attachment extends \yii\db\ActiveRecord
{

    /**
     * @return string
     */
    public static function tableName() {

        return 'attachment';
    }

    /**
     * @return array|\yii\db\ActiveRecord[]
     */
    public static function getAll() {

        $data = self::find()->asArray()->all();

        return $data;
    }
}