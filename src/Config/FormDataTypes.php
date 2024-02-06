<?php 
namespace Sygecon\AdminBundle\Config;

final class FormDataTypes
{
    public const GROUP_TITLE = 'groupTitle_';  

    public const BTN_TITLE = 'Add model template'; 

    public const ATTRIBUTES = '"label": "",' . "\n\t\t\t" . '"title": "",' . "\n\t\t\t" . '"dataFilter": "",' . "\n\t\t\t";
    
    public const INPUTS = [
        'All' => [],

        'Text' => ['tab' => ['varchar', 'char', 'integer', 'array', 'function', 'class', 'const'],
            'styles' => [], 'scripts' => []
        ],

        'Textarea' => ['tab' => ['longtext', 'text', 'varchar', 'blob'], 
            'styles' => [], 'scripts' => []
        ],

        'Textarea-TinyMCE-Full' => ['tab' => ['longtext', 'text', 'varchar', 'blob'], 
            'styles' => [], 'scripts' => []
        ],

        'Textarea-TinyMCE' => ['tab' => ['varchar', 'text'],
            'styles' => [], 'scripts' => []
        ],

        'Number' => ['tab' => ['integer', 'smallint', 'bigint', 'char', 'varchar', 'array', 'function', 'class', 'const'],
            'styles' => [], 'scripts' => []
        ],
        
        'Current' => ['tab' => ['decimal', 'numeric', 'real', 'float', 'char', 'varchar', 'array', 'function', 'class', 'const'],
            'styles' => [], 'scripts' => []
        ],
        
        'Files-Image' => ['tab' => ['varchar', 'array', 'function', 'class', 'const'],
            'styles' => [], 'scripts' => []
        ],
        
        'Files-Image-List' => ['tab' => ['varchar', 'text', 'longtext', 'array', 'function', 'class', 'const'],
            'styles' => [], 'scripts' => []
        ],

        'Files-Pdf' => ['tab' => ['varchar', 'array', 'function', 'class', 'const'],
            'styles' => [], 'scripts' => []
        ],

        'Files-Page' => ['tab' => ['varchar'],
            'styles' => [], 'scripts' => []
        ],

        'Files-Page-List' => ['tab' => ['varchar', 'text'],
            'styles' => [], 'scripts' => []
        ],

        'DateTime' => ['tab' => ['datetime', 'integer', 'char', 'varchar', 'array', 'function', 'class', 'const'],
            'styles' => [], 'scripts' => []
        ],
        
        'Date' => ['tab' => ['date', 'integer', 'char', 'varchar', 'array', 'function', 'class', 'const'],
            'styles' => [], 'scripts' => []
        ],
        
        'Time' => ['tab' => ['time', 'integer', 'char', 'varchar', 'array', 'function', 'class', 'const'],
            'styles' => [], 'scripts' => []
        ],
        
        'Checkbox' => ['tab' => ['bool', 'smallint', 'char', 'array', 'function', 'class', 'const'],
            'styles' => [], 'scripts' => []
        ],
        
        'Radio' => ['tab' => ['bool', 'smallint', 'char', 'array', 'function', 'class', 'const'],
            'styles' => [], 'scripts' => []
        ],
        
        'Hidden' => ['tab' => ['integer', 'bigint', 'smallint', 'bool', 'varchar', 'char', 'datetime', 'date', 'time'],
            'styles' => [], 'scripts' => []
        ],
        
        'isHidden' => ['tab' => ['integer', 'bigint', 'smallint', 'bool', 'varchar', 'char', 'datetime', 'date', 'time'],
            'styles' => [], 'scripts' => []
        ]
    ];

    public const VARIABLES = [
        'groupTitle_1'  => 'From Data Base',
        
        'varchar'       => "\t\t" . '"_title": {' . "\n\t\t\t" . '%fattrib%' . '"formType": "%ftype%",' . "\n\t\t\t" .  
            '"type": "VARCHAR",' . "\n\t\t\t" . '"constraint": 255,' . "\n\t\t\t" . '"null": false,' . "\n\t\t\t" . '"default": ""' . "\n\t\t}",
        
        'char'          => "\t\t" . '"_name": {' . "\n\t\t\t" . '%fattrib%' . '"formType": "%ftype%",' . "\n\t\t\t" . 
            '"type": "CHAR",' . "\n\t\t\t" . '"constraint": 255,' . "\n\t\t\t" . '"null": false,' . "\n\t\t\t" . '"default": ""' . "\n\t\t}",
        
        'text'          => "\t\t" . '"summary": {' . "\n\t\t\t" . '%fattrib%' . '"formType": "%ftype%",' . "\n\t\t\t" . 
            '"type": "TEXT",' . "\n\t\t\t" . '"default": ""' . "\n\t\t}",

        'longtext'      => "\t\t" . '"content": {' . "\n\t\t\t" . '%fattrib%' . '"formType": "%ftype%",' . "\n\t\t\t" . 
            '"type": "LONGTEXT",' . "\n\t\t\t" . '"default": ""' . "\n\t\t}",
        
        'integer'           => "\t\t" . '"_id": {' . "\n\t\t\t" . '%fattrib%' . '"formType": "%ftype%",' . "\n\t\t\t" . 
            '"type": "INT",' . "\n\t\t\t" . '"constraint": 11,' . "\n\t\t\t" . '"unsigned": true,' . "\n\t\t\t" . '"null": false,' . "\n\t\t\t" . '"default": 0' . "\n\t\t}",
        
        'bigint'        => "\t\t" . '"_id": {' . "\n\t\t\t" . '%fattrib%' . '"formType": "%ftype%",' . "\n\t\t\t" . 
            '"type": "BIGINT",' . "\n\t\t\t" . '"constraint": 20,' . "\n\t\t\t" . '"unsigned": true,' . "\n\t\t\t" . '"null": false,' . "\n\t\t\t" . '"default": 0' . "\n\t\t}",
        
        'smallint'      => "\t\t" . '"_number": {' . "\n\t\t\t" . '%fattrib%' . '"formType": "%ftype%",' . "\n\t\t\t" . 
            '"type": "SMALLINT",' . "\n\t\t\t" . '"constraint": 5,' . "\n\t\t\t" . '"default": ""' . "\n\t\t}",
        
        'datetime'      => "\t\t" . '"_datetime": {' . "\n\t\t\t" . '%fattrib%' . '"formType": "%ftype%",' . "\n\t\t\t" . 
            '"type": "DATETIME",' . "\n\t\t\t" . '"null": true' . "\n\t\t}",
        
        'date'          => "\t\t" . '"_date": {' . "\n\t\t\t" . '%fattrib%' . '"formType": "%ftype%",' . "\n\t\t\t" . 
            '"type": "DATE",' . "\n\t\t\t" . '"null": true' . "\n\t\t}",
        
        'time'          => "\t\t" . '"_time": {' . "\n\t\t\t" . '%fattrib%' . '"formType": "%ftype%",' . "\n\t\t\t" . 
            '"type": "TIME",' . "\n\t\t\t" . '"null": true' . "\n\t\t}",
        
        'bool'          => "\t\t" . '"is_": {' . "\n\t\t\t" . '%fattrib%' . '"formType": "%ftype%",' . "\n\t\t\t" . 
            '"type": "BOOLEAN",' . "\n\t\t\t" . '"null": true,' . "\n\t\t\t" . '"default": 1' . "\n\t\t}",
        
        'decimal'       => "\t\t" . '"_decimal": {' . "\n\t\t\t" . '%fattrib%' . '"formType": "%ftype%",' . "\n\t\t\t" . 
            '"type": "DECIMAL",' . "\n\t\t\t" . '"constraint": "18,4",' . "\n\t\t\t" . '"null": true' . "\n\t\t}",
        
        'numeric'       => "\t\t" . '"_numeric": {' . "\n\t\t\t" . '%fattrib%' . '"formType": "%ftype%",' . "\n\t\t\t" . 
            '"type": "NUMERIC",' . "\n\t\t\t" . '"constraint": "18,2",' . "\n\t\t\t" . '"null": true' . "\n\t\t}",
        
        'real'          => "\t\t" . '"_real": {' . "\n\t\t\t" . '%fattrib%' . '"formType": "%ftype%",' . "\n\t\t\t" . 
            '"type": "REAL",' . "\n\t\t\t" . '"null": true' . "\n\t\t}",
        
        'float'         => "\t\t" . '"_float": {' . "\n\t\t\t" . '%fattrib%' . '"formType": "%ftype%",' . "\n\t\t\t" . 
            '"type": "FLOAT",' . "\n\t\t\t" . '"null": true' . "\n\t\t}",
        
        'blob'          => "\t\t" . '"_blob": {' . "\n\t\t\t" . '%fattrib%' . '"formType": "%ftype%",' . "\n\t\t\t" . 
            '"type": "BLOB",' . "\n\t\t\t" . '"null": true' . "\n\t\t}",

        'groupTitle_2'  => 'From variables',
        
        'array'         => "\t\t" . '"array": {' . "\n\t\t\t" . '%fattrib%' . '"formType": "%ftype%",' . "\n\t\t\t" . 
            '"callback": ""' . "\n\t\t}",
        
        'function'      => "\t\t" . '"function": {' . "\n\t\t\t" . '%fattrib%' . '"formType": "%ftype%",' . "\n\t\t\t" . 
            '"callback": ""' . "\n\t\t}",
        
        'class'         => "\t\t" . '"class": {' . "\n\t\t\t" . '%fattrib%' . '"formType": "%ftype%",' . "\n\t\t\t" . 
            '"callback": ""' . "\n\t\t}",
        
        'const'         => "\t\t" . '"constant": {' . "\n\t\t\t" . '%fattrib%' . '"formType": "%ftype%",' . "\n\t\t\t" . 
            '"callback": ""' . "\n\t\t}",

        'relationship'  => "\t\t" . '"%tab_name%": {' . "\n\t\t\t" . '%fattrib%' . '"formType": "%ftype%",' . "\n\t\t\t" . 
            '"relation": "left",' . "\n\t\t}",
    ];

    // $skeleton
    public const SCELETON = '{' . 
        "\n\t" . '"head": {' .
        "\n\t\t" . '"asp-box": "fetch|getUserData",' .
        "\n\t\t" . '"asp-all-form-class": ".card-body",' .
        "\n\t\t" . '"collapsed": false,' .
        // "\n\t\t" . '"select-language": true,' .
        "\n\t\t" . '"icon": "receipt-cutoff",' .
        "\n\t\t" . '"btn-save": "save-form-data"' .
        "\n\t" . '},' . 
        "\n\t" . '"fields": {' . 
        "\n\t\t" . '"summary": {' .
        "\n\t\t\t" . '"label": "{lang|HeadLines.formModel.content}",' .
        "\n\t\t\t" . '"title": "{lang|HeadLines.formModel.content}",' .
        "\n\t\t\t" . '"formType": "Textarea-TinyMCE-Full",' .
        "\n\t\t\t" . '"type": "VARCHAR"' .
        "\n\t\t" . '}' .    
        "\n\t" . '}' . 
        "\n" . '}'
    ;
}