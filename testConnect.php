<?php

require "../database/database.php";
$pdo = Database::connect();

$sql = "SELECT * FROM  iss_persons";
$data = $pdo->query($sql);
 $data->execute();
 $data = $data->fetch(PDO::FETCH_ASSOC);


print_r($data);





?>