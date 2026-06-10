<?php


/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

namespace Besnovatyj\Documents\controllers\backend;


use common\components\controller\ControllerTrait;
use common\components\urlmanager\UrlManagerHelperTrait;
use Exception;
use Besnovatyj\Documents\entities\Document;
use Besnovatyj\Documents\forms\backend\DocumentForm;
use Besnovatyj\Documents\forms\backend\search\DocumentSearch;
use Besnovatyj\Documents\services\manage\DocumentsManageService;
use Throwable;
use Yii;
use yii\base\InvalidConfigException;
use yii\filters\VerbFilter;
use yii\helpers\VarDumper;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class DocumentController extends Controller
{
    use UrlManagerHelperTrait;
    use ControllerTrait;

    private DocumentsManageService $service;

    public function __construct($id, $module, DocumentsManageService $service, $config = [])
    {
        parent::__construct($id, $module, $config);
        $this->service = $service;
    }

    public function behaviors(): array
    {
        return [
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'delete' => ['POST'],
                    'activate' => ['POST'],
                    'draft' => ['POST'],
                ],
            ],
        ];
    }

    public function actionIndex(): string
    {
        $searchModel = new DocumentSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * @param integer $id
     * @return string
     * @throws InvalidConfigException|NotFoundHttpException
     */
    public function actionView(int $id): string
    {
        $absoluteFrontendUrl = $this->getAbsoluteFrontendRoute('/Documents/document/view/', ['id' => $id]);
        $document = $this->findModel($id);
        return $this->render('view', [
            'document' => $document,
            'absoluteFrontendUrl' => $absoluteFrontendUrl,
        ]);
    }

    public function actionCreate(): Response|string
    {
        $form = new DocumentForm();
        if ($form->load(Yii::$app->request->post()) && $form->validate()) {
            try {
                $document = $this->service->create($form);
                return $this->redirect(['view', 'id' => $document->id]);
            } catch (Exception $e) {
                $this->handleDomainException($e);
            }
        }
        return $this->render('create', [
            'model' => $form,
        ]);
    }

    /**
     * @param integer $id
     * @return string|Response
     * @throws NotFoundHttpException
     */
    public function actionUpdate(int $id): Response|string
    {
        $document = $this->findModel($id);

        $form = new DocumentForm($document);
        if ($form->load(Yii::$app->request->post()) && $form->validate()) {
            try {
                $this->service->edit($document->id, $form);
                return $this->redirect(['view', 'id' => $document->id]);
            } catch (Exception $e) {
                $this->handleDomainException($e);
            }
        }
        return $this->render('update', [
            'model' => $form,
            'document' => $document,
        ]);
    }

    /**
     * @param integer $id
     * @return Response
     */
    public function actionDelete(int $id): Response
    {
        try {
            $this->service->remove($id);
        } catch (Throwable $e) {
            $this->handleDomainException($e);
        }
        return $this->redirect(['index']);
    }

    /**
     * @param integer $id
     * @return Response
     */
    public function actionActivate(int $id): Response
    {
        try {
            $this->service->activate($id);
        } catch (Exception $e) {
            $this->handleDomainException($e);
        }
        return $this->goReferer();
    }

    /**
     * @param integer $id
     * @return Response
     */
    public function actionDraft(int $id): Response
    {
        try {
            $this->service->draft($id);
        } catch (Exception $e) {
            $this->handleDomainException($e);
        }
        return $this->goReferer();
    }

    /**
     * @param integer $id
     * @return Document the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel(int $id): Document
    {
        if (($model = Document::findOne($id)) !== null) {
            return $model;
        }
        throw new NotFoundHttpException('The requested page does not exist.');
    }
}
