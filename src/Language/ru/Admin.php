<?php

return [
    'goHome'        => 'Главная',
    'goWelcome'     => 'Добро пожаловать!',
    'goBack'        => 'Вернуться',
    'IdNotFound'    => 'Не найден идентификатор.',
    'selectItem'    => 'Выберите элемент',
    'interfaceLanguage'     => 'Язык интерфейса',
    'pageLanguage'  => 'Язык страниц',
    'editTitle'     => 'Редактор',
    'editorTitle'   => 'Редактор кода',
    'editorHTML'    => 'HTML Редактор',
    'default'   => 'По умолчанию',
    'type'      => 'Тип',
    'page'      => 'Страница',
    'list'      => 'Список страниц',
    'changePassword'        => 'Изменить пароль',
    'changePasswordTitle'   => 'Изменение пароля пользователя',
    'newPassword'           => 'Новый пароль',
    'titleBtnMinify'        => 'Объединить (Minify)',
    'descBtnMinify'   => 'Объединить скрипты и файлы стилей в минифайлы (theme.min.css, theme.min.js)',
    'titleDlgMinify'  => 'Вы действительно хотите объединить скрипты и файлы стилей в минифайлы?',
    'titleBtnSitemap' => 'СЕО генератор (sitemap ...)',
    'global' => [
        'name'   => 'Название',
        'logo'   => 'Логотип',
        'dlgCreateTitle' => 'Параметры создаваемого объекта',
        'close'  => 'Закрыть',
        'action' => 'Действие',
        'logout' => 'Выйти',
    ],
    'error' => [
        'notHavePermissionHead' => 'Ошибка 403 (Forbidden)! Доступ к запрошенной странице запрещён.',
        'notHavePermission' => 'У вас нет прав доступа к этой странице.',
        'notFindPageHead' => 'Ошибка 404! Страница не найдена.',
        'notFindPage' => 'Извините! Не удается найти страницу, которую вы искали.'
    ],
    /**
     * Language.
     */
    'language' => [
        'add'       => 'Добавить язык',
        'edit'      => 'Разрешение на редактирование',
        'title'     => 'Управление списком языков',
        'title_add' => 'Создание нового языка',
        'subtitle'  => 'Список языков',
        'fields'    => [
            'name'              => 'Название',
            'description'       => 'Описание',
            'plc_name'          => 'Имя',
            'plc_description'   => 'Описание',
            'icon'              => 'Иконка',
        ],
        'msg' => [
            'msg_insert'   => 'Язык успешно добавлено.',
            'msg_update'   => 'Язык c id {0} успешно изменено.',
            'msg_delete'   => 'Язык c id {0} успешно удалено.',
            'msg_get'      => 'Язык c id {0} успешно получено.',
            'msg_get_fail' => 'Язык c id {0} не найдено или уже удалено.',
        ],
    ],
    /**
     * Permission.
     */
    'permission' => [
        'add'       => 'Добавить разрешение',
        'edit'      => 'Разрешение на редактирование',
        'title'     => 'Управление разрешениями',
        'title_add' => 'Создание нового разрешения',
        'subtitle'  => 'Список разрешений',
        'fields'    => [
            'name'            => 'Название',
            'description'     => 'Описание',
            'plc_name'        => 'Название разрешения',
            'plc_description' => 'Описание для разрешения',
        ]
    ],
    /**
     * Groups
     */
    'group' => [
        'fields' => [
            'name'  => 'Группа',
        ],
        'msg' => [
            'msg_get_fail' => 'Группа c id {0} не найдена или уже удалена.',
        ]
    ],
    /**
     * Navigation Bars
     */
    'navbar' => [
        'title' => 'Навигационные панели',
        'name' => 'Название',
        'add'       => 'Создать',
        'title_add' => 'Создание Навигационной панели',
        'menuAdd' => 'Создание меню для сайта',
        'msg' => [
            'msg_insert'     => 'Объект успешно добавлен.',
            'msg_update'     => 'Объект был успешно изменен.',
            'msg_delete'     => 'Объект успешно удален.',
            'msg_get'        => 'Объект получен.',
            'msg_get_fail'   => 'Объект не найден или уже удален.',
            'msg_fail_order' => 'Не удалось изменить порядок меню.',
        ],
    ],
    /**
     * Menu.
     */
    'menu' => [
        'expand'   => 'Развернуть',
        'collapse' => 'Свернуть',
        'refresh'  => 'Обновить',
        'add'      => 'Добавить данные',
        'edit'     => 'Редактировать данные',
        'title'    => 'Управление меню',
        'subtitle' => 'Список меню',
        'profileName' => 'Профиль',
        'profileDesc' => 'Перейти в ваш аккаунт',
        'postsName'   => 'Сообщения',
        'postsDesc'   => 'Ваши сообщения',
        'chatName'    => 'Чат',
        'chatDesc'    => 'Перейти в ваш чат',
        'sidebar' => [
            'catalogName' => 'Каталог страниц',
            'catalogDesc' => 'Каталог страниц',
            'pageEditor' => 'Редактор страницы',
            'navName' => 'Навигация',
            'navDesc' => 'Навигация, меню',
            'navPanelsName' => 'Панели',
            'navPanelsDesc' => 'Пользовательские панели',
            'navMenuName' => 'Меню',
            'navMenuDesc' => 'Меню страниц сайта',
            'templatesName' => 'Шаблоны',
            'templatesDesc' => 'Шаблоны',
            'themesName' => 'Темы',
            'themesDesc' => 'Темы',
            'blocksName' => 'Блоки',
            'blocksDesc' => 'Блоки страниц',
            'layoutsName' => 'Макеты',
            'layoutsDesc' => 'Макеты страниц',
            'routeName' => 'Маршрутизация',
            'routeDesc' => 'Маршрутизация страниц',
            'redirectName' => 'Перенаправление',
            'redirectDesc' => 'Перенаправление страниц',
            'modelsName' => 'Модели',
            'modelsDesc' => 'Модели данных',
            'relationName' => 'Связи',
            'relationDesc' => 'Связи между разделами',
            'controllerName' => 'Контроллеры',
            'controllerDesc' => 'Контроллеры загрузки страниц',
            'componentsName' => 'Компоненты',
            'componentsDesc' => 'Структурные компоненты',
            'usersName' => 'Пользователи',
            'usersDesc' => 'Управление данными пользователей',
            'staffName' => 'Персонал',
            'staffDesc' => 'Управление данными сотрудников',
            'clientsName' => 'Клиенты',
            'clientsDesc' => 'Управление данными клиентов',
            'controlName' => 'Управление',
            'controlDesc' => 'Данные пользователей',
            'groupsName' => 'Группы',
            'groupsDesc' => 'Управление группами пользоватей',
            'permissionsName' => 'Разрешения',
            'permissionsDesc' => 'Управление разрешениями',
            'languageName' => 'Список языков',
            'languageDesc' => 'Управление списком языков',
            'variablesName' => 'Переменные',
            'variablesDesc' => 'Глобальные переменные',
            'importName' => 'Импорт',
            'importDesc' => 'Импорт данных из внешних источников',
            'importDataName' => 'Текстовые данные',
            'importDataDesc' => 'Импорт текстовых данных',
            'importResourcesName' => 'Ресурсы',
            'importResourcesDesc' => 'Импорт ресурсов (стили, скрипты, изображения ...)',
        ]
    ],
    // Пользователи
    'user' => [
        'pageManageHeadTitle' => 'Управление пользователями',
        'editDataHeadTitle' => 'Данные пользователя',
        'cardBasicData' => 'Основные данные',
        'cardPersonalData' => 'Персональные данные',
        'cardLegalEntity' => 'Юридическое лицо',
        'formFirstName' => 'Имя',
        'formLastName'  => 'Фамилия',
        'formPatronymic' => 'Отчество',
        'formFullName' => 'Полное имя',
        'formPhone' => 'Номер телефона',
        'formSecondPhone' => 'Второй телефон',
        'formLanguage' => 'Язык интерфейса',
        'add' => 'Зарегистрировать',
        'avatar' => 'Аватар',
        'changeAvatar' => 'Изменить аватар',
        'addDescription' => 'Зарегистрировать нового пользователя',
        'addModalTitle' => 'Вы действительно хотите зарегистрировать нового пользователя?',
        'fieldsPersonalData' => [
            'country'        => 'Страна',
            'region'       => 'Область/район',
            'city'         => 'Город',
            'address'      => 'Адрес',
            'postcode'     => 'Индекс',
            'dateBirth'    => 'Дата рождения',
            'inn'    => 'Идентификационный номер',
            'passportNumber' => 'Номер паспорта',
            'passportIssued' => 'Кем выдан паспорт',
            'passportDateIssued' => 'Дата выдачи',
            'specialty' => 'Специальность',
            'position' => 'Должность',
            'department' => 'Отдел',
            'supplements' => 'Дополнения',
            'isMan' => 'Мужчина',
        ],
        'fields'   => [
            'active'          => 'Активный',
            'profile'         => 'Профиль',
            'join'            => 'Участник с',
            'setting'         => 'Настройка',
            'non_active'      => 'Неактивный',
        ],
        'msg' => [
            'msg_update'   => 'Данные пользователя успешно изменены.',
            'msg_get_fail' => 'Пользователь не найден или уже удален.',
        ],
    ],
    'table' => [
        'filter' => [
            'label' => 'Поиск',
            'placeholder' => 'Найти ...'
        ]
    ]
];
