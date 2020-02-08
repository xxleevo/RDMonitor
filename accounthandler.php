<?php
include './config.php';
include './includes/DbConnector.php';

header( 'Content-Type: application/json' );
$now = new DateTime();
$now->sub( new DateInterval( 'PT20S' ) );
$d           = array();
$d["timestamp"] = $now->getTimestamp();

$action 		= ! empty( $_POST['action'] ) ? $_POST['action'] : '';
$username 		= ! empty( $_POST['username'] ) ? $_POST['username'] : '';
$amount 		= ! empty( $_POST['amount'] ) ? $_POST['amount'] : '';

if ( $action === "transferAccount") {
	$sqlType = $config['db_lorgnette']['type'];
	// Initiate lorgnette db for data grab
	global $config;
	$db = new DbConnector($config['db_lorgnette']);
	$pdo = $db->getConnection();
	// Get Account Details from Lorgnette DB
	if($sqlType == 'psql'){
		$sql = "
		SELECT
			password, level, logout
		FROM accounts where username = '$username';
		";
		$sqlStatus = "Not Executed";
		$result = pg_query($sql) or die('Error: ' . pg_last_error());
		$row = pg_fetch_array($result, null, PGSQL_ASSOC);
		if($row > 0){
			$password = $row['password'];
			$level = $row['level'];
			$logout = $row['logout'];
			$sqlStatus = "Success";
		} else{
			$sqlStatus = "Error";
		}
	
		// Free result set
		pg_free_result($result);
	} else if($sqlType == 'mysql'){
		try {
			$sql = "
			SELECT
				password, level, logout
			FROM accounts where username = '$username';
			";
			$result = $pdo->query($sql);
			$sqlStatus = "Not Executed";
			if ($result->rowCount() > 0) {
				$count = $result->rowCount();
				$row = $result->fetch();
				$password = $row['password'];
				$level = $row['level'];
				$logout = $row['logout'];
				$sqlStatus = "Success";
			} else{
				$sqlStatus = "Error";
			}
			// Free result set
			unset($result);
		} catch (PDOException $e) {
			$sqlStatus  = "Error! ". $e->getMessage();
			die("ERROR: Could not able to execute $sql. " . $e->getMessage());
		}
	}
	$d['details'] = "account_get: " . $sqlStatus;
	
	// Close connection
	if($sqlType == 'mysql'){
		unset($pdo);
	} else if($sqlType == 'psql'){
		//pg_close($pdo);
	}
	
	//#######################################
	
	// Import in RDM and check if its there
		global $config;
		$importStatus = "Not Done";
		$verifyStatus = "Not Done";
		$verifyStatusCode = -1;
		$db = new DbConnector($config['db']);
		$pdo = $db->getConnection();
		// Set SQLs
		$sql = "
			INSERT INTO account(username, password, level, last_encounter_time, spins, tutorial)
			VALUES ('$username', '$password', $level, $logout, 0, 1);
		";
		$sql_verify = "
			SELECT username from account where username = '$username';
		";
		// Import
		try {
			$result = $pdo->query($sql);
			// If inserted Successfully, put it to Done
			if ($result->rowCount() > 0) {
				$importStatus = "Done";
			} else{
				$importStatus = "Error";
			}
			// Free result set
			unset($result);
		} catch (PDOException $e) {
			$importStatus = "Error!" . $e->getMessage();
		}
		//Verify
		try {
			$result_verify = $pdo->query($sql_verify);
			if ($result_verify->rowCount() > 0) {
				$row = $result_verify->fetch();
				$verifyStatus = "verified";
				$verifyStatusCode = 1;
				
			} else{
				$verifyStatus = "not verified";
				$verifyStatusCode = 0;
			}
			// Free result set
			unset($result_verify);
		} catch (PDOException $e) {
			$verifyStatus = "Error!" . $e->getMessage();
		}
		//Set Details
		$d['details'] .= " import_account: " . $importStatus;
		$d['details'] .= " verify_rdm_account: " . $verifyStatusCode . "(" . $verifyStatus . ")";
		
		//If Account is in RDM DB, delete from Lorgnette
		$deleteStatus = "Not Done";
		if($verifyStatusCode === 1 && $importStatus == "Done"){
			if($sqlType == 'psql'){
				$sql = "
					DELETE 
					FROM accounts where username = '$username';
				";
				$deleteStatus = "Not Executed";
				$result = pg_query($sql) or die('Error: ' . pg_last_error());
				if (pg_affected_rows($result) > 0) {
					$deleteStatus = "Done";
				} else{
					$deleteStatus = "Unknown Error";
				}
				$password = $row['password'];
				$level = $row['level'];
				$logout = $row['logout'];

				// Free result set
				pg_free_result($result);
			} else if($sqlType == 'mysql'){
				// Mysql - Delete Account
				global $config;
				$db = new DbConnector($config['db_lorgnette']);
				$pdo = $db->getConnection();
				$sql = "
					DELETE 
					FROM accounts where username = '$username';
				";
				$deleteStatus = "Not Executed";
				try {
					$result = $pdo->query($sql);
						if ($result->rowCount() > 0) {
							$deleteStatus = "Done";
						} else{
							$deleteStatus = "Unknown Error";
						}
					// Free result set
					unset($result);
					} catch (PDOException $e) {
						$verifyStatus = "Error!" . $e->getMessage();
					}
			}
			// Close connection
			if($sqlType == 'mysql'){
				unset($pdo);
			} else if($sqlType == 'psql'){
				pg_close($pdo);
			}
		}
		// Append DeleteStatus
		$d['details'] .= " account_lorgnette_deleted: " . $deleteStatus;
	
	//Check Status and set success or error
	if($sqlStatus == "Success" && $verifyStatusCode === 1 && $importStatus == "Done" & $verifyStatus == "verified" && $deleteStatus == "Done"){
		$d['status'] = "Success";
	} else{
		$d['status'] = "Error - Something went wrong - Check your console!";
	}
}

$jaysson = json_encode($d);
echo $jaysson;
?>