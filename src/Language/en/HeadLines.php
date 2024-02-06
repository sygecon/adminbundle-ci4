<?php

return [
    'field'     => 'Field',
    'clearForm' => 'Clear form',
    'save'      => 'Save',
    'btnConfirmSave'=> 'Do you want to save the changes you made?',
    'mainSettings'  => 'Main Settings',
    'extraOptions'  => 'Extra Options',
    'announce'      => 'Announcement',
    'createConfigTemplate'=> 'Create configuration template',
    /**
     * Form label Models for data pages
    */
    'formModel' => [
        'name' => 'Name',
        'title' => 'Title',
        'description' => 'Description',
        'content' => 'Content',
        'author' => 'Author',
        'imageLink' => 'Image link',
        'linkPage' => 'Link to page',
        'license' => 'License',
        'linkCertificates' => 'Link to certificates',
        'photoGallery' => 'Photo Gallery',
        'jobTitle' => 'Position',
        'specialization' => 'Specialization',
        'qualification' => 'Qualification',
        'poster' => 'Screensaver image while the file is not open',
        'tagAlt' => 'ALT tag - image title',
        'linkText' => 'Link text',
        'tooltip' => 'Tooltip - Brief Description',
    ],
    /**
     * Catalog and Pages
     */
    'catalog' => [
        'btnAdd' => 'Add page',
        'msgErrorCreate' => 'Error! Failed to create a new page in the directory.',
        'msgUpdate' => 'Data changed successfully.',
        'msgCreate' => 'The page has been successfully created.',
        'model' => 'Model',
        'sheetLayout' => 'Page layout models',
        'allSheet' => 'All models',
        'selSheet' => 'Selected models',
        'addFile' => 'Add file',
        'control' => 'Controller',
        'className' => 'Class name',
        'classModel' => 'Class Model',
        'designTemplate' => 'Design Template',
        'removeFromList' => 'Remove from list',
        'frm' => [
            'slug' => 'Pseudostatic address',
            'hint' => 'Tooltip',
            'imageAnnounce' => 'Image for the announcement',
            'setingTitle' => 'Basic page parameters',
            'addChildPage' => 'Add child page',
            'searchDeny' => 'Exclude from search',
            'navDeny' => 'Exclude from navigation',
            'datePublication' => 'Date of publication',
            'pageLayout' => 'Page layout',
            'tableDataStructure' => 'Table data structure',
            'primaryIdCat' => 'Primary section (Left)',
            'secondaryIdCat' => 'Secondary section (Right)',
            'formPageBlockTemplate' => ' template',
            'formPageBlockData' => ' form, for block data',
            'formPageModelShow' => ', to form the page',
            'formPageModelAdmin' => ', for page administrator',
            'filesGeneratingDataForm' => 'Page generation files (styles, scripts)',
            'preventIndexing' => 'Prevent indexing',
            'hint' => [
                'name' => 'Displayed in menus, lists, paths. It is recommended to give pages short titles so they will fit in the menu.',
                'slug' => 'A pseudo-static address (URL) will be shown in the address bar of the browser.',
                'h1' => 'The title of the page (H1) is one of the most important logical markup tags for search engines.',
                'metaTitle' => 'The string that will be displayed in the title of the browser window. This tag is important for search engine optimization. Recommended 70 - 80 characters.',
                'metaDescriptions' => 'Small text describing the content of the page. It may also be taken into account by search engines, displayed as an explanation in search results.',
                'metaKeywords' => 'Comma-separated keywords and phrases are not considered by search engines.',
                'dataCreate' => 'Page creation date.',
                'dataType' => 'Template data type',
                'preventIndexing' => 'Prevent page indexing by search robots.',
            ]
        ]
    ],
    'layout' => [
        'typeLabel' => 'Data type',
        'typeTitle' => 'Page data presentation option',
    ]
];