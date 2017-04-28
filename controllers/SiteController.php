<?php

namespace app\controllers;

use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\UploadedFile;
use yii\filters\VerbFilter;
use app\models\Document;
use app\models\DocumentModel;
use app\models\Attachment;

class SiteController extends Controller
{
    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'only' => ['logout'],
                'rules' => [
                    [
                        'actions' => ['logout'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'logout' => ['post'],
                ],
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function actions() {

        return [
            'error' => [
                'class' => 'yii\web\ErrorAction',
            ],
            'captcha' => [
                'class' => 'yii\captcha\CaptchaAction',
                'fixedVerifyCode' => YII_ENV_TEST ? 'testme' : null,
            ],
        ];
    }

    /**
     * @param $data
     * @param int $length
     * @param bool $raw_output
     * @return bool|string
     * @throws \Exception
     */
    public static function generateHash($data, $length = 20, $raw_output = false) {

        // check data exists
        if (empty($data)){
            throw new \Exception(get_class(self) . '_data_is_empty');
        }

        // make a little salty
        $salt = 'some-salt-from-cookie-validation-key';

        // generate hash
        if ((int)$length > 0)
            return substr(sha1($data . $salt, $raw_output), 0, $length);
        else
            return sha1($data . $salt, $raw_output);
    }

    /**
     * @param $size
     * @return string
     */
    public static function humanBytes($size) {

        $filesizename = array(" Bytes", " KB", " MB", " GB", " TB", " PB", " EB", " ZB", " YB");

        // make filesizes a little humatity
        return $size ? round($size / pow(1024, ($i = floor(log($size, 1024)))), 2) . $filesizename[$i] : '0 Bytes';
    }

    /**
     * Displays homepage.
     *
     * @return string
     */
    public function actionIndex() {

        $this->getView()->title = 'Просмотр документов';

        $message_ok = '';
        $message_err = '';

        $model = new DocumentModel();

        // edit document
        if($model->load(Yii::$app->request->post()) && $model->validate()) {

            // check params
            if (empty($model->id) || empty($model->hash)) {

                $message_err = 'Ошибка изменения документа';

            } else {

                $doc = new Document();
                $doc = $doc->findOne($model->id);

                // check hash
                if ($this::generateHash($doc->name) == $model->hash) {

                    // apply changes
                    $doc->name = $model->name;
                    $doc->description = $model->description;
                    $doc->save();

                    // if exists - add new upload files
                    $model->uploadFiles = UploadedFile::getInstances($model, 'uploadFiles');
                    $uploadFiles = $model->upload();

                    if (is_array($uploadFiles)) {

                        // add new files to DB
                        foreach ($uploadFiles as $uploaded) {
                            $attach = new Attachment();
                            $attach->name = $uploaded['name'];
                            $attach->filename = $uploaded['filename'];
                            $attach->filesize = $uploaded['size'];
                            $attach->document_id = $model->id;
                            $attach->save();
                        }
                    }

                    $message_ok = 'Документ успешно изменён';
                } else {

                    $message_err = 'Ошибка изменения документа';
                }

            }
        }

        // collect all documents to response
        $doc = Document::getAll();
        $attach = Attachment::getAll();
        $attachArr = [];

        // collect attached files to each document
        foreach ($attach as $val) {
            $attachArr[(int)$val['document_id']][(int)$val['id']] = [
                'name' => $val['name'],
                'size' => $this::humanBytes($val['filesize']),
                'extension' => end(explode('.', $val['filename'])),
                'hash' => $this::generateHash($val['filename'], 20)
            ];
        }

        return $this->render('index.twig', [
            'model' => $model,
            'docs' => $doc,
            'attach' => json_encode($attachArr),
            'message_ok' => $message_ok,
            'message_err' => $message_err,
        ]);
    }

    /**
     * Displays add new document page.
     *
     * @return string
     */
    public function actionCreate() {

        $this->getView()->title = 'Добавить новый документ';

        $model = new DocumentModel();

        // validate form and create new document
        if($model->load(Yii::$app->request->post()) && $model->validate()) {

            // write document to DB
            $doc = new Document();
            $doc->name = $model->name;
            $doc->description = $model->description;
            $doc->save();

            $pk = $doc->getPrimaryKey();

            // write new upload files to directory
            $model->uploadFiles = UploadedFile::getInstances($model, 'uploadFiles');
            $uploadFiles = $model->upload();

            if (is_array($uploadFiles)) {

                // add new files to DB
                foreach ($uploadFiles as $uploaded) {
                    $attach = new Attachment();
                    $attach->name = $uploaded['name'];
                    $attach->filename = $uploaded['filename'];
                    $attach->filesize = $uploaded['size'];
                    $attach->document_id = $pk;
                    $attach->save();
                }
            }

            return $this->redirect(['index']);
        }

        return $this->render('create.twig', ['model' => $model]);
    }

    /**
     * @return string
     */
    public function actionDeleteupload() {

        $request = \Yii::$app->request;

        // check parameters and delete uploaded files
        if(!empty($request->post('id', ''))) {

            // get parameters
            $id = (int)$request->post('id');
            $hash = $request->post('hash');

            // delete file
            $attach = new Attachment();
            $attach = $attach->findOne($id);
            $attach_hash = $this::generateHash($attach->filename, 20);
            if ($attach_hash == $hash)
                $attach->delete();
            else
                return $this->renderPartial('ajax.twig', ['content' => 'error']);

            return $this->renderPartial('ajax.twig', ['content' => 'ok']);
        } else {

            return $this->renderPartial('ajax.twig', ['content' => 'error']);
        }
    }

    /**
     * @return string
     */
    public function actionDeletedocument() {

        $request = \Yii::$app->request;

        // check parameters and delete document
        if(!empty($request->post('id', ''))) {

            // get parameters
            $id = (int)$request->post('id');
            $hash = $request->post('hash');

            $doc = new Document();
            $doc = $doc->findOne($id);
            $doc_hash = $this::generateHash($doc->name);

            // delete all assigned upload files
            \Yii::$app->db
                ->createCommand()
                ->delete('attachment', ['document_id' => $id])
                ->execute();

            if ($doc_hash == $hash)
                $doc->delete();
            else
                return $this->renderPartial('ajax.twig', ['content' => 'error']);

            return $this->renderPartial('ajax.twig', ['content' => 'ok']);
        } else {

            return $this->renderPartial('ajax.twig', ['content' => 'error']);
        }
    }

}
