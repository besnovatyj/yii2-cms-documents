/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

(function () {
  // Поле названия документа, а не одноимённое поле блока SEO ниже по форме.
  const TITLE_SELECTOR = 'input[name="DocumentForm[title]"]';
  const UNDO_ID = 'documents-title-undo';

  // Имя файла в виде, пригодном для названия записи.
  function titleFromFileName(fileName) {
    return fileName
      .replace(/\.[^/.]+$/, "") // удаляем расширение
      .replace(/[-_]/g, ' ') // заменяем дефисы и подчеркивания на пробелы
      .replace(/\s+/g, ' ') // убираем множественные пробелы
      .trim();
  }

  // Предлагает вернуть прежнее название: при обновлении записи оно заменяется
  // именем файла, а название могли задать вручную и осмысленно.
  function offerTitleUndo(titleField, previousTitle) {
    // Блок уже показан — значит прежним остаётся название до первой подстановки,
    // а не промежуточное имя предыдущего выбранного файла.
    if (document.getElementById(UNDO_ID)) {
      return;
    }

    const hint = document.createElement('div');
    hint.id = UNDO_ID;
    hint.className = 'form-text';
    hint.append('Название заменено именем файла. ');

    const undo = document.createElement('a');
    undo.href = '#';
    // Название может быть длинным — в ссылке показываем начало, вставится оно целиком.
    undo.textContent = previousTitle.length > 60
      ? `Вернуть «${previousTitle.slice(0, 60)}…»`
      : `Вернуть «${previousTitle}»`;
    undo.addEventListener('click', function (event) {
      event.preventDefault();
      titleField.value = previousTitle;
      hint.remove();
    });

    hint.append(undo);
    titleField.insertAdjacentElement('afterend', hint);
  }

  document.addEventListener('change', function (event) {
    // Обработка переключения между файлом и ссылкой
    if (event.target.classList.contains('form-check-input')) {
      const value = event.target.value;

      const fileField = document.getElementById('file-field');
      const linkField = document.getElementById('link-field');

      if (value === 'file') {
        fileField.classList.remove('d-none');
        linkField.classList.add('d-none');
      } else if (value === 'link') {
        fileField.classList.add('d-none');
        linkField.classList.remove('d-none');
      }
    }

    // Обработка выбора файла - подстановка названия в поле title
    if (event.target.type === 'file' && event.target.files.length > 0) {
      const titleField = document.querySelector(TITLE_SELECTOR);
      if (!titleField) {
        return;
      }

      const formattedName = titleFromFileName(event.target.files[0].name);
      const previousTitle = titleField.value.trim();

      if (formattedName === '' || formattedName === previousTitle) {
        return;
      }

      // Подставляем и при обновлении записи: иначе можно заменить файл и
      // оставить название от прежнего.
      titleField.value = formattedName;

      if (previousTitle !== '') {
        offerTitleUndo(titleField, previousTitle);
      }
    }
  });
})();
