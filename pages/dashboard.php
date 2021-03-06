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
	$AccsLastDay = $account_state["day"];
	$AccsLast2Days = $account_state["daytwo"];
	$AccsLastWeek = $account_state["week"];
	$AccsLastMonth = $account_state["month"];
	$active_devices = $account_state["active"];
	
	//Calculate the 24h percentage of relative comparison
	$AccsDayPercentage = round(((($AccsLastDay / ($AccsLast2Days - $AccsLastDay))-1)*100),2);
	if($AccsDayPercentage >= 0){
		$percentageString = "<font color='limegreen'><span style='font-size:20px;'>↑</span><span style='font-size:13px;'>" . $AccsDayPercentage . "%</span></font>";
	} else{
		$percentageString = "<font color='red'><span style='font-size:20px;'>↓</span><span style='font-size:13px;'>" . $AccsDayPercentage . "%</span></font>";
	}
	
	//Get Next 30s
	$nextAccount = get_lorgnette_next_account($sqlType, $pdo);
	$nextAccountTimeLeft = 0;
	$nextAccountTimeOver = 0;
	if (is_array($nextAccount) || is_object($nextAccount)){
		$nextAccountTimeLeft = $nextAccount["timeleft"];
		$nextAccountTimeOver = $nextAccount["timeover"];
		$nextAccountPercentage = (($nextAccountTimeOver/($nextAccountTimeLeft + $nextAccountTimeOver))*100);
		if($nextAccountPercentage > 100){
			$nextAccountPercentage = 100;
		}
	}
	$nextAccHours = 0;
	$nextAccMinutes = 0;
	if($nextAccountTimeLeft > 0){
		$nextAccHours = floor(($nextAccountTimeLeft*60)/60);
		$nextAccMinutes = ($nextAccountTimeLeft*60)%60;
	}
	echo $nextAccountTimeLeft;
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
			<div class='row'>
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
			
			<div class='row mb-4'>
				<div class='col-md-3'>
				</div>
				<div class='col-md-6'>
					<span class='list-group-item bold'>
						<h4 class='list-group-item-heading'>
							<img style='margin-right:5px;' src='static/images/day.png' width='50' height='50' /> 
							Finished Accounts in the last 24 hours: <font color='skyblue'>" . $AccsLastDay . " " .  $percentageString . "</font>
						</h4>
					</span>
				</div>
				<div class='col-md-3'>
				</div>
			</div>
			<div class='row mb-4'>
				<div class='col-md-4'>
					<span class='list-group-item bold'>
						<h4 class='list-group-item-heading'>
							<img style='margin-right:5px;' src='static/images/2days.png' width='50' height='50' /> 
							Accs last 48h: <font color='skyblue'>" . $AccsLast2Days . "</font>
						</h4>
					</span>
				</div>
				<div class='col-md-4'>
					<span class='list-group-item bold'>
						<h4 class='list-group-item-heading'>
							<img style='margin-right:5px;' src='static/images/week.png' width='50' height='50' /> 
							Accs last week: <font color='skyblue'>" . $AccsLastWeek . "</font>
						</h4>
					</span>
				</div>
				<div class='col-md-4'>
					<span class='list-group-item bold'>
						<h4 class='list-group-item-heading'>
							<img style='margin-right:5px;' src='static/images/month.png' width='50' height='50' /> 
							Accs last month: <font color='skyblue'>" . $AccsLastMonth . "</font>
							
						</h4>
					</span>
				</div>
			</div>";
			
			$html.="
			<button style='border: 1px solid white;border-bottom:none;' class='btn btn-secondary' onclick='reloadData()'><b><span data-i18n='lorgnette_button_update' >Update</span></b></button>
			<div class='row mb-4 float-none'>
				<div class='col-md-12 mb-4'>
					<span class='list-group-item bold'>
						<h3 class='list-group-item-heading'>
							<span style='float:left;'><span data-i18n='lorgnette_nextAccount'>Next Acc ready in</span>: <b>";
							if ($nextAccHours > 0){
							$html .= $nextAccHours . "h";
							}
							$html .= " " . $nextAccMinutes . "m</b></span>
							<span style='float:right;'>" . round($nextAccountPercentage,2) . "%</span>
							<span style='float:clear;'></span><br>
						</h3>
						<div class='progress' style='height: 40px;'>
							<div id='accountBar' class='progress-bar progress-bar-striped progress-bar-animated bg-success' role='progressbar' aria-valuenow='0' aria-valuemin='0' aria-valuemax='100' style='width: 0%;'>
							</div>
						</div>
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
						<h4 class='list-group-item-heading'>
							<img style='margin-right:5px;' src='static/images/lowlevel.png' width='50' height='50' /> 
							0-1's: <font color='#ff2c1e'>" . $LLs . "</font>
						</h4>
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
<script type="text/javascript">
var accTimeLeft = <?php echo $nextAccountTimeLeft ?>;
var accTimeOver = <?php echo $nextAccountTimeOver ?>;
var totalTime = accTimeLeft + accTimeOver;
var perc = Math.round((accTimeOver/totalTime) * 100);
if(perc > 100){
	perc = 100;
}
var percString = perc.toString() + "%";
console.log(perc);
$(".progress-bar").animate({
    width: percString
}, 400);

function reloadData(){
location.reload()
}
</script>