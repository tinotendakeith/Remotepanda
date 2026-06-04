<?php
require_once 'config.php';
require_once __DIR__ . '/../../../includes/database_config.php';

$dbConn = rp_remote_database_connect();
// mysql_select_db($dbName) or die('Cannot select database. ' . mysql_error());

function dbQuery($sql)
{
	global $dbConn;
	$result = mysqli_query($dbConn,$sql); //or die(mysql_error());
	return $result;
}

function dbAffectedRows()
{
	global $dbConn;
	return mysqli_affected_rows($dbConn);
}

function dbFetchArray($result, $resultType = MYSQLI_NUM) {
	return mysqli_fetch_array($result, $resultType);
}

function dbFetchAssoc($result)
{
	return mysqli_fetch_assoc($result);
}

function dbFetchRow($result) 
{
	return mysqli_fetch_row($result);
}

function dbFreeResult($result)
{
	return mysqli_free_result($result);
}

function dbNumRows($result)
{
	return mysqli_num_rows($result);
	
}

function dbSelect($dbName)
{
	global $dbConn;
	return mysqli_select_db($dbConn, $dbName);
}

function dbInsertId()
{
	global $dbConn;
	return mysqli_insert_id($dbConn);
}
?>
