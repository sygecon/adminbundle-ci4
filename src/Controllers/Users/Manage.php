<?php
namespace Sygecon\AdminBundle\Controllers\Users;

use Config\AuthGroups;
use Config\Encryption;
use Sygecon\AdminBundle\Models\UserModel;
use Sygecon\AdminBundle\Config\UserControl;
use Sygecon\AdminBundle\Config\Paths;
use Sygecon\AdminBundle\Controllers\AdminController;
use Sygecon\AdminBundle\Libraries\ImageResizer;

final class Manage extends AdminController 
{
    protected $helpers = ['request'];

    protected $model;

    public function __construct() {
        $this->model = new UserModel();
    }

    public function index(int $userId = 0, string $releted = 'staff'): string 
    {
        if (strtolower($this->request->getMethod()) !== 'get') return $this->pageNotFound();
        if (! $user = auth()->user()) return $this->pageNotFound();

        if (true === $this->request->isAJAX()) {
            if ($userId) {
                $data = [];
                $data['dataBasic'] = $this->getData($userId, $releted);
                if (isset($data['dataBasic']['phone'])) {
                    $data['dataBasic']['phone'] = asPhone($data['dataBasic']['phone']);
                }
                return $this->successfulResponse($data);
            }

            $data = $this->getData(0, $releted);
            foreach ($data as &$item) {
                $item['created_at'] = meDate($item['created_at'], 'j M Y');
            }
            return $this->successfulResponse($data);
        }

        if ($userId) { // Редактор данных пользователя
            return $this->build('user_edit', [
                'head' => [
                    'h1' => $this->genLinkHome(lang('Admin.user.editDataHeadTitle'))
                ],
                'model_name' => $releted
            ], 'User');
        }
        
        $data = [
            'head' => [
                'icon' => 'people', 
                'title' => lang('Admin.user.pageManageHeadTitle')
            ]
        ];
        if (true === $user->inGroup('superadmin')) {
            $groups = $this->getGroups();
            if (isset($groups['superadmin'])) unset($groups['superadmin']);
            $data['groups']  = array_reverse($groups);
            $data['date_create'] = meDate('', 'j M Y');
        }
        return $this->build('manage_' . $releted, $data, 'User');
    }

    /**
     * load Avatar
     * @param int $userId
     * @return string
     */
    public function set_avatar(int $userId = 0): string 
    {
        $res = 'error';
        if (! $userId) return $this->successfulResponse($res);

        $imageExt = ['png', 'svg', 'gif', 'webp', 'jpg', 'jpeg', 'svgz', 'bmp', 'xbm', 'pjp', 'jfif', 'pjpeg', 'avif'];
        $resp = getRequestPut();
        if (! isset($resp->ext) || ! isset($resp->data) || ! in_array($resp->ext, $imageExt)) { 
            return $this->successfulResponse($res);
        }
        $tmp = Paths::AVATAR . 'me_tmp.' . $resp->ext;
        if (! file_put_contents($tmp, $resp->data, LOCK_EX)) { 
            return $this->successfulResponse($res);
        }
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
            if (! rename($tmp, $ext)) $res = 'error';
        } else { $res = 'error'; }

        if (is_file($tmp)) unlink($tmp); 
        return $this->successfulResponse($res);
    }

    /**
     * Creating user
     * @param string $relatеd
     * @return string
     */
    public function create(string $relatеd = 'staff'): string
    {
        if (false === $this->request->isAJAX()) return $this->pageNotFound();
        $data = $this->postTokenValid($this->request->getPost());
        if (! isset($data) || ! $data) return $this->pageNotFound(); 

        if (isset($data['created_at'])) unset($data['created_at']);
        $data['lang_id']    = (int) langIdFromName();
        $data['relatеd']    = trim(strip_tags($relatеd));
        $active             = ($data['relatеd'] === 'staff' ? true : false);
        
        if ($id = $this->model->createUser($data, $active)) return $this->successfulResponse($id);
        return $this->pageNotFound();
    }

    /**
     * Updating object data.
     * @param int $userId
     * @return string
     */
    public function update(int $userId = 0): string 
    {
        if (! $userId) return $this->pageNotFound();  
        if (false === $this->request->isAJAX()) return $this->pageNotFound();

        $data = $this->postTokenValid($this->request->getRawInput());
        if (! isset($data) || ! $data) return $this->pageNotFound();

        // Activate / Deactivate an existing user by user Id 
        if (array_key_exists('active', $data)) {
            if ($this->model->activitySwitch((int) $userId)) {
                return $this->successfulResponse($userId);
            }
            return $this->pageNotFound();
        }

        // Is Login, Email ...
        if (isset($data['username'])) {
            if ($this->model->update((int) $userId, $data)) {
                return $this->successfulResponse($userId);
            }
            return $this->pageNotFound();
        }

        // Is Personal User Data
        $relatеd = $this->model->findRelated($userId);
        $dataNew = $this->getFieldsUserData($relatеd);

        foreach ($data as $key => &$value) {
            if (array_key_exists($key, $dataNew)) {
                $val = $this->model->normalizeData($key, $value);
                $row = &$dataNew[$key];
                if ($row['type'] === 'text' || $row['type'] === 'textarea') {
                    if ($row['max']) $val = mb_strimwidth($val, 0, $row['max']);
                } else 
                if (! $val) { 
                    if ($row['type'] === 'date' || $row['type'] === 'datetime') $val = null;
                } else 
                if ($row['type'] === 'tel' && strlen($val) < 10) $val = ''; 
                $row['value'] = $val;
            }
            unset($data[$key]);
        }
        unset($data);
        if (! $dataNew) return $this->pageNotFound();

        $secret = $this->encryptionData(jsonEncode($dataNew, false), $userId, true);

        if ($this->model->updateUserData((int) $userId, $secret)) {
            return $this->successfulResponse($userId);
        }
        return $this->pageNotFound();
    }

    /**
     * Delete the object from the model.
     * @param int $userId
     * @return string
     */
    public function delete(int $userId): string 
    {
        if (! $this->model->delete((int) $userId)) return $this->pageNotFound();
        return $this->successfulResponse($userId);
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
    public function getUserData(int $userId = 0): string 
    {
        if (! $userId) return $this->successfulResponse([], true);

        $relatеd = $this->model->findRelated($userId);
        $text    = $this->model->findUserData($userId);
        $text    = $this->encryptionData($text, $userId, false);
        $data    = [];

        if (! $text) {
            $fields = $this->getFieldsUserData($relatеd);
            foreach ($fields as $key => $field) {
                $data[$key] = $field['value'];
                unset($fields[$key]);
            }
            unset($fields);
            return $this->successfulResponse($data, true);
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
        return $this->successfulResponse($data, true);
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
        if ($userId) return $this->model->find($userId);
        return $this->model->findAll($releted);
    }

    private function encryptionData(string $text, int $userId, bool $encode): string
    {
        $salt   = hash('crc32b', '__START_SYGECON_ID_' . (string) $userId . '_END__', false);
        $config = new Encryption();
        $config->driver     = 'OpenSSL';
        $config->blockSize  = 16;
        $config->digest     = 'SHA512';
        $config->cipher     = 'AES-256-CTR';
        $config->rawData    = false;
        $config->key        = bin2hex(\hash_hkdf($config->digest, UserControl::ENCRYPT_KEY . $salt));
        $encrypter          = service('encrypter', $config);

        if (true === $encode) return $encrypter->encrypt($text);
        return $encrypter->decrypt($text);
    }

}
