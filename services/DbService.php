<?php

class DbService
{
    protected $pdo;

    public function __construct(PDO $pdoInstance)
    {
        $this->pdo = $pdoInstance;
    }

    public function execSql($query, $bindParams = [])
    {
        $stmt = $this->pdo->prepare($query);

        foreach ($bindParams as $key => $val)
        {
            if (is_int($val))
            {
                $type = PDO::PARAM_INT;
            } else {
                $type = PDO::PARAM_STR;
            }

            $stmt->bindParam(':'.$key, $val, $type);
        }

        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $result;
    }
}