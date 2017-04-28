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

class DocumentModel extends Model
{
    public $id;
    public $hash;
    public $name;
    public $description;
    public $uploadFiles;

    /**
     * @return array
     */
    public function rules()
    {
        $extensions = ['png', 'jpg', 'zip', 'rar', 'txt', 'doc', 'rtf',
            'gif', 'html', 'css', 'xls', 'xlsx', 'pdf'];

        return [
            [['id'], 'integer'],
            [['hash'], 'string', 'max' => 40],
            [['name'], 'string', 'max' => 255],
            [['name','description'],'required'],
            [['uploadFiles'], 'file', 'skipOnEmpty' => true,
                'extensions' => implode(', ', $extensions),
                'maxFiles' => 20,
                'checkExtensionByMimeType' => false],
        ];
    }

    /**
     * @return array
     */
    public function attributeLabels()
    {
        return [
            'name' => 'Название',
            'description' => 'Описание',
            'uploadFiles' => 'Прикрепить файлы к документу'
        ];
    }

    /**
     * @return array|string
     */
    public function upload()
    {
        if (!empty($this->uploadFiles)) {
            foreach ($this->uploadFiles as $file) {
                $filename=Yii::$app->getSecurity()->generateRandomString(22) . '.' . $file->extension;
                //echo $filename;
                $file->saveAs(__DIR__.'/../web/upload/'.$filename);
                $files[] = [
                    'name'=>$file->name,
                    'size'=>$file->size,
                    'filename'=>$filename
                    ];
            }
            return $files;
        } else {
            return 'false';
        }
    }
}
