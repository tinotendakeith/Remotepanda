<?php

//delete.php
require_once __DIR__ . '/database_config.php';

if(isset($_POST["id"]))
{
$connect = rp_remote_database_pdo();
 $query = "
 DELETE from events WHERE id=:id
 ";
 $statement = $connect->prepare($query);
 $statement->execute(
  array(
   ':id' => $_POST['id']
  )
 );
}

?>
