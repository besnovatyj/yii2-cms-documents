<?php


/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

declare(strict_types=1);

namespace Besnovatyj\Documents\services\archive;

use ZipArchive;

/**
 * Чтение содержимого ZIP-архива.
 *
 * Работает поверх ext-zip напрямую: нужны сжатый размер и признак шифрования
 * записи, которых обёртки над ZipArchive не отдают, а извлечение одного файла
 * сводится к одному вызову getStreamIndex().
 *
 * Архив только читается: распаковки на диск нет ни при снятии манифеста
 * (читается лишь центральный каталог), ни при отдаче отдельного файла
 * (данные идут потоком в ответ).
 *
 * Имена файлов берутся так, как их отдаёт ZipArchive. Архивы, упакованные
 * старыми программами без пометки UTF-8, дадут нечитаемые имена — такой архив
 * переупаковывается в ZIP заново; угадывать кодировку модуль не пытается.
 */
final class ZipReader
{
    /** Сколько записей архива попадает в манифест. */
    public const int DEFAULT_LIMIT = 500;

    /** Расширения файлов, содержимое которых модуль умеет показывать. */
    private const array SUPPORTED_EXTENSIONS = ['zip'];

    /**
     * Архивы, из которых отданы потоки.
     *
     * Поток на файл внутри архива читается, пока открыт сам архив, поэтому
     * объект держится здесь до конца запроса.
     *
     * @var ZipArchive[]
     */
    private array $streamedArchives = [];

    /**
     * Поддерживается ли просмотр содержимого файла с таким расширением.
     *
     * RAR и 7z не поддерживаются: соответствующих расширений PHP на сервере нет,
     * такой документ остаётся обычным файлом на скачивание.
     */
    public function supports(?string $extension): bool
    {
        return $extension !== null
            && in_array(strtolower($extension), self::SUPPORTED_EXTENSIONS, true);
    }

    /**
     * Снимает список файлов архива.
     *
     * @param string $absolutePath Путь к архиву на диске
     * @param int    $limit        Сколько записей поместить в манифест
     *
     * @return ArchiveManifest|null null, если файл не открывается как ZIP
     */
    public function readManifest(string $absolutePath, int $limit = self::DEFAULT_LIMIT): ?ArchiveManifest
    {
        if (!is_file($absolutePath)) {
            return null;
        }

        $zip = new ZipArchive();
        if ($zip->open($absolutePath, ZipArchive::RDONLY) !== true) {
            return null;
        }

        $entries = [];
        $total = 0;

        for ($i = 0, $count = $zip->numFiles; $i < $count; $i++) {
            $stat = $zip->statIndex($i);
            if ($stat === false || $this->isDirectory($stat['name'], $stat['size'])) {
                continue;
            }

            $total++;
            if (count($entries) >= $limit) {
                continue;
            }

            $entries[] = new ArchiveEntry(
                $i,
                $this->toValidUtf8($stat['name']),
                (int)$stat['size'],
                (int)$stat['comp_size'],
                $stat['mtime'] > 0 ? (int)$stat['mtime'] : null,
                ($stat['encryption_method'] ?? ZipArchive::EM_NONE) !== ZipArchive::EM_NONE,
            );
        }

        $zip->close();

        return new ArchiveManifest($entries, $total, $total > count($entries));
    }

    /**
     * Открывает поток на чтение одного файла внутри архива.
     *
     * Вызывающий код обязан сначала найти запись в сохранённом манифесте:
     * номер записи произвольным числом из запроса быть не должен.
     *
     * @return resource|null null, если архив или запись недоступны
     */
    public function openEntryStream(string $absolutePath, ArchiveEntry $entry)
    {
        if (!is_file($absolutePath)) {
            return null;
        }

        $zip = new ZipArchive();
        if ($zip->open($absolutePath, ZipArchive::RDONLY) !== true) {
            return null;
        }

        $stream = $zip->getStreamIndex($entry->index);
        if ($stream === false) {
            $zip->close();

            return null;
        }

        $this->streamedArchives[] = $zip;

        return $stream;
    }

    /**
     * Записи-директории в списке файлов не нужны: в ZIP они помечаются
     * завершающим слэшем и нулевым размером.
     */
    private function isDirectory(string $name, int $size): bool
    {
        return $size === 0 && str_ends_with($name, '/');
    }

    /**
     * Гарантирует, что имя переживёт сохранение в JSON и в БД.
     *
     * Кодировку не угадывает: невалидные байты заменяются, чтобы нечитаемое имя
     * дошло до админки как нечитаемое, а не уронило сохранение документа.
     */
    private function toValidUtf8(string $name): string
    {
        if (mb_check_encoding($name, 'UTF-8')) {
            return $name;
        }

        $substitute = mb_substitute_character();
        mb_substitute_character(0xFFFD);
        try {
            return mb_convert_encoding($name, 'UTF-8', 'UTF-8');
        } finally {
            mb_substitute_character($substitute);
        }
    }
}
