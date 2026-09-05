<?php


/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

declare(strict_types=1);

namespace Besnovatyj\Documents\services\archive;

use JsonException;

/**
 * Список содержимого архива, снятый один раз при загрузке документа.
 *
 * Хранится в колонке `documents_documents.manifest_json`. Формат версионирован
 * полем `v`: манифест неизвестной версии считается отсутствующим, документ
 * просто показывается без списка файлов.
 */
final readonly class ArchiveManifest
{
    /** Версия формата JSON. */
    public const int VERSION = 1;

    /**
     * @param ArchiveEntry[] $entries   Записи архива (без директорий), не более лимита чтения
     * @param int            $total     Сколько файлов в архиве всего — может быть больше, чем показано
     * @param bool           $truncated Список обрезан лимитом чтения
     */
    public function __construct(
        public array $entries,
        public int $total,
        public bool $truncated,
    ) {
    }

    /**
     * Суммарный размер показанных записей в распакованном виде, в байтах.
     */
    public function unpackedSize(): int
    {
        return array_sum(array_map(static fn(ArchiveEntry $entry): int => $entry->size, $this->entries));
    }

    /**
     * Есть ли в списке хотя бы один зашифрованный файл.
     */
    public function hasEncrypted(): bool
    {
        foreach ($this->entries as $entry) {
            if ($entry->encrypted) {
                return true;
            }
        }

        return false;
    }

    /**
     * Ищет запись по её номеру в архиве.
     *
     * Единственный допустимый способ добраться до файла внутри архива: номер
     * из запроса сверяется с сохранённым манифестом. Имя файла в распаковке
     * не участвует, поэтому подставить в него посторонний путь невозможно.
     */
    public function findByIndex(int $index): ?ArchiveEntry
    {
        foreach ($this->entries as $entry) {
            if ($entry->index === $index) {
                return $entry;
            }
        }

        return null;
    }

    /**
     * @throws JsonException
     */
    public function toJson(): string
    {
        return json_encode([
            'v' => self::VERSION,
            'total' => $this->total,
            'truncated' => $this->truncated,
            'entries' => array_map(static fn(ArchiveEntry $entry): array => $entry->toArray(), $this->entries),
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
    }

    /**
     * Разбирает JSON из БД. Возвращает null для пустого, битого или
     * неизвестной версии манифеста — отсутствие списка файлов не должно
     * ломать страницу документа.
     */
    public static function fromJson(?string $json): ?self
    {
        if ($json === null || trim($json) === '') {
            return null;
        }

        try {
            $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return null;
        }

        if (!is_array($data) || ($data['v'] ?? null) !== self::VERSION || !is_array($data['entries'] ?? null)) {
            return null;
        }

        $entries = [];
        foreach ($data['entries'] as $entry) {
            if (is_array($entry)) {
                $entries[] = ArchiveEntry::fromArray($entry);
            }
        }

        return new self(
            $entries,
            (int)($data['total'] ?? count($entries)),
            (bool)($data['truncated'] ?? false),
        );
    }
}
