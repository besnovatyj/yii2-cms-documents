<?php


/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

namespace Besnovatyj\Documents\controllers\frontend;

use common\components\controller\ControllerTrait;
use Exception;
use Besnovatyj\Documents\readModels\CategoryReadRepository;
use Besnovatyj\Documents\readModels\DocumentsReadRepository;
use Yii;
use yii\web\Controller;
use yii\web\NotFoundHttpException;

class DocumentController extends Controller
{
    use ControllerTrait;

    private DocumentsReadRepository $documents;
    private CategoryReadRepository $categories;

    public function __construct(
        $id,
        $module,
        DocumentsReadRepository $documents,
        CategoryReadRepository $categories,
        $config = []
    )
    {
        parent::__construct($id, $module, $config);
        $this->documents = $documents;
        $this->categories = $categories;
    }

    public function actionIndex(): string
    {
        $dataProvider = $this->documents->getAll();
        $category = $this->categories->getRoot();

        return $this->render('/frontend/document/index', [
            'category' => $category,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * @throws NotFoundHttpException
     */
    public function actionCategory(string $slug): string
    {
        if (!$category = $this->categories->findBySlug($slug)) {
            throw new NotFoundHttpException('The requested page does not exist.');
        }

        $dataProvider = $this->documents->getAllByCategory($category);

        return $this->render('/frontend/document/category', [
            'category' => $category,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * @throws NotFoundHttpException
     */
    public function actionView(int $id): string
    {
        if (!$document = $this->documents->find($id)) {
            throw new NotFoundHttpException('The requested page does not exist.');
        }

        return $this->render('/frontend/document/view', [
            'document' => $document,
        ]);
    }

    public function actionDownload(int $id): void
    {
        try {
            $document = $this->documents->find($id);
            Yii::$app->response->sendFile($document->getUploadPath('original_filename'), $document->original_filename)->send();
        } catch (Exception $e) {
            $this->handleDomainException($e);
        }
    }

}
