<?php

/**
 * Created by PhpStorm.
 * User: runBaby
 * Date: 2019/8/3
 * Time: 5:55 PM
 */
class UsersBind
{


    private $redisObj;
    private $fieldPrefix;
    private $serverNode;
    private $redisTable;

    private $clientNode = ['APP', 'PC'];
    private static $classIndexFd = 1;

    const REDIS_HOST = '127.0.0.1';
    const REDIS_PORT = 6379;
    const REDIS_TIMEOUT = 2;

    public function __construct($redisTable = 'ws', $fieldPrefix = 'bind', $serverNode = 0, $client = 0)
    {
        $this->redisObj = new Redis();
        $this->redisObj->connect(self::REDIS_HOST, self::REDIS_PORT, self::REDIS_TIMEOUT);

        $this->redisTable = $redisTable;
        $this->serverNode = $serverNode;
        $this->fieldPrefix = $fieldPrefix;
        $this->clientNode = $this->clientNode[$client];

    }


    /*
     * getRedisTable
     */
    public function getRedisTable()
    {
        return $this->redisTable;
    }


    /*
     * getFieldPrefix
     */
    public function getFieldPrefix()
    {
        return $this->fieldPrefix;
    }


    /*
     * getServerNode
     */
    public function getServerNode()
    {
        return $this->serverNode;
    }


    /*
     * getClientNode
     */
    public function getClientNode()
    {
        return $this->clientNode;
    }


    /**
     * Explain: set field prefix
     * User: runBaby
     * Date: 2019/8/3
     * Time: 6:15 PM
     * @return string
     */
    private function setFieldPrefix($type = 0)
    {
        $keyPrefix = $this->fieldPrefix . ':server:' . $this->serverNode . ':client:' . $this->clientNode . ':';
        if ($type === 1) {
            $keyPrefix .= 'fd:';
        } else {
            $keyPrefix .= 'uid:';
        }


        return $keyPrefix;
    }


    /**
     * Explain: redis key
     * @param $id
     * @param $type
     * User: runBaby
     * Date: 2019/8/3
     * Time: 6:26 PM
     * @return string
     */
    private function setRedisField($id, $type = 0)
    {
        $fieldPrefix = $this->setFieldPrefix($type);
        $key = $fieldPrefix . $id;


        return $key;
    }


    /**
     * Explain: user id bind fd
     * @param $uid
     * @param $fd
     * User: runBaby
     * Date: 2019/8/3
     * Time: 6:23 PM
     * @return int
     */
    public function userIdBind($uid, $fd)
    {
        $field = $this->setRedisField($uid);
        $result = $this->redisObj->hSet($this->redisTable, $field, $fd);

        return $result;
    }


    /**
     * Explain: fd bind user id
     * @param $uid
     * @param $fd
     * User: runbaby
     * Date: 2019/8/4
     * Time: 9:08 PM
     * @return int
     */
    public function fdBind($uid, $fd)
    {
        $field = $this->setRedisField($fd, self::$classIndexFd);
        $result = $this->redisObj->hSet($this->redisTable, $field, $uid);


        return $result;
    }


    /**
     * Explain: Two-way binding
     * @param $uid
     * @param $fd
     * User: runbaby
     * Date: 2019/8/4
     * Time: 9:32 PM
     * @return bool
     */
    public function setBindId($uid, $fd)
    {
        $result_uid = $this->userIdBind($uid, $fd);
        $result_fd = $this->fdBind($uid, $fd);


        if ($result_uid && $result_fd) {
            return true;
        } else {
            return false;
        }
    }


    /**
     * Explain: get bind fd
     * @param $uid
     * User:runBaby
     * Date: 2019/8/3
     * Time: 6:28 PM
     * @return string
     */
    public function getBindFd($uid)
    {
        $field = $this->setRedisField($uid);
        $data = $this->redisObj->hGet($this->redisTable, $field);


        return $data;
    }


    /**
     * Explain: get bind user id
     * @param $fd
     * User:runBaby
     * Date: 2019/8/3
     * Time: 6:28 PM
     * @return string
     */
    public function getBindUserId($fd)
    {
        $field = $this->setRedisField($fd, self::$classIndexFd);
        $data = $this->redisObj->hGet($this->redisTable, $field);


        return $data;
    }


    /**
     * Explain: unbinds userId and  fd
     * @param $uid
     * User: runBaby
     * Date: 2019/8/3
     * Time: 6:38 PM
     * @return bool
     */
    public function unbindFd($uid, $fd)
    {
        //unbind userId
        $field_uid = $this->setRedisField($uid);
        $result_uid = $this->redisObj->hDel($this->redisTable, $field_uid);
        //unbind fd
        $field_fd = $this->setRedisField($fd, self::$classIndexFd);
        $result_fd = $this->redisObj->hDel($this->redisTable, $field_fd);


        if ($result_uid && $result_fd) {
            return true;
        } else {
            return false;
        }
    }


}