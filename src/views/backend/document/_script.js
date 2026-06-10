/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

document.addEventListener('change', function(event) {
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
    const titleField = document.querySelector('input[name*="title"]');
    const file = event.target.files[0];

    // Подставляем название только если поле title пустое
    if (titleField && !titleField.value.trim()) {
      // Убираем расширение файла и приводим к красивому формату
      const fileName = file.name.replace(/\.[^/.]+$/, ""); // удаляем расширение
      const formattedName = fileName
        .replace(/[-_]/g, ' ') // заменяем дефисы и подчеркивания на пробелы
        .replace(/\s+/g, ' ') // убираем множественные пробелы
        .trim();

      titleField.value = formattedName;
    }
  }
});
