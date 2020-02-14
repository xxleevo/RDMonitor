<?php
//Utils 

function get_transferable_amount($sqlType, $pdo){
	if($sqlType == 'psql'){
		$sql = "
			SELECT
				(select count(*) from accounts where level = 30 AND reason IS NULL AND logout < extract(epoch from now()-INTERVAL '2 hour')) as transferable, 
				(select count(*) from accounts where level = 30 AND reason IS NOT NULL AND reason != 'record') as failed, 
				(select count(*) from accounts where level = 30 AND logout > extract(epoch from now()-INTERVAL '2 hour')) as cooldowned,
				(select count(*) from accounts where level = 30 AND reason IS NOT NULL AND reason = 'record') as record;
		";
		$result = pg_query($sql) or die('Abfrage fehlgeschlagen: ' . pg_last_error());
		$row = pg_fetch_array($result, null, PGSQL_ASSOC);
		// Free result set
		pg_free_result($result);
		return $row;
	} else if($sqlType == 'mysql'){
		try {
			$sql = "
				SELECT 
					sum(reason IS NULL AND logout < UNIX_TIMESTAMP(NOW() - INTERVAL 2 HOUR)) as transferable,
					sum(reason IS NOT NULL AND reason != 'record') as failed,
					sum(logout > UNIX_TIMESTAMP(NOW() - INTERVAL 2 HOUR)) as cooldowned,
					sum(reason IS NOT NULL AND reason = 'record') as record
				FROM accounts
				WHERE level >= 30;
			";
			$result = $pdo->query($sql);
			if ($result->rowCount() > 0) {
				$row = $result->fetch();
				// Free result set
				unset($result);
				return $row;
			}
		} catch (PDOException $e) {
			die("ERROR: Could not able to execute $sql. " . $e->getMessage());
		}
	}
}

function get_lorgnette_overview($sqlType, $pdo){
	if($sqlType == 'psql'){
		$sql = "
			SELECT
				(select count(*) from accounts where level = 30) as hl, 
				(select count(*) from accounts where level = 0 OR level = 1) as ll, 
				(select count(*) from accounts where level >1 AND level <30) as ml,
				(select count(*) as active from accounts where device_id is not null AND updated > extract(epoch from now()-INTERVAL '5 minute'));
		";
		$result = pg_query($sql) or die('Abfrage fehlgeschlagen: ' . pg_last_error());
		$row = pg_fetch_array($result, null, PGSQL_ASSOC);
		// Free result set
		pg_free_result($result);
		return $row;
	} else if($sqlType == 'mysql'){
		try {
			$sql = "
		
			SELECT * FROM(
				SELECT sum(level=30) as hl, sum(level=0 OR level = 1) as ll, sum(level >1 AND level <30) as ml from accounts
			) AS a
			JOIN(
				SELECT COUNT(*) as active from accounts where device_id is not null AND updated >= UNIX_TIMESTAMP(NOW() - INTERVAL 5 MINUTE)
			) AS b
			";
			$result = $pdo->query($sql);
			if ($result->rowCount() > 0) {
				$row = $result->fetch();
				// Free result set
				unset($result);
				return $row;
			}
		} catch (PDOException $e) {
			die("ERROR: Could not able to execute $sql. " . $e->getMessage());
		}
	}
}
function get_lorgnette_accounts($sqlType, $pdo, $conditions){
	switch($conditions){
		case 'ongoing':
			$conds = "WHERE device_id IS NOT NULL";
			$order = "ORDER BY online DESC, level DESC";
			break;
		case 'all':
			$conds = "";
			$order = "ORDER BY xp DESC";
			break;
		case 'done':
			$conds = "WHERE level = 30 AND logout IS NOT NULL";
			$order = "ORDER BY level DESC,hour ASC";
			break;
	}
	if($sqlType == 'psql'){
		$sql = "
			SELECT 
				username, 
				level, 
				spins, 
				device_id, 
				route_num as route, 
				total_exp as xp, 
				round((updated-login)/3600.0, 2) as hour,
				to_timestamp(updated) as updated,
				logout,
				reason as failed,
				updated > extract(epoch from now() - INTERVAL '5 minute') as online
			FROM 
			accounts
			$conds
			$order;
		";
		$result = pg_query($sql) or die('Abfrage fehlgeschlagen: ' . pg_last_error());
		$i = 0;
		$data = [];
		while($row = pg_fetch_array($result, NULL, PGSQL_ASSOC)){
			$data[$i]["level"] = $row["level"];
			$data[$i]["device_id"] = $row["device_id"];
			$data[$i]["username"] = $row["username"];
			$data[$i]["spins"] = $row["spins"];
			$data[$i]["route"] = $row["route"];
			$data[$i]["xp"] = $row["xp"];
			$data[$i]["hour"] = $row["hour"];
			$data[$i]["updated"] = $row["updated"];
			$data[$i]["online"] = $row["online"];
			$data[$i]["logout"] = $row["logout"];
			$data[$i]["failed"] = $row["failed"];
			$i++;
		}
		// Free result set
		pg_free_result($result);
		return $data;
	} else if($sqlType == 'mysql'){
		switch($conditions){
			case 'ongoing':
				$conds = "WHERE device_id IS NOT NULL";
				$order = "ORDER BY online DESC, level DESC";
				break;
			case 'all':
				$conds = "";
				$order = "ORDER BY xp DESC";
				break;
			case 'done':
				$conds = "WHERE level = 30 AND logout IS NOT NULL";
				$order = "ORDER BY level DESC,hour ASC";
				break;
		}
		try {
			$sql = "
				SELECT 
					username, 
					level, 
					spins, 
					device_id, 
					route_num as route, 
					total_exp as xp, 
					round((updated-login)/3600.0, 2) as hour, 
					FROM_UNIXTIME(updated) as updated,
					logout,
					reason as failed,
					updated > UNIX_TIMESTAMP(NOW() - INTERVAL 5 MINUTE) as online
				FROM accounts 
			$conds
			$order;
			";
			$result = $pdo->query($sql);
			if ($result->rowCount() > 0) {
				$i = 0;
				$data = [];
				while ($row = $result->fetch()) {
					$data[$i]["level"] = $row["level"];
					$data[$i]["device_id"] = $row["device_id"];
					$data[$i]["username"] = $row["username"];
					$data[$i]["spins"] = $row["spins"];
					$data[$i]["route"] = $row["route"];
					$data[$i]["xp"] = $row["xp"];
					$data[$i]["hour"] = $row["hour"];
					$data[$i]["updated"] = $row["updated"];
					$data[$i]["online"] = $row["online"];
					$data[$i]["logout"] = $row["logout"];
					$data[$i]["failed"] = $row["failed"];
					$i++;
				}
				// Free result set
				unset($result);
				return $data;
			}
		} catch (PDOException $e) {
			die("ERROR: Could not able to execute $sql. " . $e->getMessage());
		}
	}
}

function get_lorgnette_next_account($sqlType, $pdo){
	if($sqlType == 'psql'){
		$sql = "
		
			SELECT
				((2000000 - total_exp) / (total_exp / ((updated+1-login)/60/60))) as timeleft,
                ((updated-login)/60/60) as timeover
			FROM accounts
			WHERE updated > extract(epoch from now()-INTERVAL '5 minute') AND device_id is not null AND total_exp > 0 AND login is not null
			ORDER BY timeleft ASC LIMIT 1;
		";
		$result = pg_query($sql) or die('Abfrage fehlgeschlagen: ' . pg_last_error());
		$row = pg_fetch_array($result, null, PGSQL_ASSOC);
		// Free result set
		pg_free_result($result);
		return $row;
	} else if($sqlType == 'mysql'){
		try {
			$sql = "
				SELECT
					round(((2000000 - total_exp) / (total_exp / ((updated+1-login)/60/60))),3) as timeleft,
                    round(((updated-login)/60/60),3) as timeover
				FROM
					accounts
				WHERE 
					updated > UNIX_TIMESTAMP(NOW() - INTERVAL 5 MINUTE) AND device_id is not null AND total_exp > 0 AND login is not null
				ORDER BY timeLeft ASC LIMIT 1;
			";
			$result = $pdo->query($sql);
			if ($result->rowCount() > 0) {
				$row = $result->fetch();
				// Free result set
				unset($result);
				return $row;
			}
		} catch (PDOException $e) {
			die("ERROR: Could not able to execute $sql. " . $e->getMessage());
		}
	}
}
?>