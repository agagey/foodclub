<?php
/**
 * Created by PhpStorm.
 * User: dkinev
 * Date: 26.04.17
 * Time: 23:57
 */

namespace app\models;

use yii;
use yii\base\Model;
use yii\web\UploadedFile;
use app\models\Attachment;

class Document extends \yii\db\ActiveRecord
{

    public static function tableName() {

        return 'document';
    }

    public static function getAll() {

        $data = self::find()->asArray()->all();

        return $data;
    }

    public function deleteByID($doc_id) {

        if ((int)$doc_id == 0){
            throw new \Exception(get_class($this) . '_document_id_not_defined');
        }

        $this->findOne($doc_id)->delete();

        // delete all attachments
    }
}