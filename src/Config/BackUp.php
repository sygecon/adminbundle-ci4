<?php 
namespace Sygecon\AdminBundle\Config;

final class BackUp
{
    public const MAXIMUM_BACKUPS = 3;

    public const DB_BACKUP = [
        'default'
    ];

    public const FOLDERS_BACKUP = [
        APPPATH,
        FCPATH,
        WRITEPATH . 'base'
    ];

    public const DB_TYPE = [
        'mysqli'    => 'MySql',
        'postgre'   => 'PostgreSql',
        'sqlite3'   => 'Sqlite',
        'mongo'     => 'MongoDb'
    ];

    public const FORMAT_FOLDER_NAME = 'bk_d_m_Y';

    public const FOLDER  = WRITEPATH . 'backup';

    public const PREFIX_DUMPER_DB = '\\Sygecon\AdminBundle\\Libraries\\Dumper\\Databases\\';
}