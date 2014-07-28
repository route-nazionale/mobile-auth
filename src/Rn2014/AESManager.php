<?php
/**
 * User: lancio
 * Date: 28/07/14
 * Time: 23:41
 */

namespace Rn2014;


use Doctrine\DBAL\Connection;

class AESManager {

    private $key;
    private $iv;

    public function __construct(Connection $conn)
    {
        $this->conn = $conn;

        if (AES_IV && AES_KEY) {

            $this->iv = AES_IV;
            $this->key = AES_KEY;

        } else {
            $this->loadFromDb();
        }
    }

    public function getEncoder()
    {
        return new AESEncoder($this->key, $this->iv);
    }

    public function loadFromDb()
    {
        $sql = "SELECT * FROM aes LIMIT 1";
        $cryptData = $this->conn->fetchAssoc($sql);

        if (!$cryptData) {
            throw new \Exception("key and iv not found");
        }

        $this->iv = base64_decode($cryptData['iv']);
        $this->key = base64_decode($cryptData['key']);
    }
}
