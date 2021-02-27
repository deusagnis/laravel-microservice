<?php


namespace App\Microservice\Tools;


use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;

class Authentication
{
    /**
     * Duration of authorize cookie
     *
     * @var float|int
     */
    public $rememberTime = 24 * 60 * 60;
    /**
     * Users table name
     *
     * @var string
     */
    public $usersTable = 'users';
    /**
     * Users tokens table name
     *
     * @var string
     */
    public $tokensTable = 'users_tokens';
    /**
     * Cookies key for authorization
     *
     * @var string
     */
    public $cookiesKey = 'au';
    /**
     * Access to Cookies
     *
     * @var Cookie
     */
    protected $cookiesProxy;
    /**
     * Access to Database
     *
     * @var ConnectionInterface
     */
    protected $db;
    /**
     * Data of current authorized user
     *
     * @var object
     */
    protected $currentUser;

    protected $currentUserPwdHash;

    /**
     * Authentication constructor.
     *
     * @param DB $dbConnection
     * @param Cookie $cookiesProxy
     */
    public function __construct(DB $dbConnection, Cookie $cookiesProxy)
    {
        $this->cookiesProxy = $cookiesProxy;
        $this->db = $dbConnection::connection(env('AUTH_DB_CONNECTION'));
    }

    /**
     * New user registration
     *
     * @param string $password
     * @param string $username
     * @param string $email
     * @return false|int
     */
    public function register($password, $username, $email)
    {
        $userExists = $this->db->table($this->usersTable)
            ->where('name', '=', $username)
            ->orWhere('email', '=', $email)
            ->exists();
        if ($userExists) return false;

        $pwdHash = static::hashPassword($password);

        return $this->db->table($this->usersTable)
            ->insertGetId([
                'name' => $username,
                'email' => $email,
                'pwdHash' => $pwdHash,
                'createdAt' => now()->unix(),
            ]);
    }

    /**
     * Generate password hash
     *
     * @param string $password
     * @return false|string|null
     */
    protected static function hashPassword($password)
    {
        return password_hash($password, PASSWORD_DEFAULT);
    }

    /**
     * User login
     *
     * @param false|string $password
     * @param false|string $username
     * @param false|string $email
     * @param false|string $token
     * @param bool $remember
     * @return $this
     */
    public function login($password = false, $username = false, $email = false, $token = false, $remember = false)
    {
        return $this->auth(false, $password, $username, $email, $token, $remember);
    }

    /**
     * Authorize user by parameters
     *
     * @param false|array $cookie
     * @param false|string $password
     * @param false|string $username
     * @param false|string $email
     * @param false|string $token
     * @param bool $remember
     * @return $this
     */
    public function auth($cookie = false, $password = false, $username = false, $email = false, $token = false, $remember = true)
    {
        if (!empty($cookie)) {
            $this->cookiesAuth($cookie);
        } elseif (!empty($password) and (!empty($username) or !empty($email))) {
            $this->directAuth($password, $username, $email, $remember);
        } elseif (!empty($token)) {
            $this->tokenAuth($token, $remember);
        }

        return $this;
    }

    /**
     * Authorize user by cookie
     *
     * @param array|false $cookieObject
     */
    protected function cookiesAuth($cookieObject)
    {
        if (!empty($cookieObject->user)) {
            $this->setCurrentUser($cookieObject->user);
        }
    }

    /**
     * Authorize by password + login
     *
     * @param string $password
     * @param false|string $name
     * @param false|string $email
     * @param bool $remember
     */
    protected function directAuth($password, $name = false, $email = false, $remember = false)
    {
        if (empty($name)) $name = '0';
        if (empty($email)) $email = '0';
        $foundUser = $this->db->table($this->usersTable)
            ->where('name', '=', $name)
            ->orWhere('email', '=', $email)
            ->first();

        if (empty($foundUser)) return;

        if (!static::cmpPasswordHash($password, $foundUser->pwdHash)) return;

        $this->setCurrentUser($foundUser);

        if ($remember) {
            $this->rememberUser($this->getCurrentUser());
        }
    }

    /**
     * Compare password with hash
     *
     * @param string $password
     * @param string $hash
     * @return bool
     */
    protected static function cmpPasswordHash($password, $hash)
    {
        return password_verify($password, $hash);
    }

    /**
     * Remember user throw cookie
     *
     * @param array|object $userData
     */
    protected function rememberUser($userData)
    {
        if ($this->cookiesProxy::has($this->cookiesKey)) {
            $cookieObject = $this->cookiesProxy::get($this->cookiesKey);
        } else {
            $cookieObject = new class {
            };
        }
        $cookieObject->user = $userData;
        $this->setCookie(json_encode($cookieObject), $this->rememberTime);
    }

    /**
     * Set cookie for authentication
     *
     * @param string $cookieValue
     * @param int $duration
     */
    protected function setCookie(string $cookieValue, $duration = 0)
    {
        $expiredAt = now()->unix() + $duration;
        $cookie = \Symfony\Component\HttpFoundation\Cookie::create(
            $this->cookiesKey,
            $cookieValue,
            $expiredAt,
            '/',
            null,
            null,
            false,
            false,
            \Symfony\Component\HttpFoundation\Cookie::SAMESITE_NONE
        );

        $this->cookiesProxy::queue($cookie);
    }

    /**
     * Get current user object
     *
     * @return false|object
     */
    public function getCurrentUser()
    {
        if (empty($this->currentUser)) return false;
        return $this->currentUser;
    }

    /**
     * Set current user object
     *
     * @param false|object $userData
     */
    protected function setCurrentUser($userData)
    {
        if (is_object($userData) and isset($userData->pwdHash)){
            $this->currentUserPwdHash = $userData->pwdHash;
            unset($userData->pwdHash);
        }
        if (is_array($userData) and isset($userData['pwdHash'])){
            $this->currentUserPwdHash = $userData['pwdHash'];
            unset($userData['pwdHash']);
        }
        $this->currentUser = $userData;
    }

    /**
     * Authorize user by token
     *
     * @param string $token
     * @param bool $remember
     */
    protected function tokenAuth($token, $remember = false)
    {
        $tokenNote = $this->db->table($this->tokensTable)
            ->where('token', '=', $token)
            ->first();
        if (empty($tokenNote)) return;
        $user = $this->db->table($this->usersTable)
            ->where('id', '=', $tokenNote->userId)
            ->first();
        if (empty($user)) return;
        $user->tokenNote = $tokenNote;
        $this->setCurrentUser($user);
        if ($remember) {
            $this->rememberUser($this->getCurrentUser());
        }
    }

    /**
     * Reset authorization
     */
    public function logout()
    {
        $this->forgetUser();
        $this->setCurrentUser(false);
    }

    /**
     * Reset user authorized cookie
     *
     */
    protected function forgetUser()
    {
        $this->setCookie('', 0);
    }

    /**
     * Get user tokens
     *
     * @param bool $first
     * @return false|Model|Builder|Collection|object|null
     */
    public function getTokens($first = true)
    {
        $currentUser = $this->getCurrentUser();
        if (empty($currentUser)) return false;

        $tokens = $this->db->table($this->tokensTable)
            ->where('userId', '=', $currentUser->id);
        if ($first) {
            $tokens = $tokens->first();
        } else {
            $tokens = $tokens->get();
        }

        return $tokens;
    }

    /**
     * Create token for current user
     *
     * @param int $expiredAt
     * @return false|int|string
     */
    public function createToken($expiredAt = 0)
    {
        $currentUser = $this->getCurrentUser();
        if (empty($currentUser)) return false;

        $token = $this->genToken($currentUser,$this->currentUserPwdHash);
        $created = $this->db->table($this->tokensTable)->insertGetId([
            'userId' => $currentUser->id,
            'token' => $token,
            'createdAt' => now()->unix(),
            'expiredAt' => $expiredAt,
        ]);
        if ($created) return $token;
        return $created;
    }

    /**
     * Gen access token by user object
     *
     * @param array|object $userData
     * @param $pwdHash
     * @return string
     */
    protected function genToken($userData,$pwdHash)
    {
        return md5($userData->id . $pwdHash) . md5(microtime());
    }

    /**
     * Check authorized
     *
     * @return bool
     */
    public function authorized()
    {
        $user = $this->getCurrentUser();

        return !empty($user);
    }
}
