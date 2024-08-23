<?php

return [
    'goHome' => 'Home',
    'goWelcome' => 'Welcome!',
    'goBack' => 'Back to',
    'IdNotFound' => 'ID not found.',
    'selectItem' => 'Select item',
    'interfaceLanguage' => 'Interface language',
    'pageLanguage' => 'Pages language',
    'editTitle' => 'Editor',
    'editorTitle' => 'Code editor',
    'editorHTML'    => 'HTML Editor',
    'default' => 'Default',
    'type' => 'Type',
    'page' => 'Page',
    'list' => 'Page list',
    'changePassword' => 'Change password',
    'changePasswordTitle' => 'Change user password',
    'newPassword' => 'New password',
    'titleBtnMinify' => 'Combine (Minify)',
    'descBtnMinify' => 'Combine scripts and stylesheets into minifiles (theme.min.css, theme.min.js)',
    'titleDlgMinify' => 'Are you sure you want to combine scripts and style files into minifiles?',
    'titleBtnSitemap' => 'SEO builder (sitemap ...)',
    'global' => [
        'name'   => 'Name',
        'logo'   => 'Logo',
        'dlgCreateTitle' => 'Parameters of the created object',
        'close'  => 'Close',
        'action' => 'Action',
        'logout' => 'Logout',
    ],
    'error' => [
        'notHavePermissionHead' => 'Error 403 (Forbidden)! Access to the requested page is denied.',
        'notHavePermission' => 'You do not have permission to access this page.',
        'notFindPageHead' => 'Error 404! Page not found.',
        'notFindPage' => 'Sorry! Cannot seem to find the page you were looking for.'
    ],
    /**
     * Language.
     */
    'language' => [
        'add' => 'Add language',
        'edit' => 'Edit permission',
        'title' => 'Language list management',
        'title_add' => 'Create a new language',
        'subtitle' => 'List of languages',
        'fields'   => [
            'name' => 'Name',
            'description' => 'Description',
            'plc_name' => 'Name',
            'plc_description' => 'Description',
            'icon' => 'Icon',
        ],
        'msg' => [
            'msg_insert' => 'The language was added successfully.',
            'msg_update' => 'Language with id {0} changed successfully.',
            'msg_delete' => 'The language with id {0} was successfully deleted.',
            'msg_get' => 'Language with id {0} received successfully.',
            'msg_get_fail' => 'The language with id {0} could not be found or has already been removed.',
        ],
    ],
    /**
     * Permission.
     */
    'permission' => [
        'add'      => 'Add permission',
        'edit'     => 'Edit permission',
        'title'    => 'Permission management',
        'title_add' => 'Create a new permission',
        'subtitle' => 'Permission list',
        'fields'   => [
            'name'            => 'Permission',
            'description'     => 'Description',
            'plc_name'        => 'Name of permission',
            'plc_description' => 'Description for permission',
        ]
    ],

    /**
     * Groups
     */
    'group' => [
        'fields' => [
            'name'  => 'Group',
        ],
        'msg' => [
            'msg_get_fail' => 'The group id {0} not found or already deleted.',
        ],
    ],
    /**
     * Navigation Bars
     */
    'navbar' => [
        'title' => 'Navigation panels',
        'name' => 'Name',
        'add'       => 'Create',
        'title_add' => 'Create Navigation Bar',
        'menuAdd' => 'Create a menu for the site',
        'msg' => [
            'msg_insert'     => 'Object added successfully.',
            'msg_update'     => 'The object was updated successfully.',
            'msg_delete'     => 'The object was successfully deleted.',
            'msg_get'        => 'The object was successfully received.',
            'msg_get_fail'   => 'The object was not found or has already been deleted.',
            'msg_fail_order' => 'The menu failed the reorder.',
        ]
    ],
    /**
     * Menu.
     */
    'menu' => [
        'expand'   => 'Expand',
        'collapse' => 'Collapse',
        'refresh'  => 'Refresh',
        'add'      => 'Add data',
        'edit'     => 'Edit data',
        'title'    => 'Menu management',
        'subtitle' => 'Menu list',
        'profileName' => 'Profile',
        'profileDesc' => 'Go to your account',
        'postsName'   => 'Posts',
        'postsDesc'   => 'Your messages',
        'chatName'    => 'Chat room',
        'chatDesc'    => 'Go to your chat',
        'passwordResetName' => 'Password reset',
        'passwordResetDesc' => 'Forcing password reset',
        'sidebar' => [
            'catalogName' => 'Catalog',
            'catalogDesc' => 'Pages directory',
            'pageEditor' => 'Page editor',
            'navName' => 'Navigation',
            'navDesc' => 'Navigation, menu',
            'navPanelsName' => 'Panels',
            'navPanelsDesc' => 'Custom panels',
            'navMenuName' => 'Menu',
            'navMenuDesc' => 'Site page menu',
            'templatesName' => 'Templates',
            'templatesDesc' => 'Templates',
            'themesName' => 'Themes',
            'themesDesc' => 'Themes',
            'blocksName' => 'Blocks',
            'blocksDesc' => 'Page blocks',
            'layoutsName' => 'Components',
            'layoutsDesc' => 'Page Components',
            'routeName' => 'Routing',
            'routeDesc' => 'Page Routing',
            'redirectName' => 'Redirect',
            'redirectDesc' => 'Page redirect',
            'modelsName' => 'Models',
            'modelsDesc' => 'Data models',
            'relationName' => 'Relationships',
            'relationDesc' => 'Relationships between sections',
            'controllerName' => 'Controllers',
            'controllerDesc' => 'Page load controllers',
            'componentsName' => 'Components',
            'componentsDesc' => 'Structural components',
            'variablesName' => 'Variables',
            'variablesDesc' => 'Variables',
            'dataTypesName' => 'Data Types',
            'dataTypesDesc' => 'Data Types',
            'usersName' => 'Users',
            'usersDesc' => 'Manage user data',
            'staffName' => 'Staff',
            'staffDesc' => 'Employee data management',
            'clientsName' => 'Clients',
            'clientsDesc' => 'Customer data management',
            'controlName' => 'Control',
            'controlDesc' => 'User data',
            'groupsName' => 'Groups',
            'groupsDesc' => 'Manage user groups',
            'permissionsName' => 'Permissions',
            'permissionsDesc' => 'Manage permissions',
            'languageName' => 'List of languages',
            'languageDesc' => 'Manage the list of languages',
            'variablesName' => 'Variables',
            'variablesDesc' => 'Global variables',
            'importName' => 'Importing',
            'importDesc' => 'Importing from external sources',
            'importDataName' => 'Text data',
            'importDataDesc' => 'Importing text data',
            'importResourcesName' => 'Resources',
            'importResourcesDesc' => 'Import resources (styles, scripts, images...)',
            'projectStructureName' => "Project basis",
            'projectStructureDesc' => "The basis of the project",
        ]
    ],
    // Пользователи
    'user' => [
        'pageManageHeadTitle' => 'User management',
        'editDataHeadTitle' => 'Edit user data',
        'cardBasicData' => 'Basic data',
        'cardPersonalData' => 'Personal data',
        'cardLegalEntity' => 'Company',
        'formFirstName' => 'First name',
        'formLastName'  => 'Last Name',
        'formPatronymic' => 'Patronymic',
        'formFullName' => 'Full name',
        'formPhone' => 'Phone number',
        'formSecondPhone' => 'Second phone',
        'formLanguage' => 'Interface language',
        'add' => 'Register',
        'avatar' => 'Avatar',
        'changeAvatar' => 'Change avatar',
        'addDescription' => 'Register a new user',
        'addModalTitle' => 'Are you sure you want to register a new user?',
        'fieldsPersonalData' => [
            'country'        => 'Country',
            'region'       => 'Region',
            'city'         => 'City',
            'address'      => 'Address',
            'postcode'     => 'Postcode',
            'dateBirth'    => 'Date of birth',
            'inn'           => 'An identification number',
            'passportId'    => 'Passport ID',
            'passportIssued' => 'Passport issued by',
            'passportDateIssued' => 'Date of issue',
            'specialty' => 'Specialty',
            'position' => 'Position',
            'department' => 'Department',
            'supplements' => 'Supplements',
            'isMan' => 'Man',
        ],
        'fields'   => [
            'active'          => 'Active',
            'profile'         => 'Profile',
            'join'            => 'Member since',
            'setting'         => 'Setting',
            'non_active'      => 'Non Active',
        ],
        'msg' => [
            'msg_update'   => 'User data has been correctly modified.',
            'msg_get_fail' => 'The user not found or already deleted.',
        ],
    ],
    'table' => [
        'dataLength' => [
            'label' => 'Show',
            'result' => 'line'
        ],
        'filter' => [
            'label' => 'Search',
            'placeholder' => 'Find ...'
        ]
    ]
];
