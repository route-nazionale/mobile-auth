<?php

namespace Rn2014;

use Doctrine\DBAL\Connection ;

class Statistic
{
    public function __construct(Connection $conn)
    {
        $this->conn = $conn;
    }

    public function findByTimeAndImei($time, $imei)
    {
        $sql = "SELECT codiceUnivoco FROM statistiche where timeStamp = :timeStamp and imei = :imei limit 1";

        $res = $this->conn->fetchAssoc($sql, [
            'timeStamp' => $time,
            'imei' => $imei,
        ]);

        if ($res ) {
            return false;
        }
    }

    public function insertStatistics($stat)
    {
        if ($this->findByTimeAndImei($stat->time, $stat->imei)) {
            return false;
        }

        $data = [
            'codiceUnivoco' => $stat->cu,
            'ristampaBadge' => $stat->reprint,
            'timeStamp' => $stat->time,
            'codiceOperatore' => $stat->operator,
            'turno' => $stat->turn,
            'imei' => $stat->imei,
            'tipo' => $stat->type,
            'idVarco' => $stat->gate,
        ];

        if ($this->conn->insert('statistiche', $data)) {
            return $data;
        }
        return false;
    }
}