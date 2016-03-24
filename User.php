<?php
namespace App\Component\Token;

class User implements \App\Interfaces\Token
{
    /**
     * @var \Phalcon\Di
     */
    protected $_di;

    /**
     * @var \Phalcon\Http\Request
     */
    protected $request;

    /**
     * @var \App\Component\Cache
     */
    protected $cache;

    const EXPIRE = 7776000;     // 3 months
    
    const PREFIX = 'TOKEN_';
    
    public function __construct(\Phalcon\Di $di)
    {
        $this->_di     = $di;
        $this->request = $this->_di->get('request');
        $this->cache   = $this->_di->get('cache');
    }

    /**
     * @param bool|true $thrown
     * @return string|void
     * @throws \Exception
     */
    public function getId($thrown = true)
    {
        $token = $this->getToken($thrown);

        if($token) {
            if(!$this->cache->exists($this->getKey($token))) return $thrown ? $this->unauthorized() : "";
            return $this->cache->get($this->getKey($token));
        }

        return "";
    }

    /**
     * @throws \Exception
     */
    public function getUser()
    {
        $userId = $this->getId(true);
        $fetch  = $this->_di->get('fetch');
        return $fetch->cache->user->byId($userId);
    }

    /**
     * @param string $userId
     * @return bool
     */
    public function isSelf($userId)
    {
        return $this->getId(false) == $userId;
    }

    public function isRole($role)
    {
        return in_array($role, $this->getRole());
    }

    /**
     * @param $userId
     * @return array
     */
    public function update($userId)
    {
        $random = $this->getRandom();
        $token  = $this->cache->get($this->getKey($userId));

        if($token) {
            // update expire time...
            $this->cache->delete($this->getKey($userId));
            $this->cache->delete($this->getKey($token));
            $this->cache->delete($this->getKey($token.'role'));
        } else {
            $token = $random->string(48);
        }
        $this->cache->save($this->getKey($userId), $token,  self::EXPIRE);
        $this->cache->save($this->getKey($token),  $userId, self::EXPIRE);
        $this->cache->save($this->getKey($token.'role'), [self::ROLE_USER], self::EXPIRE);

        return ['token' => $token, 'expire' => time() + self::EXPIRE, 'role' => [self::ROLE_USER], 'user_id' => $userId];
    }

    private function getRole()
    {
        $token = $this->getToken(true);
        $this->cache->get($this->getKey($token.'role'));
    }

    private function getToken($thrown)
    {
        $token = $this->request->getHeader('Authorization');
        if(!$token) return $thrown ? $this->unauthorized() : "";
        return $token;
    }
    
    private function getKey($key)
    {
        return self::PREFIX . $key;
    }
    
    private function getRandom()
    {
        return $this->_di->get('random');
    }

    private function unauthorized()
    {
        throw new \Exception('need authorized', 401);
    }
}