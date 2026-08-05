<?php


/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

namespace Besnovatyj\Documents\controllers\frontend;

use Besnovatyj\Kernel\controller\ControllerTrait;
use Besnovatyj\Documents\readModels\CategoryReadRepository;
use Besnovatyj\Documents\readModels\DocumentsReadRepository;
use Yii;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;

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

        return $this->render('index', [
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

        return $this->render('category', [
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

        return $this->render('view', [
            'document' => $document,
        ]);
    }

    /**
     * @throws NotFoundHttpException
     */
    public function actionDownload(int $id): Response
    {
        if (!$document = $this->documents->find($id)) {
            throw new NotFoundHttpException('The requested document does not exist.');
        }

        // Документ-ссылка на внешний файлообменник — перенаправляем на него.
        if ($document->type === 'link' && !empty($document->external_url)) {
            return $this->redirect($document->external_url);
        }

        $path = $document->getUploadPath('original_filename');
        if ($path === null || !is_file($path)) {
            throw new NotFoundHttpException('Файл документа не найден.');
        }

        // Отдаём файл под оригинальным именем пользователя (с fallback на title + расширение).
        $downloadName = $document->original_name
            ?: $document->title . ($document->extension ? '.' . $document->extension : '');

        return Yii::$app->response->sendFile($path, $downloadName);
    }

}
