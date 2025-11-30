<?php

namespace CT275\Labs;

use PDO;

class PDOFactory 
{
   public function create(array $config): PDO
   {
      [
         'dbhost' => $dbhost,
         'dbname' => $dbname,
         'dbuser' => $dbuser,
         'dbpass' => $dbpass
      ] = $config;
      $dns = "pgsql:host={$dbhost}; dbname={$dbname};";
      $options = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION];
      return new PDO($dns, $dbuser, $dbpass, $options);
   }
}