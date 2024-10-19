<?php 
namespace Sygecon\AdminBundle\Config;

final class UserControl
{
    public const FORM_JSON_PATH = 'control/form/user/';

    public const CACHE_FIELDS   = 'Fields_User_Data';

    /** 
     * ! Don't change! Otherwise, the data of all users will need to be re-entered.
     * ! Copy the key to a secluded place so that if you lose it, you can access the available data.
    */
    public const ENCRYPT_KEY = '546c4ec7f1257515716f562e02df89544069d4e8786ffbf513cfc766';
}