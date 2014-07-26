<?php

namespace Rn2014;

use Doctrine\DBAL\Connection ;

class Varchi
{
    public function __construct(Connection $conn)
    {
        $this->conn = $conn;
    }

    public function existsPerson($cu, $birthdate)
    {
        $sql = "SELECT * FROM date WHERE cu = :cu AND datanascita = :birthdate limit 1";
        $result = $this->conn->fetchAssoc($sql, [
            'cu' => $cu,
            'birthdate' => $birthdate
        ]);

        return ($result || 0);
    }

    public function isCapoSpalla($cu)
    {
        $sql = "SELECT * FROM assegnamenti WHERE codiceUnivoco = :cu AND staffEvent = 1";
        $result = $this->conn->fetchAssoc($sql, [
            'cu' => $cu,
        ]);


        return ($result || 0);
    }

    public function findByCu($cu)
    {
        $sql = "SELECT * FROM `persone`,`date` WHERE persone.codiceUnivoco = date.cu AND   `codiceUnivoco` = :cu ";

        $result = $this->conn->fetchAssoc($sql, [
            'cu' => $cu,
        ]);


        return $result;
    }
}