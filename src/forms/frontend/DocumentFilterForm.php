<?php


/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

declare(strict_types=1);

namespace Besnovatyj\Documents\forms\frontend;

use Besnovatyj\Forms\BaseForm;
use DateTimeImmutable;
use DateTimeZone;
use Yii;

/**
 * Фильтр и сортировка списка документов на фронтенде.
 *
 * Форма читается из GET, поэтому её значения попадают в адрес страницы:
 * подобранный список можно сохранить в закладки или переслать.
 */
class DocumentFilterForm extends BaseForm
{
    /** Фильтровать по дате загрузки документа на сайт. */
    public const string DATE_UPLOADED = 'uploaded';

    /** Фильтровать по дате самого документа. */
    public const string DATE_DOCUMENT = 'document';

    /** Порядок по умолчанию: сначала недавно загруженные. */
    public const string SORT_DEFAULT = 'uploaded_desc';

    public string|null $q = null;
    public string|null $dateFrom = null;
    public string|null $dateTo = null;
    public string $dateField = self::DATE_UPLOADED;
    public string $sort = self::SORT_DEFAULT;

    /**
     * Разрешённые сортировки: ключ из запроса → подпись и порядок для SQL.
     *
     * Список закрытый — в ORDER BY попадает только то, что перечислено здесь.
     *
     * @return array<string,array{label: string, order: array<string,int>}>
     */
    public static function sortMap(): array
    {
        return [
            'uploaded_desc' => ['label' => 'Дате загрузки: сначала новые', 'order' => ['d.uploaded_at' => SORT_DESC]],
            'uploaded_asc' => ['label' => 'Дате загрузки: сначала старые', 'order' => ['d.uploaded_at' => SORT_ASC]],
            'document_desc' => ['label' => 'Дате документа: сначала новые', 'order' => ['d.document_date' => SORT_DESC]],
            'document_asc' => ['label' => 'Дате документа: сначала старые', 'order' => ['d.document_date' => SORT_ASC]],
            'title_asc' => ['label' => 'Названию: А—Я', 'order' => ['d.title' => SORT_ASC]],
            'title_desc' => ['label' => 'Названию: Я—А', 'order' => ['d.title' => SORT_DESC]],
        ];
    }

    /**
     * Подписи вариантов сортировки для выпадающего списка.
     *
     * @return array<string,string>
     */
    public static function sortList(): array
    {
        return array_map(static fn(array $item): string => $item['label'], self::sortMap());
    }

    /**
     * Подписи вариантов «по какой дате фильтровать».
     *
     * @return array<string,string>
     */
    public static function dateFieldList(): array
    {
        return [
            self::DATE_UPLOADED => 'загрузки',
            self::DATE_DOCUMENT => 'документа',
        ];
    }

    public function rules(): array
    {
        return [
            ['q', 'string', 'max' => 255],
            ['q', 'trim'],
            [['dateFrom', 'dateTo'], 'date', 'format' => 'php:Y-m-d'],
            ['dateField', 'in', 'range' => array_keys(self::dateFieldList())],
            ['sort', 'in', 'range' => array_keys(self::sortMap())],
        ];
    }

    /**
     * {@inheritdoc}
     *
     * Форма приходит из GET без имени модели, чтобы адрес читался: ?q=устав&sort=title_asc
     */
    public function formName(): string
    {
        return '';
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels(): array
    {
        return [
            'q' => 'Название',
            'dateFrom' => 'С',
            'dateTo' => 'По',
            'dateField' => 'Дата',
            'sort' => 'Сортировать по',
        ];
    }

    /**
     * Загружает и проверяет параметры запроса.
     *
     * Некорректные значения (чужой ключ сортировки, дата не того формата)
     * откатываются к умолчанию: список показывается всегда, ошибок посетителю
     * фильтр не показывает.
     */
    public function loadRequest(array $params): static
    {
        // В адресе может оказаться что угодно, вплоть до ?q[]=1: до присваивания
        // типизированным свойствам доходят только скалярные значения.
        $this->load(array_filter($params, static fn(mixed $value): bool => is_scalar($value)));

        if (!$this->validate()) {
            foreach (array_keys($this->getErrors()) as $attribute) {
                $this->$attribute = match ($attribute) {
                    'dateField' => self::DATE_UPLOADED,
                    'sort' => self::SORT_DEFAULT,
                    default => null,
                };
            }
            $this->clearErrors();
        }

        return $this;
    }

    /**
     * Задан ли хоть один фильтр — по этому признаку показывается кнопка сброса.
     */
    public function isApplied(): bool
    {
        return $this->searchText() !== null || $this->dateFrom !== null || $this->dateTo !== null;
    }

    /**
     * Строка поиска по названию; null, если посетитель ничего не ввёл.
     */
    public function searchText(): ?string
    {
        $q = is_string($this->q) ? trim($this->q) : '';

        return $q === '' ? null : $q;
    }

    /**
     * Фильтр применяется к дате самого документа, а не к дате загрузки.
     */
    public function filtersByDocumentDate(): bool
    {
        return $this->dateField === self::DATE_DOCUMENT;
    }

    /**
     * Нижняя граница диапазона дат в том виде, в каком колонка хранится в БД.
     *
     * Дата загрузки хранится в UTC, поэтому местная полночь переводится в UTC;
     * дата документа — обычная дата без часового пояса и переводу не подлежит.
     *
     * @throws \DateMalformedStringException
     * @throws \DateInvalidTimeZoneException
     */
    public function dateFromValue(): ?string
    {
        if ($this->dateFrom === null) {
            return null;
        }

        return $this->filtersByDocumentDate()
            ? $this->dateFrom
            : $this->localToUtc($this->dateFrom . ' 00:00:00');
    }

    /**
     * Верхняя граница диапазона дат, включая последний день целиком.
     *
     * @throws \DateMalformedStringException
     * @throws \DateInvalidTimeZoneException
     */
    public function dateToValue(): ?string
    {
        if ($this->dateTo === null) {
            return null;
        }

        return $this->filtersByDocumentDate()
            ? $this->dateTo
            : $this->localToUtc($this->dateTo . ' 23:59:59');
    }

    /**
     * Порядок сортировки для ORDER BY.
     *
     * @return array<string,int>
     */
    public function orderBy(): array
    {
        $map = self::sortMap();
        $order = ($map[$this->sort] ?? $map[self::SORT_DEFAULT])['order'];

        // Дата документа заполнена не у всех записей, а название не уникально:
        // добавляем стабильный второй ключ, иначе порядок «прыгает» между страницами.
        return $order + ['d.id' => SORT_DESC];
    }

    /**
     * @throws \DateMalformedStringException
     * @throws \DateInvalidTimeZoneException
     */
    private function localToUtc(string $local): string
    {
        return new DateTimeImmutable($local, new DateTimeZone(Yii::$app->timeZone))
            ->setTimezone(new DateTimeZone('UTC'))
            ->format('Y-m-d H:i:s');
    }
}
