<?php

use App\Database;
use App\Schema;
use Dotenv\Dotenv;

require __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();

date_default_timezone_set('Africa/Addis_Ababa');

$pdo = Database::connection();
Schema::ensure($pdo);

return $pdo;
