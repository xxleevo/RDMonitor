<?php
include './includes/utils.php';
global $config; 
$db = new DbConnector($config['db']);
$pdo = $db->getConnection();

$devicesOnline = 0;
$devicesOffline = 0;
$invasionsPerHour = 0;
$ivPerHour = 0;

//General Data
try {
	$sql = "
	SELECT * FROM(
		SELECT 
        sum(last_seen >= UNIX_TIMESTAMP(now() - INTERVAL 10 MINUTE)) as devices_online,
        sum(last_seen < UNIX_TIMESTAMP(now() - INTERVAL 10 MINUTE)) as devices_offline
        FROM device
	) AS a
    JOIN(
		SELECT
        sum(iv is not null and first_seen_timestamp > UNIX_TIMESTAMP(NOW() - INTERVAL 1 HOUR)) as iv_per_hour
        FROM pokemon
    ) as b
    JOIN(
		SELECT
        sum(incident_expire_timestamp > UNIX_TIMESTAMP(NOW() - INTERVAL 1 HOUR)) as invasions_per_hour
        FROM pokestop
    ) as c
	";
	$result = $pdo->query($sql);
	if ($result->rowCount() > 0) {
		while ($row = $result->fetch()) {
			$devicesOnline = $row["devices_online"];
			$devicesOffline = $row["devices_offline"];
			$invasionsPerHour = $row["invasions_per_hour"];
			$ivPerHour = $row["iv_per_hour"];
		}
		// Free result set
		unset($result);
	}
} catch (PDOException $e) {
	die("ERROR: Could not able to execute $sql. " . $e->getMessage());
}

if($config['ui']['pages']['lorgnette']['enabled']){
	$db = new DbConnector($config['db_lorgnette']);
	$pdo = $db->getConnection();
	
	$sqlType = $config['db_lorgnette']['type'];
	//Get Overview Data
	$account_state = get_lorgnette_overview($sqlType, $pdo);
	$L30s = $account_state["hl"];
	$LLs = $account_state["ll"];
	$LMids = $account_state["ml"];
	$active_devices = $account_state["active"];
}

// Close connection
if($sqlType == 'mysql'){
	unset($pdo);
} else if($sqlType == 'psql'){
	pg_close($pdo);
}

//Write Data
$html = "
<div style='max-width:1440px;margin: 0 auto !important;float: none !important;'>
	<div class='card text-center m-6'>
		<div class='card-header heading dark text-light' style='font-size:30px;'>
			<span data-i18n='dashboard_overview_rdm' >Overview - RDM</span>
		</div>
	</div>
	<div class='card-body'>
		<div class='row mb-4'>
			<div class='col-md-6 mb-1'>
				<span class='list-group-item bold'>
					<h4 class='list-group-item-heading'>
						<img style='margin-right:5px;' src='static/images/online.png' width='50' height='50' /> 
						<span data-i18n='dashboard_active_devices' >Active Devices</span>
						: " . $devicesOnline . "
					</h4>
				</span>
			</div>
			<div class='col-md-6'>
				<span class='list-group-item bold'>
					<h4 class='list-group-item-heading'>
						<img style='margin-right:5px;' src='static/images/iv.png' width='50' height='50' /> 
						<span data-i18n='dashboard_iv' >IV / Hour</span>
						: " . $ivPerHour . "
					</h4>
				</span>
			</div>
			<div class='col-md-6'>
				<span class='list-group-item bold'>
					<h4 class='list-group-item-heading'>
						<img style='margin-right:5px;' src='static/images/offline.png' width='50' height='50' /> 
						<span data-i18n='dashboard_inactive_devices' >Inactive Devices</span>
						: " . $devicesOffline . "
					</h4>
				</span>
			</div>
			<div class='col-md-6'>
				<span class='list-group-item bold'>
					<h4 class='list-group-item-heading'>
						<img style='margin-right:5px;' src='static/images/invasions.png' width='50' height='50' /> 
						<span data-i18n='dashboard_invasions' >Invasions / Hour</span>
						: " . $invasionsPerHour . "
					</h4>
				</span>
			</div>
		</div>
	</div>";
	
	if($config['ui']['pages']['lorgnette']['enabled']){
		$html .= "
		<div class='card text-center m-6 mt-4'>
			<div class='card-header heading dark text-light' style='font-size:30px;'>
				
			<span data-i18n='dashboard_overview_lorgnette' >Overview - Lorgnette</span>
			</div>
		</div>
		<div class='card-body'>
			<div class='row mb-4'>
				<div class='col-md-12 mb-4'>
					<span class='list-group-item bold'>
						<h4 class='list-group-item-heading'>
							<center>
								<img style='margin-right:5px;' src='static/images/online.png' width='50' height='50' /> 
								<span data-i18n='dashboard_active_devices' >Active Devices</span>
								: " . $active_devices . "
							</center>
						</h4>
					</span>
				</div>
			</div>
				
			<div class='dark text-light text-center my-3'>
				<u>
					<span style='font-size:32px;' data-i18n='dashboard_lorgnette_accounts' >Lorgnette Accounts</span>
				</u>
			</div>
			
			<div class='row mb-4'>
				<div class='col-md-4'>
					<span class='list-group-item bold'>
						<h3 class='list-group-item-heading'>
							<img style='margin-right:5px;' src='static/images/lowlevel.png' width='50' height='50' /> 
							0-1's: <font color='#ff2c1e'>" . $LLs . "</font>
						</h3>
					</span>
				</div>
				<div class='col-md-4'>
					<span class='list-group-item bold'>
						<h4 class='list-group-item-heading'>
							<img style='margin-right:5px;' src='static/images/midlevel.png' width='50' height='50' /> 
							2-29's: <font color='skyblue'>" . $LMids . "</font>
						</h4>
					</span>
				</div>
				<div class='col-md-4'>
					<span class='list-group-item bold'>
						<h4 class='list-group-item-heading'>
							<img style='margin-right:5px;' src='static/images/highlevel.png' width='50' height='50' /> 
							30's: <font color='lime'>" . $L30s . "</font>
						</h4>
					</span>
				</div>
			</div>
		</div>";
	}
$html .= "
</div>";

echo $html;
?>
<link rel="stylesheet" href="./static/css/dashboard.css"/>