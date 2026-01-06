<?php
date_default_timezone_set('Asia/Bangkok'); // ตั้งค่า Timezone เป็นประเทศไทย
$host = "sql208.infinityfree.com";
$db   = "if0_40816527_cafe";
$user = "if0_40816527";
$pass = "A8QHxds5qyX";
$charset = "utf8mb4";

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
     $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
     throw new \PDOException($e->getMessage(), (int)$e->getCode());
}
?>