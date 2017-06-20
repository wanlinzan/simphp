<?php
namespace simphp;

class Mysql
{
    /**
     * @var array
     */
    private $_config = [
        'type' => 'mysql',
        'hostname' => '',
        'database' => '',
        'username' => '',
        'password' => '',
        'charset' => 'utf8mb4',
        'port' => 3306
    ];

    /**
     * @var \PDO
     */
    private $_mysql;


    /**
     * @var  \PDOStatement
     */
    private $_stmp;


    /**
     * @var array 执行过的sql集合
     */
    private $_sqlRecord = [];


    public function __construct($config)
    {
        //合并配置项
        $this->_config = array_merge($this->_config, $config);
        $this->_mysql = new \PDO("{$this->_config['type']}:host={$this->_config['hostname']};dbname={$this->_config['database']}", $this->_config['username'], $this->_config['password']);
        $this->exec('set names ' . $this->_config['charset']);
    }

    //所有的sql执行都从这里开始
    public function exec($sql, $data = [])
    {
        $this->_sqlRecord[] = $sql;//记录此sql
        $this->_stmp = $this->_mysql->prepare($sql);
        foreach ($data as $key => $value) {
            $this->_stmp->bindValue(":{$key}", $value);
        }
        if (false === $this->_stmp->execute()) {
            $info = $this->_stmp->errorInfo();
            throw new \Exception($info[2]);
        }
    }

    //单表单行插入
    public function insert($table = null, $data = [])
    {
        $keys = implode(',', array_keys($data));
        $values = ":" . implode(",:", array_keys($data));
        $sql = "insert into {$table} ({$keys}) values ({$values})";
        $this->exec($sql, $data);
    }

    //单表更新
    public function update($table, $data, $where = '')
    {
        if (!empty($where)) {
            $where = " where {$where}";
        }
        $keys = '';
        foreach ($data as $key => $value) {
            $keys .= "{$key}=:{$key},";
        }
        $keys = substr($keys, 0, -1);
        $sql = "update {$table} set {$keys}{$where}";
        $this->exec($sql, $data);
    }

    public function delete($table, $where = '')
    {
        if (!empty($where)) {
            $where = " where {$where}";
        }
        $sql = "delete from {$table}{$where}";
        $this->exec($sql);
    }

    public function select($sql,$data = [])
    {
        $this->exec($sql,$data);
        return $this->_stmp->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function find($sql,$data = [])
    {
        $this->exec($sql,$data);
        $result = $this->_stmp->fetch(\PDO::FETCH_ASSOC);
        return $result ? $result : [];
    }

    public function field($sql,$data = [])
    {
        $this->exec($sql,$data);
        $result = $this->_stmp->fetch(\PDO::FETCH_ASSOC);
        return is_array($result) ? current($result) : '';
    }

    public function debug()
    {
        echo '<pre>';
        print_r($this->_sqlRecord);
        print_r($this->_stmp->debugDumpParams());
        echo '</pre>';
    }

    public function lastInsertId()
    {
        return $this->_mysql->lastInsertId();
    }

    public function begin()
    {
        $this->_mysql->beginTransaction();
    }

    public function commit()
    {
        $this->_mysql->commit();
    }

    public function rollback()
    {
        $this->_mysql->rollBack();
    }

    public function __destruct()
    {
        $this->_mysql = null;
    }
}