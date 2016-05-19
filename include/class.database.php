<?php

class database
{
    public $dbh;
    private $host;
    private $dbname;
    private $user;
    private $pass;

    public function __construct($host=HOST, $dbname=DATABASE, $user=USER, $pass=PASSWORD)
    {
        $this->host = $host;
        $this->dbname = $dbname;
        $this->user = $user;
        $this->pass = $pass;
    }

    private function connect(array $options=array())
    {
        $this->dbh = new PDO("mysql:host={$this->host};dbname={$this->dbname}", $this->user, $this->pass, $options);
        $this->dbh->setAttribute(PDO::ATTR_AUTOCOMMIT,0);
        $this->dbh->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    }

    private function disconnect()
    {
        $this->dbh = null;
    }


    public function getError()
    {
        $this->connect();
        $results = $this->dbh->errorInfo();
        $this->disconnect();
        return $results;
    }

    public function prepare($query, array $params)
    {
        $this->connect();
        $results = $this->dbh->prepare($query);
        $results->execute($params);
        $results = $results->fetchAll();
        $this->disconnect();
        return $results;
    }

    public function query($query)
    {
        $this->connect();
        $results = $this->dbh->query($query);
        $results = $results->fetchAll();
        $this->disconnect();
        return $results;
    }

}