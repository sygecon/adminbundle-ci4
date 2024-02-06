<?php
namespace Sygecon\AdminBundle\Controllers\Users;

use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Database\Exceptions\DatabaseException;
use Config\Database;
use Config\AuthGroups;
use App\Config\Boot\NestedTree;
use Sygecon\AdminBundle\Config\UserControl;
use Sygecon\AdminBundle\Config\Paths;
use Sygecon\AdminBundle\Controllers\AdminController;
use Sygecon\AdminBundle\Libraries\ImageResizer;
use Sygecon\AdminBundle\Libraries\Control\EncryptionOpenSSL;
use Throwable;

final class Manage extends AdminController 
{
    private const COL_RELATED = 'relatеd';

    private $db;

    protected $helpers = ['request'];

    public function __construct() {
        try {
            $this->db = Database::connect(NestedTree::DB_GROUP);
        } catch (Throwable $th) {
            throw new DatabaseException($th->getMessage());
        }
    }

    /** * Destructor */
    public function __destruct() 
    {
        if ($this->db instanceof BaseConnection) { 
            $this->db->close(); 
        }
    }

    public function index(int $userId = 0, string $releted = 'staff'): ResponseInterface 
    {
        if ($this->request->getMethod() !== 'get') {
            return $this->fail(lang('Admin.IdNotFound'));
        }

        if ($this->request->isAJAX()) {
            if ($userId) {
                $data = [];
                $data['dataBasic'] = $this->getData($userId, $releted);
                if (isset($data['dataBasic']['phone'])) {
                    $data['dataBasic']['phone'] = asPhone($data['dataBasic']['phone']);
                }
                return $this->respond(jsonEncode($data, false), 200);
            }
            $data = $this->getData(0, $releted);
            foreach ($data as &$item) {
                $item['created_at'] = meDate($item['created_at'], 'j M Y');
            }
            return $this->respond(jsonEncode($data, false), 200);
        }
        
        if ($userId) { // Редактор данных пользователя
            return $this->respond($this->build('user_edit', [
                'head' => [
                    'h1' => $this->genLinkHome(lang('Admin.user.editDataHeadTitle'))
                ],
                'model_name' => $releted
            ], 'User'), 200);
        }

        return $this->respond(
            $this->build('manage_' . $releted, ['head' => 
                ['icon' => 'people', 'title' => lang('Admin.user.pageManageHeadTitle')]
            ], 'User')
        , 200);
    }

    /**
     * load Avatar
     * @param int $userId
     * @return ResponseInterface
     */
    public function set_avatar(int $userId = 0): ResponseInterface 
    {
        $res = 'error';
        if (! $userId) { return $this->respond($res, 200); }
        $imageExt = ['png', 'svg', 'gif', 'webp', 'jpg', 'jpeg', 'ico', 'svgz', 'bmp', 'xbm', 'pjp', 'jfif', 'pjpeg', 'avif'];
        $resp = getRequestPut();
        if (! isset($resp->ext) || ! isset($resp->data) || ! in_array($resp->ext, $imageExt)) { 
            return $this->respond($res, 200); 
        }
        $tmp = Paths::AVATAR . 'me_tmp.' . $resp->ext;
        if (! file_put_contents($tmp, $resp->data, LOCK_EX)) { return $this->respond($res, 200); }

        $res = Paths::AVATAR . $userId;
        foreach ($imageExt as $i => $e) {
            if (is_file($res . '.' . $e)) { unlink($res . '.' . $e); }
            unset($imageExt[$i]);
        }
        unset($imageExt);

        $ext = $res . '.' . $resp->ext;
        if (is_file($ext)) { unlink($ext); }
        $meImage = new ImageResizer($tmp);
        $meImage->setMaxSize(175);
        $meImage->saveTo($ext, 75);
        if (! $meImage->err) { 
            if (! rename($tmp, $ext)) { $res = 'error'; }
        } else { $res = 'error'; }

        if (is_file($tmp)) { unlink($tmp); }
        return $this->respond($res, 200);
    }

    /**
     * Add or update a model resource, from "posted" properties.
     * @param int $userId
     * @return ResponseInterface
     */
    public function update(int $userId = 0): ResponseInterface 
    {
        if (! $userId) { return $this->fail(lang('Auth.invalidUser')); }  
        if (! $this->request->isAJAX()) { 
            return $this->fail(lang('Auth.invalidUser')); 
        }
        $data = $this->postTokenValid($this->request->getRawInput());
        // if ($_SERVER['REQUEST_METHOD'] === "PUT") parse_str(file_get_contents('php://input'), $data);
        // if (!isset($data) || !$data) $data = $this->request->getPost();
        if (! isset($data) || ! $data) { return $this->fail(lang('Auth.invalidUser')); }
        // Set User Active  
        if (array_key_exists('active', $data)) {
            $model = model('UserModel');
            $val = 1;
            if ($data['active'] && $data['active'] !== "0") { $val = 0; }
            if ($model->update((int)$userId, ['active' => $val])) {
                return $this->respond($val, 200, lang('Admin.user.msg.msg_update', [$userId]));
            }
            return $this->fail(lang('Auth.invalidUser'));
        }
        // Normalize data
        foreach ($data as $key => &$value) { 
            if (in_array($key, ['lang_id', 'is_man', 'inn', 'postcode'])) {
                $data[$key] = (int) toInt($value);
            } else if (in_array($key, ['phone', 'second_phone'])) {
                $data[$key] = toInt($value);
            } else {
                $data[$key] = strip_tags($value);
            }
        }
        // Is Login, Email ...
        if (isset($data['username'])) {
            // Validate User Name
            if (isset($data['username'])) {
                if (! $this->validUsername($data['username'])) { unset($data['username']); }
            }
            // Validate E-Mail
            if (isset($data['email'])) {
                if (! $this->validEmail($data['email'])) { unset($data['email']); }
            }
            // Validate Phone
            if (isset($data['phone'])) {     
                if (! $this->validPhone($data['phone'])) { unset($data['phone']); }
            }
            
            $users = model('UserModel');
            $user = $users->findById((int) $userId);
            if (isset($user) && $user) {
                // Group from user
                if (isset($data['group'])) {
                    $newGroup = $data['group'];
                    unset($data['group']);
                    if ($newGroup && strtolower($newGroup) !== strtolower($user->getGroups()[0])) {
                        $user->syncGroups($newGroup);
                        cache()->delete("{$userId}_groups");
                        cache()->delete("{$userId}_permissions");
                    }
                }
                // Language
                if (isset($data['lang_id'])) {
                    if (! $data['lang_id'] || $data['lang_id'] == $user->lang_id) { 
                        unset($data['lang_id']); 
                    }
                }
                $user->fill($data);
                $users->save($user);

                return $this->respond($userId, 200, lang('Admin.user.msg.msg_update', [$userId]));
            }
            return $this->fail(lang('Auth.invalidUser'));
        }

        // Is Personal User Data
        $dataNew = $this->getFieldsUserData($this->getRelatеd($userId));
        foreach ($data as $key => &$value) {
            if (array_key_exists($key, $dataNew)) {
                $row = &$dataNew[$key];
                if ($row['type'] === 'text' || $row['type'] === 'textarea') {
                    if ($row['max']) { $value = mb_strimwidth($value, 0, $row['max']); }
                } else if (! $value) { 
                    if ($row['type'] === 'date' || $row['type'] === 'datetime') { $value = null; }
                } else if ($row['type'] === 'tel' && strlen($value) < 10) { $value = ''; }
                $row['value'] = $value;
            }
            unset($data[$key]);
        }
        unset($data);
        if (! $dataNew) { return $this->fail(lang('Auth.invalidUser')); }
        $dataNew = [ UserControl::COL_DATA => $this->encrypt(jsonEncode($dataNew, false)) ];

        $builder = $this->db->table(UserControl::TABLE);
        $row = $builder->select('user_id')->where('user_id', (int) $userId)->get()->getRow();
        
        $this->db->transStart();
        if (isset($row) && $row) {
            $builder->set($dataNew)->where('user_id', (int) $userId)->update();
        } else {
            $dataNew['user_id'] = (int) $userId;
            $builder->set($dataNew)->insert();
        }
        $this->db->transComplete();
        return $this->respond($userId, 200, lang('Admin.user.msg.msg_update', [$userId]));
    }

    /**
     * Delete the designated resource object from the model.
     * @param int $userId
     * @return ResponseInterface
     */
    public function delete(int $userId = 0): ResponseInterface 
    {
        if ($userId) {  
            $users = model('UserModel');
            $user = $users->findById((int) $userId);
            if ($user && !$user->inGroup('superadmin')) {
                $users->delete((int) $userId, true);
                cache()->delete("{$userId}_groups");
                cache()->delete("{$userId}_permissions");
                return $this->respond($userId, 200);
            } else {
                return $this->failNotFound(lang('Admin.user.msg.msg_get_fail'));
            }
        }
        return $this->fail(lang('Admin.IdNotFound'));
    }

    /**
     * Get resource data Groups.
     */
    public static function getGroups(): array 
    {
        $data = [];
        $config = new AuthGroups();
        foreach($config->groups as $name => $value) {
            $data[$name] = [ucfirst($name), ucfirst($value['title'])];
        }
        return $data;
    }
    
    /**
     * Get resource data Groups.
     */
    public function getUserData(int $userId = 0): ResponseInterface 
    {
        if (! $userId) { return $this->respond('[]', 200); }
        $text = '';
        $relatеd = 'client';
        try {
            $builder = $this->db->table(UserControl::TABLE)->where('user_id', $userId);
            if (! $row = $builder->get()->getRow()) { 
                return $this->respond('[]', 200); 
            }
        } catch (Throwable $th) {
            return $this->respond('[]', 200);
        }

        $relatеd = $row->{self::COL_RELATED};
        $text = $this->decrypt($row->{UserControl::COL_DATA});
        unset($row->{self::COL_RELATED}, $row->{UserControl::COL_DATA}, $row);
        $data = [];

        if (! $text) {
            $fields = $this->getFieldsUserData($relatеd); //$this->db->getFieldNames(UserControl::TABLE);
            foreach ($fields as $key => $field) {
                $data[$key] = $field['value'];
                unset($fields[$key]);
            }
            unset($fields);
            return $this->respond(jsonEncode($data, false), 200);
        }

        $tmpData = jsonDecode($text);
        foreach ($tmpData as $key => &$row) {
            if ($row['type'] === 'tel') {
                $row['value'] = asPhone($row['value']);
            }
            if ($row['value']) {
                if ($row['type'] === 'date') {
                    $row['value'] = meDate($row['value'], 'Y-m-d');
                } else if ($row['type'] === 'datetime') {
                    $row['value'] = meDate($row['value']);
                }
            }
            $data[$key] = $row['value'];
            unset($row['value'], $tmpData[$key]);
        }
        return $this->respond(jsonEncode($data, false), 200);
    }

    private function getFieldsUserData(string $releted): array
    {
        if ($result = cache(UserControl::CACHE_FIELDS)) { return $result; }
        helper('path');
        $releted = UserControl::FORM_JSON_PATH . $releted . '.json';
        if (! $data = jsonDecode(baseReadFile($releted))) { return []; }
        if (! isset($data['personalData']['form']['fields'])) { return []; }
        $data = $data['personalData']['form']['fields'];

        $fields = [];
        foreach($data as $key => &$value) {
            if (! isset($value['type'])) { continue; }
            if ($value['type'] === 'hidden') { continue; }
            $name = (isset($value['name']) ? $value['name'] : $key);
            if (! $name || (int) $name) { continue; }

            $var = null;
            if ($value['type'] === 'checkbox' || $value['type'] === 'radio' || $value['type'] === 'number') { 
                $var = (int) 0; 
            } else if ($value['type'] === 'text' || $value['type'] === 'textarea') {
                $var = '';
            }
            $fields[$value['name']] = [
                'type' => $value['type'], 
                'max' => (isset($value['maxlength']) ? (int) $value['maxlength'] : (int) 0), 
                'value' => $var
            ];
        }
        cache()->save(UserControl::CACHE_FIELDS, $fields, 40320);
        return $fields;
    }

    /** Get resource data Users. */
    private function getData(int $userId, string $releted): array 
    {
        if (auth()->loggedIn()) {
            $builder = $this->db->table('users')
                ->select('users.*, auth_identities.secret as email, auth_groups_users.group') 
                ->join('auth_identities', 'auth_identities.user_id = users.id')
                ->join('auth_groups_users', 'auth_groups_users.user_id = users.id');
            if ($userId) {
                $builder = $builder->where('users.deleted_at', null)->where('users.id', (int) $userId);
                if ($row = $builder->get()->getRowArray()) { return $row; }
                return [];
            } 
            $builder = $builder->join(UserControl::TABLE, UserControl::TABLE . '.user_id = users.id')
                ->where('users.deleted_at', null)
                ->where(UserControl::TABLE . '.' . self::COL_RELATED, $releted)
                ->orderBy('auth_groups_users.group');
            return $builder->get()->getResult('array');
        }
        return [];
    }

    private function getRelatеd(int $userId = 0): string 
    {
        if (! $userId) { return 'client'; }
        try {
            $builder = $this->db->table(UserControl::TABLE)->select(self::COL_RELATED)->where('user_id', (int) $userId);
            if (! $row = $builder->get()->getRowArray()) { return 'client'; }
            return (string) array_shift($row);
        } catch (Throwable $th) {
            return 'client';
        }
    }

    /** Checks for a correctly formatted email address
     * @param string $str
     */
    private function validEmail(?string $str = null): bool
    {
        if ($str === null) { return false; }
        // emailRegExp = /^[a-zA-Z0-9.!#$%&'*+/=?^_`{|}~-]+@[a-zA-Z0-9-]+(?:\.[a-zA-Z0-9-]+)*$/;
        // @see https://regex101.com/r/wlJG1t/1/
        if (function_exists('idn_to_ascii') && defined('INTL_IDNA_VARIANT_UTS46') && preg_match('#\A([^@]+)@(.+)\z#', $str ?? '', $matches)) {
            $str = $matches[1] . '@' . idn_to_ascii($matches[2], 0, INTL_IDNA_VARIANT_UTS46);
        }
        if ((bool) filter_var($str, FILTER_VALIDATE_EMAIL)) {
            $builder = $this->db->table('auth_identities')->select('1')->where('secret', $str)->limit(1);
            $result = (bool) ($builder->get()->getRow() === null);
            return $result;
        }
        return false;
    }

    private function validUsername(string $str = ''): bool
    {
        if (strlen($str) < 5) { return false; } 
        if (preg_match('/\A[A-Z0-9 ~!#$%\&\*\-_+=|:.]+\z/i', $str) === 1) {
            $builder = $this->db->table('users')->select('1')->where('username', $str)->limit(1);
            $result = (bool) ($builder->get()->getRow() === null);
            return $result;
        }
        return false;
    }

    private function validPhone(string $str = ''): bool
    {
        if (strlen($str) < 10) { return false; } 
        $builder = $this->db->table('users')->select('1')->where('phone', $str)->limit(1);
        $result = (bool) ($builder->get()->getRow() === null);
        return $result;
    }

    private function encrypt(string $text = ''): string
    {
        $encrypter = new EncryptionOpenSSL(UserControl::ENCRYPT_KEY, UserControl::SALT);
        return $encrypter->encrypt($text);
    }

    private function decrypt(string $text = ''): string
    {
        $encrypter = new EncryptionOpenSSL(UserControl::ENCRYPT_KEY, UserControl::SALT);
        return $encrypter->decrypt($text);
    }
}
