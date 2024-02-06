<?php

return [
    'field'     => 'Поле',
    'clearForm' => 'Очистить форму',
    'save'      => 'Сохранить',
    'btnConfirmSave'=> 'Сохранить сделанные вами изменения?',
    'mainSettings'  => 'Основные параметры',
    'extraOptions'  => 'Дополнительные опции',
    'announce'      => 'Анонс',
    'createConfigTemplate'=> 'Создать шаблон конфигурации',
    /**
     * Form label Models for data pages
    */
    'formModel' => [
        'name'  => 'Наименование',
        'title' => 'Заглавие',
        'description' => 'Описание',
        'content' => 'Контент',
        'author'  => 'Автор',
        'imageLink' => 'Ссылка',
        'linkPage'  => 'Ссылка на страницу',
        'license'   => 'Лицензия',
        'linkCertificates' => 'Ссылка на сертификаты',
        'photoGallery'  => 'Фотогаллерея',
        'jobTitle'      => 'Должность',
        'specialization'=> 'Специализация',
        'qualification' => 'Квалификация',
        'poster' => 'Картинка для заставки, пока файл не открыт',
        'tagAlt' => 'Тег ALT - название изображения',
        'linkText' => 'Текст ссылки',
        'tooltip' => 'Всплывающая подсказка - краткое описание',
    ],
    /**
     * Catalog and Pages
    */
    'catalog' => [
        'btnAdd'        => 'Добавить страницу',
        'msgErrorCreate'=> 'Ошибка! Не удалось создать новую страницу в каталоге.',
        'msgUpdate'     => 'Данные успешно изменены.',
        'msgCreate'     => 'Страница успешно создана.',
        'model'         => 'Модель',
        'sheetLayout'   => 'Модели макета страницы',
        'allSheet'      => 'Все Модели',
        'selSheet'      => 'Выбранные Модели',
        'addFile'       => 'Добавить файл',
        'control'       => 'Контроллера',
        'className'     => 'Название класса',
        'classModel'    => 'Модель класса',
        'designTemplate'=> 'Шаблон дизайна',
        'removeFromList'=> 'Удалить из списка',
        'frm' => [
            'slug' => 'Псевдостатический адрес',
            'hint' => 'Всплывающая подсказка',
            'imageAnnounce' => 'Картинка для анонса',
            'setingTitle' => 'Основные параметры страницы',
            'addChildPage' => 'Добавить подстраницу',
            'searchDeny' => 'Исключить из поиска',
            'navDeny' => 'Исключить из навигации',
            'datePublication' => 'Дата публикации',
            'pageLayout' => 'Макет страницы',
            'primaryIdCat' => 'Первичный раздел (Левый)',
            'secondaryIdCat' => 'Вторичный раздел (Правый)',
            'tableDataStructure' => 'Структура данных таблицы',
            'formPageBlockTemplate' => ' шаблона',
            'formPageBlockData' => ' формы, для данных блока',
            'formPageModelShow' => ', для формирования страницы',
            'formPageModelAdmin' => ', для администратора страниц',
            'filesGeneratingDataForm' => 'Файлы для формирования страницы (стили, скрипты)',
            'preventIndexing' => 'Запретить индексацию',
            'hint' => [
                'name' => 'Выводится в меню, списках, путях. Рекомендуется давать страницам краткие названия, тогда они поместятся в меню.',
                'slug' => 'Псевдостатический адрес (URL) будет показан в адресной строке браузера.',
                'h1' => 'Заголовок страницы (H1) - один из важнейших тегов логической разметки текста, который учитывается поисковыми системами.',
                'metaTitle' => 'Cтрока, которая будет выведена в заголовке окна браузера. Этот тег важен для поисковой оптимизации. Рекомендуется 70 - 80 символов.',
                'metaDescriptions' => 'Небольшой текст, описывающий содержание страницы. Также может учитываться поисковыми системами, выводиться в качестве пояснения в результатах поиска.',
                'metaKeywords' => 'Ключевые слова и фразы разделенные запятой, не учитываются поисковыми системами.',
                'dataCreate' => 'Дата создания страницы.',
                'dataType' => 'Тип данных шаблона',
                'preventIndexing' => 'Запретить индексацию страницы поисковым роботам.',
            ]
        ]
    ],
    'layout' => [
        'typeLabel' => 'Тип данных',
        'typeTitle' => 'Вариант представления данных на странице',
    ]
];