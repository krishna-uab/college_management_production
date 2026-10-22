<?php

namespace App\Traits;

trait UsesDatabaseConnection
{
    protected static ?string $collegeConn = null;

    public static function useConnection(string $conn): void
    {
        static::$collegeConn = $conn;
    }

    public function getConnectionName()
    {
        return static::$collegeConn ?? parent::getConnectionName();
    }
}
