<?php


/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

namespace Besnovatyj\Documents\controllers\frontend;

use Besnovatyj\Kernel\controller\ControllerTrait;
use Besnovatyj\Documents\entities\Document;
use Besnovatyj\Documents\forms\frontend\DocumentFilterForm;
use Besnovatyj\Documents\readModels\CategoryReadRepository;
use Besnovatyj\Documents\readModels\DocumentsReadRepository;
use Besnovatyj\Documents\services\archive\ZipReader;
use Yii;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class DocumentController extends Controller
{
    use ControllerTrait;

    private DocumentsReadRepository $documents;
    private CategoryReadRepository $categories;
    private ZipReader $archives;

    public function __construct(
        $id,
        $module,
        DocumentsReadRepository $documents,
        CategoryReadRepository $categories,
        ZipReader $archives,
        $config = []
    )
    {
        parent::__construct($id, $module, $config);
        $this->documents = $documents;
        $this->categories = $categories;
        $this->archives = $archives;
    }

    public function actionIndex(): string
    {
        $filter = new DocumentFilterForm()->loadRequest(Yii::$app->request->queryParams);
        $dataProvider = $this->documents->getAll($filter);
        $category = $this->categories->getRoot();

        return $this->render('index', [
            'category' => $category,
            'filter' => $filter,
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

        $filter = new DocumentFilterForm()->loadRequest(Yii::$app->request->queryParams);
        $dataProvider = $this->documents->getAllByCategory($category, $filter);

        return $this->render('category', [
            'category' => $category,
            'filter' => $filter,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * @throws NotFoundHttpException
     */
    public function actionView(int $id): string
    {
        $document = $this->findDocument($id);

        return $this->render('view', [
            'document' => $document,
        ]);
    }

    /**
     * @throws NotFoundHttpException
     */
    public function actionDownload(int $id): Response
    {
        $document = $this->findDocument($id);

        // Документ-ссылка на внешний файлообменник — перенаправляем на него.
        if ($document->type === 'link' && !empty($document->external_url)) {
            return $this->redirect($document->external_url);
        }

        $path = $this->findFilePath($document);

        // Отдаём файл под оригинальным именем пользователя (с fallback на title + расширение).
        return Yii::$app->response->sendFile($path, $document->downloadName());
    }

    /**
     * Отдаёт файл для показа на самой странице: картинку в теге img и PDF —
     * встроенному просмотрщику.
     *
     * Файл лежит на домене статики, а страница документа — на основном домене.
     * Просмотрщику PDF пришлось бы читать чужой домен, поэтому файл проходит
     * через это действие: так он остаётся в пределах одного домена и настройки
     * веб-сервера менять не нужно.
     *
     * Отдаются только те типы, что перечислены в
     * {@see Document::PREVIEW_MIME_TYPES}: браузер показывает содержимое в
     * контексте сайта, и произвольному файлу здесь не место.
     *
     * @throws NotFoundHttpException
     */
    public function actionPreview(int $id): Response
    {
        $document = $this->findDocument($id);

        $mimeType = $document->previewMimeType();
        if ($mimeType === null) {
            throw new NotFoundHttpException('Предпросмотр для этого документа недоступен.');
        }

        return Yii::$app->response->sendFile($this->findFilePath($document), $document->downloadName(), [
            'inline' => true,
            'mimeType' => $mimeType,
        ]);
    }

    /**
     * Отдаёт один файл из архива, не распаковывая архив на диск.
     *
     * Номер записи сверяется с манифестом, снятым при загрузке документа:
     * произвольным числом из запроса он быть не может. Содержимое всегда
     * уходит вложением — открывать в браузере файл из архива незачем.
     *
     * @throws NotFoundHttpException
     */
    public function actionArchiveFile(int $id, int $index): Response
    {
        $document = $this->findDocument($id);

        $entry = $document->getManifest()?->findByIndex($index);
        if ($entry === null) {
            throw new NotFoundHttpException('Файл в архиве не найден.');
        }

        if ($entry->encrypted) {
            throw new NotFoundHttpException('Файл в архиве защищён паролем и не может быть выдан.');
        }

        $stream = $this->archives->openEntryStream($this->findFilePath($document), $entry);
        if ($stream === null) {
            throw new NotFoundHttpException('Файл в архиве не читается.');
        }

        // Поток внутри архива не перематывается: выдать произвольный фрагмент
        // файла нечем, и запрос на докачку вернул бы начало файла под видом
        // его середины. Поэтому Range игнорируем и отдаём файл целиком.
        Yii::$app->request->getHeaders()->remove('Range');

        $response = Yii::$app->response->sendStreamAsFile($stream, $entry->basename(), [
            'mimeType' => 'application/octet-stream',
            'fileSize' => $entry->size,
        ]);
        $response->getHeaders()->set('Accept-Ranges', 'none');

        return $response;
    }

    /**
     * @throws NotFoundHttpException
     */
    private function findDocument(int $id): Document
    {
        if (!$document = $this->documents->find($id)) {
            throw new NotFoundHttpException('The requested document does not exist.');
        }

        return $document;
    }

    /**
     * @throws NotFoundHttpException
     */
    private function findFilePath(Document $document): string
    {
        $path = $document->hasFile() ? $document->getUploadPath('original_filename') : null;
        if ($path === null || !is_file($path)) {
            throw new NotFoundHttpException('Файл документа не найден.');
        }

        return $path;
    }
}
