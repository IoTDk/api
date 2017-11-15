<?php
/**
 * Created by PhpStorm.
 * User: David
 * Date: 14/11/2017
 * Time: 11:08
 */

class Database {

    private $host = "mysql14.gigahost.dk";
    private $db_name = "iotdk_buttonapp";
    private $user = "iotdk";
    private $passwd = "8c#gDjMN}]q7.)_L+[x{h2Fsv~BTme";
    public $connection;

    public function connect () {
        $connection_str = "mysql:host=$this->host;dbname=$this->db_name";

        $dbConnection = new PDO($connection_str, $this->user, $this->passwd, array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION));
        return $dbConnection;
    }

}