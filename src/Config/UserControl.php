<?php 
namespace Sygecon\AdminBundle\Config;

final class UserControl
{
    public const FORM_JSON_PATH = 'control/form/user/';

    public const CACHE_FIELDS   = 'Fields_User_Data';

    // ! Don't change! Otherwise, the data of all users will need to be re-entered.
    public const ENCRYPT_KEY    = 'e74ngh8sUD9234d01932Qr8Hfmx9KsXce67f';
    public const SALT           = 'TosOchka7Qdb';
}