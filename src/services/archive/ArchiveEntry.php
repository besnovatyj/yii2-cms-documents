<?php


/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

declare(strict_types=1);

namespace Besnovatyj\Documents\services\archive;

/**
 * Одна запись (файл) внутри архива.
 *
 * Значения снимаются из центрального каталога архива при загрузке документа
 * и хранятся в колонке `manifest_json`, поэтому показ страницы документа
 * не открывает архив заново.
 */
final readonly class ArchiveEntry
{
    /**
     * @param int      $index      Порядковый номер записи в архиве — по нему файл и извлекается,
     *                             поэтому склеивать пути для распаковки не требуется вовсе
     * @param string   $path       Путь файла внутри архива, как он записан в архиве
     * @param int      $size       Размер в распакованном виде, в байтах
     * @param int      $packedSize Размер в сжатом виде, в байтах
     * @param int|null $modifiedAt Время изменения файла (unix timestamp), null — в архиве не указано
     * @param bool     $encrypted  Содержимое зашифровано паролем (имя и размер при этом читаются)
     */
    public function __construct(
        public int $index,
        public string $path,
        public int $size,
        public int $packedSize,
        public ?int $modifiedAt,
        public bool $encrypted,
    ) {
    }

    /**
     * Имя файла без пути внутри архива.
     */
    public function basename(): string
    {
        $name = strrchr($this->path, '/');

        return $name === false ? $this->path : substr($name, 1);
    }

    /**
     * Компактное представление для хранения в JSON.
     *
     * @return array{i: int, p: string, s: int, c: int, m: int|null, e: bool}
     */
    public function toArray(): array
    {
        return [
            'i' => $this->index,
            'p' => $this->path,
            's' => $this->size,
            'c' => $this->packedSize,
            'm' => $this->modifiedAt,
            'e' => $this->encrypted,
        ];
    }

    /**
     * Восстановление из компактного представления.
     *
     * @param array<string,mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            (int)($data['i'] ?? 0),
            (string)($data['p'] ?? ''),
            (int)($data['s'] ?? 0),
            (int)($data['c'] ?? 0),
            isset($data['m']) ? (int)$data['m'] : null,
            (bool)($data['e'] ?? false),
        );
    }
}
