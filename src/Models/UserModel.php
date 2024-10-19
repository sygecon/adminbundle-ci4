<?php
namespace Sygecon\AdminBundle\Models;

use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Database\BaseBuilder;
use CodeIgniter\Database\Exceptions\DatabaseException;
use CodeIgniter\Validation\ValidationInterface;
use Config\Auth;
use Config\AuthGroups;
use Config\Database;
use Config\Services;
use CodeIgniter\Shield\Entities\User as UserEntity;
use App\Models\Boot\UserModel as ShieldUserModel;
use CodeIgniter\Shield\Validation\ValidationRules;
use Throwable;

final class UserModel 
{   
    private const COL_RELATED       = 'relatеd';
    private const RELATED_DEFAULT   = 'client';
    private const COL_USER_DATA     = 'data';
    private const TABLE_DETAILS     = 'user_details';

    private ?string $DBGroup        = null;
    private string $findSelect      = '%s.*, %s.secret as email, %s.group';

    private string $table           = '';
    private array $tables           = [];
    private array $validationRules  = [];
    
    private $validation;
    private $builder;
    private $db;

    public function __construct() {
        try {
            $authConfig       = new Auth();
            $this->tables     = $authConfig->tables;
            $this->findSelect = sprintf($this->findSelect, 
                $this->tables['users'], $this->tables['identities'], $this->tables['groups_users']
            );
            if (null !== $authConfig->DBGroup && is_string($authConfig->DBGroup)) {
                $this->DBGroup = $authConfig->DBGroup;
            }
            $validationRules = new ValidationRules();
            $this->validationRules = $validationRules->getRegistrationRules();
            if (isset($this->validationRules['password_confirm'])) {
                unset($this->validationRules['password_confirm']);
            }
        } catch (Throwable $th) {
            throw new DatabaseException($th->getMessage());
        }
    }

    /** * Destructor */
    public function __destruct() 
    {
        if ($this->db instanceof BaseConnection) $this->db->close(); 
    }

    public function find(int $userId = 0): array
    {
        if (! $userId) return [];
        if (! $builder = $this->builder($this->tables['users'])) return [];

        $this->tableJoin($builder);
        $builder = $builder
            ->where($this->tables['users'] . '.deleted_at', null)
            ->where($this->tables['users'] . '.id', (int) $userId);
        $row = $builder->get()->getRowArray();
        return (is_array($row) && $row ? $row : []);
    }

    public function findUserData(int $userId = 0): string
    {
        return $this->getUserData($userId, self::COL_USER_DATA, '');
    }

    public function findRelated(int $userId): string
    {
        return $this->getUserData($userId, self::COL_RELATED, self::RELATED_DEFAULT);
    }

    public function findAll(string $releted = self::RELATED_DEFAULT): array
    {
        if (! $builder = $this->builder($this->tables['users'])) return [];

        $this->tableJoin($builder);
        $builder = $builder
            ->join(self::TABLE_DETAILS, self::TABLE_DETAILS . '.user_id = ' . $this->tables['users'] . '.id')
            ->where($this->tables['users'] . '.deleted_at', null);
        if ($releted) {
            $builder = $builder->where(self::TABLE_DETAILS . '.' . self::COL_RELATED, $releted);
        }
        $builder = $builder->orderBy($this->tables['groups_users'] . '.group')->distinct();
        return $builder->get()->getResultArray();
    }

    /**
     * User Activity switch
     */
    public function activitySwitch(int $userId = 0): bool
    {
        if (! $userId) return false;
        $userModel = auth()->getProvider();
        if (! $user = $userModel->findById((int) $userId)) return false;

        $active = $user->active;
        if ($active && $user->inGroup('superadmin')) return false;

        $user->active = ($active ? 0 : 1);
        return $userModel->save($user);
    }

    /*
     * Create a new user
     * @param array $data
     * @param bool $isActive
     * @return bool
     */
    public function createUser(array $data = [], bool $isActive = true): int
    {
        $id     = (int) 0;
        $row    = [];
        if (! $data) return $id;
        
        foreach($data as $key => $value) {
            if (! in_array($key, ['username', 'email', 'password', 'password_confirm'])) {
                $row[$key] = $this->normalizeData($key, $value);
                unset($data[$key]);
            } else {
                $data[$key] = trim(strip_tags($value));
            }
        }
        // Validate User Name
        if (! isset($data['username']) || ! $this->validUsername($data['username'])) return $id;
        // Validate E-Mail
        if (! isset($data['email']) || ! $this->validEmail($data['email'])) return $id;
        // Validate Password
        if (! isset($data['password']) || ! $this->validPassword($data['password'])) return $id;
        if (! isset($data['password_confirm'])) return $id;
        if ($data['password'] !== $data['password_confirm']) return $id;
        unset($data['password_confirm']);
        if (isset($row['id'])) unset($row['id']);
        if (isset($row['user_id'])) unset($row['user_id']);
        if (isset($row['active'])) unset($row['active']);
        if (isset($row['secret'])) unset($row['secret']);

        $group = '';
        if (isset($row['group'])) {
            if ($row['group']) {
                $group  = $row['group'];
                $config = new AuthGroups();
                if (! isset($config->groups[$group])) $group = '';
            } 
            unset($row['group']);
        }
        $relatеd = '';
        if (isset($row['relatеd'])) {
            if ($row['relatеd']) {
                $relatеd = strtolower($row['relatеd']);
                if ($relatеd !== 'client' || $relatеd !== 'staff') $relatеd = '';
            }
            unset($row['relatеd']);
        }

        // Run validation if the user has passed username and/or email via command line
        $userModel      = auth()->getProvider();
        $allowedFields  = $userModel->allowedFields;
        $user           = new UserEntity($data);
        
        foreach($row as $key => $value) {
            if (isset($user->{$key}) && in_array($key, $allowedFields)) {
                $user->{$key} = $value;
            }
            unset($row[$key]);
        }
        if (true === $isActive) $user->active = 1;

        if (! $userModel->save($user)) return $id;

        $id   = (int) $userModel->getInsertID();
        $user = $userModel->findById($id);

        if ($group && $group !== 'user') {
            $user->removeGroup('user');
            $user->addGroup($group);
        }
        
        if (! $builder = $this->builder(self::TABLE_DETAILS)) return $id;
        $row = $builder->where('user_id', $id)->get()->getRowArray();
        if (isset($row) && $row && isset($row['relatеd'])) {
            if (! $relatеd) $relatеd = $row['relatеd'];
            $builder->set(['relatеd' => $relatеd, 'data' => ''])->where('user_id', $id)->update();
        } else {
            if (! $relatеd) $relatеd = 'client';
            $builder->insert(['user_id' => $id, 'relatеd' => 'staff', 'data' => '']);
        }
        return $id;
    }

    /**
     * User save data
     * Activate / Deactivate an existing user by user Id
     */
    public function update(int $userId, array $data): bool
    {
        if (! $userId) return false;
        if (isset($data['id'])) unset($data['id']);

        $userModel = auth()->getProvider();
        if (! $user = $userModel->findById((int) $userId)) return false;

        // Change password
        if (isset($data['password'])) {
            if (! isset($data['password_confirm'])) return false;
            if ($data['password'] !== $data['password_confirm']) return false;
            unset($data['password_confirm']);
            if (! $this->validPassword($data['password'])) return false;

            $user->password = $data['password'];
            return $userModel->save($user);
        }

        // Validate E-Mail
        if (isset($data['email'])) {
            $email = trim(strip_tags($data['username']));
            if ($email && $this->validEmail($data['email'])) {
                $user->email = $email;
                $userModel->save($user);
            }
            unset($data['email']);
        }
        // Validate User Name
        if (isset($data['username'])) {
            $data['username'] = trim(strip_tags($data['username']));
            if (! $this->validUsername($data['username'])) unset($data['username']);
        }
        // Validate Phone
        if (isset($data['phone'])) {  
            $data['phone'] = $this->normalizeData('phone', $data['phone']); 
            if (! $data['phone']) unset($data['phone']);   
        }

        // Language
        if (isset($data['lang_id'])) {
            $data['lang_id'] = (int) $this->normalizeData('lang_id', $data['lang_id']);
            if (! $data['lang_id'] || $data['lang_id'] === (int) $user->lang_id) { 
                unset($data['lang_id']); 
            }
        }

        // Group from user
        if (isset($data['group'])) {
            $newGroup = $data['group'];
            unset($data['group']);
            $group = $user->getGroups()[0];
            if ($newGroup && strtolower($newGroup) !== strtolower($group)) {
                $config = new AuthGroups();
                if (isset($config->groups[$newGroup])) {
                    // $user->syncGroups($newGroup);
                    $user->removeGroup($group);
                    $user->addGroup($newGroup);
                    cache()->delete("{$userId}_groups");
                    cache()->delete("{$userId}_permissions");
                }
            }
        }
        if (! $data) return true;

        $allowedFields = $userModel->allowedFields;
        $act = false;
        foreach($data as $key => $value) {
            if (isset($user->{$key}) && in_array($key, $allowedFields)) {
                if ($key === 'phone' || $key === 'username') {
                    $user->{$key} = $value;
                } else {
                    $user->{$key} = $this->normalizeData($key, $value);
                }
                $act = true;
            }
        }
        if ($act) return $userModel->save($user);
        return true;
    }

    public function updateUserData(int $userId, string $data): bool
    {
        if (! $userId) return false;
        if (! $builder = $this->builder(self::TABLE_DETAILS)) return false;
        
        $row = $builder->select('user_id')
            ->where('user_id', (int) $userId)
            ->get()->getRow();
        $dataNew = [self::COL_USER_DATA => $data];
        
        $this->db->transStart();
        if ($row !== null && isset($row->user_id)) {
            $result = $builder->set($dataNew)->where('user_id', (int) $userId)->update();
        } else {
            $dataNew['user_id'] = (int) $userId;
            $result = $builder->set($dataNew)->insert();
        }
        $this->db->transComplete();
        return (! $result ? false : true);
    }

    /**
     * Delete user.
     * @param int $userId
     * @return bool
     */
    public function delete(int $userId): bool 
    {
        if (! $userId) return false;

        $userModel = auth()->getProvider();
        if (! $user = $userModel->findById((int) $userId)) return false;
        if ($user->inGroup('superadmin')) return false;
        $userModel->delete((int) $userId, true);
        cache()->delete("{$userId}_groups");
        cache()->delete("{$userId}_permissions");
        return true;
    }

    public function normalizeData(string $key, mixed $value): mixed
    {
        if (in_array($key, ['lang_id', 'is_man', 'inn', 'postcode', 'id', 'user_id'])) {
            return (int) toInt((string) $value);
        } else 
        if ($key === 'phone') {
            $phone =  toInt((string) $value);
            return ($this->validPhone($phone) ? $phone : '');
        } else
        if ($key === 'second_phone') {
            return toInt((string) $value);
        }
        return trim(strip_tags((string) $value));
    }

    /** Checks for a correctly formatted email address
     * @param string $email
     * @return bool
     */
    private function validEmail(string $email): bool
    {
        if (! $email) return false; 
        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) return false;
        try {
            $validation = $this->serviceValidation();
            $validation->setRules(['email' => $this->validationRules['email']]);
            if (! $validation->run(['email' => $email], null, $this->DBGroup)) return false;
            return true;
        } catch (Throwable $th) {
            return false;
        }
    }

    /** Checks for a correctly formatted User name
     * @param string $username
     * @return bool
     */
    private function validUsername(string $username): bool
    {
        if (! $username) return false; 
        if (preg_match('/\A[A-Z0-9 ~!#$%\&\*\-_+=|:.]+\z/i', $username) !== 1) return false;
        try {
            $validation = $this->serviceValidation();
            $validation->setRules(['username' => $this->validationRules['username']]);
            if (! $validation->run(['username' => $username], null, $this->DBGroup)) return false;
            return true;
        } catch (Throwable $th) {
            return false;
        }
    }

    /** Checks for a correctly formatted Password
     * @param string $password
     * @return bool
     */
    private function validPassword(string $password): bool
    {
        if (! $password) return false; 
        try {
            $validation = $this->serviceValidation();
            $validation->setRules(['password' => $this->validationRules['password']]);
            if (! $validation->run(['password' => $password], null, $this->DBGroup)) return false;
            return true;
        } catch (Throwable $th) {
            return false;
        }
    }

    private function validPhone(string $phone): bool
    {
        if (strlen($phone) < 10) return false;
        $user = $this->findUser(null, null, $phone);
        return ($user === null ? true : false);
    }

    /** Provides a shared instance of Validation
     * @return ValidationInterface
     */
    private function serviceValidation(): ValidationInterface
    {
        if (! $this->validation instanceof ValidationInterface) {
            $this->validation = Services::validation();
        }
        return $this->validation;
    }

    /** Provides a shared instance of the Query Builder.
     * @return BaseBuilder
     */
    private function builder(?string $table = null): ?BaseBuilder
    {
        if ($this->builder instanceof BaseBuilder) {
            if (is_string($table) && $table && $this->builder->getTable() !== $table) {
                return $this->db->table($table);
            }
            return $this->builder;
        }
        $table = ((is_string($table) && $table) ? $table : $this->table);
        if (! $this->db instanceof BaseConnection) {
            $this->db = Database::connect($this->DBGroup);
        }
        $builder = $this->db->table($table);
        if ($table === $this->table) {
            $this->builder = $builder;
        }
        return $builder;
    }

    private function tableJoin(BaseBuilder &$builder): void
    {
        $builder = $builder->select($this->findSelect) 
            ->join($this->tables['identities'], $this->tables['identities'] . '.user_id = ' . $this->tables['users'] . '.id')
            ->join($this->tables['groups_users'], $this->tables['groups_users'] . '.user_id = ' . $this->tables['users'] . '.id');
    }

    private function getUserData(int $userId, string $col, string $resultDefault): string
    {
        if (! $userId) return $resultDefault;
        try {
            if (! $builder = $this->builder(self::TABLE_DETAILS)) return null;
            $builder = $builder->select($col)->where('user_id', (int) $userId);
            $row     = $builder->get()->getRow();

            if ($row !== null && isset($row->{$col})) return (string) $row->{$col};
            return $resultDefault;
        } catch (Throwable $th) {
            return $resultDefault;
        }
    }

    /**
     * Find an existing user by username or email or phone.
     * @param string|null $username User name to search for (optional)
     * @param string|null $email    User email to search for (optional)
     * @param string|null $phone    User phone to search for (optional)
     */
    private function findUser(?string $username = null, ?string $email = null, ?string $phone = null): ?UserEntity
    {
        if ($username === null && $email === null && $phone === null) return null;

        $userModel = auth()->getProvider();
        if ($username !== null) {
            return $userModel->findByCredentials(['username' => $username]);
        }
        if ($email !== null) {
            return $userModel->findByCredentials(['email', $email]);
        }
        return $userModel->findByCredentials(['phone', $phone]);
    }
}