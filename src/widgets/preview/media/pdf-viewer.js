/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

/**
 * Встроенный просмотрщик PDF на pdf.js.
 *
 * Показывает по одной странице, подгоняя её под ширину контейнера. Постраничный
 * показ выбран сознательно: документ на сотню страниц не должен рисоваться
 * целиком, чтобы открыться.
 *
 * Воркер и шрифты берутся из папки рядом с этим файлом: Yii публикует ресурсы
 * пакета в каталог со случайным именем, и вычислять их адрес на стороне PHP
 * не пришлось бы иначе.
 */

import * as pdfjsLib from './pdfjs/pdf.min.js';

pdfjsLib.GlobalWorkerOptions.workerSrc = new URL('./pdfjs/pdf.worker.min.js', import.meta.url).href;

/** Базовые шрифты PDF на случай, если в самом файле они не встроены. */
const STANDARD_FONT_DATA_URL = new URL('./pdfjs/standard_fonts/', import.meta.url).href;

/** Во сколько раз холст может быть детальнее CSS-пикселей — ограничение против перерасхода памяти. */
const MAX_PIXEL_RATIO = 2;

class PdfViewer {
    /**
     * @param {HTMLElement} root Корневой элемент виджета
     */
    constructor(root) {
        this.root = root;
        this.canvas = root.querySelector('[data-pdf-canvas]');
        this.stage = root.querySelector('[data-pdf-stage]');
        this.status = root.querySelector('[data-pdf-status]');
        this.prevButton = root.querySelector('[data-pdf-prev]');
        this.nextButton = root.querySelector('[data-pdf-next]');

        this.document = null;
        this.pageNumber = 1;
        this.renderTask = null;
        this.renderedWidth = 0;

        this.prevButton.addEventListener('click', () => this.goTo(this.pageNumber - 1));
        this.nextButton.addEventListener('click', () => this.goTo(this.pageNumber + 1));

        // Перерисовываем только при смене ширины: изменение высоты окна
        // (например, при скрытии адресной строки на телефоне) масштаб не меняет.
        this.resizeObserver = new ResizeObserver(() => {
            if (this.document && Math.round(this.stage.clientWidth) !== this.renderedWidth) {
                this.renderPage();
            }
        });
        this.resizeObserver.observe(this.stage);
    }

    /**
     * Загружает документ и показывает первую страницу.
     */
    async load() {
        const source = this.root.dataset.src;

        try {
            this.document = await pdfjsLib.getDocument({
                url: source,
                standardFontDataUrl: STANDARD_FONT_DATA_URL,
            }).promise;
        } catch (error) {
            this.fail(error);
            return;
        }

        this.root.classList.add('is-ready');
        await this.renderPage();
    }

    /**
     * Переходит на страницу, если такая есть.
     *
     * @param {number} pageNumber
     */
    async goTo(pageNumber) {
        if (!this.document || pageNumber < 1 || pageNumber > this.document.numPages) {
            return;
        }

        this.pageNumber = pageNumber;
        await this.renderPage();
    }

    /**
     * Рисует текущую страницу по ширине контейнера.
     */
    async renderPage() {
        if (this.renderTask) {
            this.renderTask.cancel();
            this.renderTask = null;
        }

        let page;
        try {
            page = await this.document.getPage(this.pageNumber);
        } catch (error) {
            this.fail(error);
            return;
        }

        const stageWidth = Math.round(this.stage.clientWidth);
        const unscaled = page.getViewport({ scale: 1 });
        const scale = stageWidth > 0 ? stageWidth / unscaled.width : 1;
        const viewport = page.getViewport({ scale });
        const pixelRatio = Math.min(window.devicePixelRatio || 1, MAX_PIXEL_RATIO);

        this.canvas.width = Math.floor(viewport.width * pixelRatio);
        this.canvas.height = Math.floor(viewport.height * pixelRatio);
        this.canvas.style.width = `${Math.floor(viewport.width)}px`;
        this.canvas.style.height = `${Math.floor(viewport.height)}px`;
        this.renderedWidth = stageWidth;

        const task = page.render({
            canvas: this.canvas,
            viewport,
            transform: pixelRatio === 1 ? null : [pixelRatio, 0, 0, pixelRatio, 0, 0],
        });
        this.renderTask = task;

        try {
            await task.promise;
        } catch (error) {
            // Отмена — обычное дело при быстром листании или изменении размера окна.
            if (error?.name !== 'RenderingCancelledException') {
                this.fail(error);
                return;
            }
        } finally {
            // Отменённый рендер уступил место следующему — забывать его задачу нельзя.
            if (this.renderTask === task) {
                this.renderTask = null;
            }
        }

        this.updateControls();
    }

    /**
     * Обновляет счётчик страниц и доступность кнопок.
     */
    updateControls() {
        this.status.textContent = `Страница ${this.pageNumber} из ${this.document.numPages}`;
        this.prevButton.disabled = this.pageNumber <= 1;
        this.nextButton.disabled = this.pageNumber >= this.document.numPages;
    }

    /**
     * Показывает, что документ не открылся: посетителю остаётся кнопка скачивания.
     *
     * @param {unknown} error
     */
    fail(error) {
        console.error('Не удалось показать PDF', error);
        this.root.classList.add('is-failed');
        this.status.textContent = 'Просмотр недоступен — документ можно скачать.';
        this.prevButton.disabled = true;
        this.nextButton.disabled = true;
    }
}

document.querySelectorAll('[data-pdf-viewer]').forEach((root) => {
    new PdfViewer(root).load();
});
