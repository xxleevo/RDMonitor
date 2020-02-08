<?php
include './config.php';
include './includes/utils.php';

// Establish connection to database
$databases = [];
$pokeDatas = [];
$itemDatas = [];
$dustDatas = [];
$L30s = 0;
$LLs = 0;
$LMids = 0;
$active_devices = 0;

global $config;
$db = new DbConnector($config['db_lorgnette']);
$pdo = $db->getConnection();
//General Data
$sqlType = $config['db_lorgnette']['type'];
$account_state = get_lorgnette_overview($sqlType, $pdo);
$L30s = $account_state["hl"];
$LLs = $account_state["ll"];
$LMids = $account_state["ml"];
$active_devices = $account_state["active"];


// Write all Data
echo "<div style='max-width:1440px;margin: 0 auto !important;float: none !important;'>
	<div class='card text-center my-1 m-6'>
		<div class='card-header heading dark text-light' style='font-size:30px;'>
			<span data-i18n='overview' >Overview</span>
		</div>
	</div>

	<div class='row mb-4 mt-2'>

		<div class='col-md-3'>
			<span class='list-group-item bold' style='border: 1px solid white;'>
				<h4 class='list-group-item-heading'>
					<img style='margin-right:5px;' src='static/images/online.png' width='50' height='50' /> 
					<span data-i18n='lorgnette_active_devices' >Active Devices</span>
					: " . $active_devices . "
				</h4>
			</span>
		</div>
		<div class='col-md-3'>
			<span class='list-group-item bold' style='border: 1px solid white;'>
				<h4 class='list-group-item-heading'>
					<img style='margin-right:5px;' src='static/images/lowlevel.png' width='50' height='50' /> 
					<span data-i18n='lorgnette_lowlevel_accounts' >L0-1 Pool</span>
					: " . $LLs . "
				</h4>
			</span>
		</div>
		<div class='col-md-3'>
			<span class='list-group-item bold' style='border: 1px solid white;'>
				<h4 class='list-group-item-heading'>
					<img style='margin-right:5px;' src='static/images/midlevel.png' width='50' height='50' /> 
					<span data-i18n='lorgnette_midlevel_accounts' >L2-29 Pool</span>
					: " . $LMids . "
					</h4>
			</span>
		</div>
		<div class='col-md-3'>
			<span class='list-group-item bold' style='border: 1px solid white;'>
				<h4 class='list-group-item-heading'>
					<img style='margin-right:5px;' src='static/images/highlevel.png' width='50' height='50' /> 
					<span data-i18n='lorgnette_highlevel_accounts' >L30 Pool</span>
					: " . $L30s . "
				</h4>
			</span>
		</div>

	</div>";
	echo "
	<div class='tab text-center m-2' style='font-size:20px;'>
			<button class='tablinks heading active' onclick='switchContainer(event,\"ongoingContainer\")'><b><span data-i18n='lorgnette_button_ongoing' >Ongoing</span></b></button>
			<button class='tablinks heading' onclick='switchContainer(event,\"allContainer\")'><b><span data-i18n='lorgnette_button_all' >All</span></b></button>
			<button class='tablinks heading' onclick='switchContainer(event,\"doneContainer\")'><b><span data-i18n='lorgnette_button_done' >Done</span></b></button>
	</div>";
	
	//####################
	//Ongoing Container
	//####################
	echo "
	<div id='ongoingContainer' class='tabcontent'>
		<div class='card text-center m-6'>
			<div class='card-header heading dark text-light' style='font-size:30px;'>
				<span data-i18n='lorgnette_ongoing_leveling' >Ongoing Leveling</span>
			</div>
		</div>";
		
		echo "<div id='no-more-tables'>
			<table id='quest-table' class='table table-dark table-striped' border='1'>
				<thead class='thead-dark'>
					<tr class='text-nowrap'>
						<th data-i18n='lorgnette_table_level' class='level'>Level</th>
						<th data-i18n='lorgnette_table_device' class='device'>Device</th>
						<th data-i18n='lorgnette_table_total_xp' class='xp'>Total XP</th>
						<th data-i18n='lorgnette_table_xp_per_hour' class='xph'>XP/Hr</th>
						<th data-i18n='lorgnette_table_spins' class='spins'>Spins</th>
						<th data-i18n='lorgnette_table_spins_per_hour' class='spinshour'>Spins/Hr</th>
						<th data-i18n='lorgnette_table_xp_per_spin' class='xpspins'>XP/Spin</th>
						<th data-i18n='lorgnette_table_route' class='route'>Route</th>
						<th data-i18n='lorgnette_table_user' class='username'>User</th>
						<th data-i18n='lorgnette_table_duration' class='hour'>Duration</th>
						<th data-i18n='lorgnette_table_estimated_finish' class='hour'>Est. Finish</th>
						<th data-i18n='lorgnette_table_estimated_total' class='hour'>Est. Time</th>
						<th data-i18n='lorgnette_table_status' class='status'>Status</th>
					</tr>
				</thead>";
				
		$result = get_lorgnette_accounts($sqlType, $pdo, 'ongoing');
		foreach($result as $row){
			$level = $row['level']; 
			$device = $row['device_id']; 
			$username = substr($row['username'], 0, 6) . "...";
			$spins = $row['spins'];
			$device_id = $row['device_id'];
			$route = $row['route'];
			$updated = $row['updated'];
			$hour = floor($row['hour']);
			$minute = ($row['hour']*60)%60;
			$status = get_status($row['online'], $sqlType);
			$spinsPerHour = round($spins / $row['hour']);
			
			$xp = $row['xp'];
			$xph = round(($xp / $row['hour'])/1000,1);
			$avg_xp_stop = round($xp / $spins);
			
			//Estimates
			$xpNeeded = 2000000;
			$estXpPerHour = ($xp / $row['hour']);
			
			$estFinish = ($xpNeeded - $xp)/($estXpPerHour);
			$estFinishHours = floor($estFinish);
			$estFinishMinutes = ($estFinish*60)%60;
			
			$estTime = $row['hour'] + $estFinish;
			$estTimeHours = floor($estTime);
			$estTimeMinutes = ($estTime*60)%60;
			//Build Table
			echo "
				<tr class='text-nowrap'>
					<td data-title='level'>" . $level . "</td>
					<td data-title='device'>" . $device . "</td>
					<td data-title='xp'>" . $xp . "</td>
					<td data-title='xph'>" . $xph . "K</td>
					<td data-title='spins'>" . $spins . "</td>
					<td data-title='spinshour'>" . $spinsPerHour . "</td>
					<td data-title='xpspins'>" . $avg_xp_stop . "</td>
					<td data-title='route'>" . $route . "</td>
					<td data-title='username'>" . $username . "</td>
					<td data-title='hour'>" . $hour . "h " .  $minute  ."m" ."</td>
					<td data-title='estfinish'>" .$estFinishHours . "h " .  $estFinishMinutes  ."m" ."</td>
					<td data-title='esttime'>" . $estTimeHours . "h " . $estTimeMinutes . "m" . "</td>
					<td data-title='status'>" . $status . "</td>
				</tr>
			";
		}
		echo "
			</table>
		</div>
	</div>";

	
	
	//####################
	//All Container
	//####################
	echo "
	<div id='allContainer' class='tabcontent active'>
		<div class='card text-center m-6'>
			<div class='card-header heading dark text-light' style='font-size:30px;'>
				<span data-i18n='lorgnette_all_accounts' >All Accounts</span>
			</div>
		</div>";
		
		echo "<div id='no-more-tables'>
			<table id='quest-table' class='table table-dark table-striped' border='1'>
				<thead class='thead-dark'>
					<tr class='text-nowrap'>
						<th data-i18n='lorgnette_table_level' class='level'>Level</th>
						<th data-i18n='lorgnette_table_duration' class='hour'>Duration</th>
						<th data-i18n='lorgnette_table_total_xp' class='xp'>Total XP</th>
						<th data-i18n='lorgnette_table_spins' class='spins'>Spins</th>
						<th data-i18n='lorgnette_table_user' class='username'>User</th>
						<th data-i18n='lorgnette_table_updated' class='updated'>Updated</th>
					</tr>
				</thead>";
				
		$result = get_lorgnette_accounts($sqlType, $pdo, 'all');
		foreach($result as $row){
			$username = substr($row['username'], 0, 6) . "...";
			$level = $row['level']; 
			$spins = $row['spins'];
			$device_id = $row['device_id'];
			$route = $row['route'];
			$xp = $row['xp'];
			$hour = floor($row['hour']);
			$minute = ($row['hour']*60)%60;
			$updated = $row['updated'];
			//Build Table
			echo "
				<tr class='text-nowrap'>
					<td data-title='level'>" . $level . "</td>
					<td data-title='hour'>" . $hour . "h " .  $minute  ."m" ."</td>
					<td data-title='xp'>" . $xp . "</td>
					<td data-title='spins'>" . $spins . "</td>
					<td data-title='username'>" . $username . "</td>
					<td data-title='updated'>" . $updated . "</td>
				</tr>
			";
		}
		echo "
			</table>
		</div>
	</div>";
	
	
	//####################
	//Done Container
	//####################
	echo "
	<div id='doneContainer' class='tabcontent'>
		<div class='card text-center m-6'>
			<div class='card-header heading dark text-light' style='font-size:30px;'>
				<span data-i18n='lorgnette_done_accounts' >Accounts Done</span>
			</div>
		</div>";
		
		
		echo "<div style='border: 1px solid white;border-top:none;'>";
			$result = get_lorgnette_accounts($sqlType, $pdo, 'done');
			$averages = [];
			if(sizeOf($result) > 1){
				$showAverages = true;
				$resultAmount = sizeOf($result);
				//Set Averages to 0 first
				$averages["XPH"] = 0;
				$averages["Spins"] = 0;
				$averages["SpinsPerEgg"] = 0;
				$averages["Duration"] = 0;
				$averages["XPS"] = 0;
				$averages["XPS"] = 0;
			} else{
				$showAverages = false;
			}
			if($config['ui']['pages']['lorgnette']['highLevelAverageLimitTime'] !== null && $config['ui']['pages']['lorgnette']['highLevelAverageLimitTime']){
				$resultAmount = 0;
				$includeTime = $config['ui']['pages']['lorgnette']['l30AverageIncludeTime'];
				$durationString = "(<span data-i18n='lorgnette_lasthours_1'>Last</span> " . $includeTime . " <span data-i18n='lorgnette_lasthours_2'>Hours</span>)";
				$now = date_timestamp_get(date_create());
			} else{
				$durationString = "";
			}
			foreach($result as $row){
				//Build Averages
				if($config['ui']['pages']['lorgnette']['highLevelAverageLimitTime'] !== null && $config['ui']['pages']['lorgnette']['highLevelAverageLimitTime']){
					if(($now - $row['logout']) <= $includeTime*3600){
						$averages["XPH"] += $row['xp'] / $row['hour'];
						$averages["Duration"] += $row['hour'];
						$averages["Spins"] += $row['spins'];
						$averages["SpinsPerEgg"] += round((($row['xp'] - ($row['spins'] * 250)) /250)/getEggAmount($row['level']));
						$averages["XPS"] += $row['xp'] / $row['spins'];
						$resultAmount++;
					}
				}else{
					$averages["XPH"] += $row['xp'] / $row['hour'];
					$averages["Duration"] += $row['hour'];
					$averages["Spins"] += $row['spins'];
					$averages["SpinsPerEgg"] += round((($row['xp'] - ($row['spins'] * 250)) /250)/getEggAmount($row['level']));
					$averages["XPS"] += $row['xp'] / $row['spins'];
				}
			}
			//Print Averages
			if($showAverages){
				echo "
				<div class='text-center dark text-light p-1' style='font-size:28px;'>
					<u><span data-i18n='lorgnette_averages_header' >Average Data</span> " . $durationString . "</u>
				</div>
					
				<div class='row m-2 p-2 mb-3'>
					<div class='col-md-3'>
						<span class='list-group-item bold' style='border: 1px solid white;'>
							<h4 class='list-group-item-heading'>
								<img style='margin-right:5px;' src='static/images/xp.png' width='50' height='50' /> 
								<span data-i18n='lorgnette_averages_xph' >XP/Hr</span>
								: " . round((($averages["XPH"] / $resultAmount)/1000),1) . "K
							</h4>
						</span>
					</div>
					<div class='col-md-3'>
						<span class='list-group-item bold' style='border: 1px solid white;'>
							<h4 class='list-group-item-heading'>
								<img style='margin-right:5px;' src='static/images/spins.png' width='50' height='50' /> 
								<span data-i18n='lorgnette_averages_spins' >Spins</span>
								: " . round($averages["Spins"] / $resultAmount) . "
							</h4>
						</span>
					</div>
					<div class='col-md-3'>
						<span class='list-group-item bold' style='border: 1px solid white;'>
							<h4 class='list-group-item-heading'>
								<img style='margin-right:5px;' src='static/images/egg.png' width='50' height='50' /> 
								<span data-i18n='lorgnette_averages_spinsegg' >Spins per Egg</span>
								: " . round($averages["SpinsPerEgg"] / $resultAmount) . "
							</h4>
						</span>
					</div>
					<div class='col-md-3'>
						<span class='list-group-item bold' style='border: 1px solid white;'>
							<h4 class='list-group-item-heading'>
								<img style='margin-right:5px;' src='static/images/xpstop.png' width='50' height='50' /> 
								<span data-i18n='lorgnette_averages_xps' >XP per Stop</span>
								: " . round($averages["XPS"] / $resultAmount) . "
							</h4>
						</span>
					</div>
					<!-- ### Second Row ###-->
					<div class='col-md-3'>
					</div>
					<div class='col-md-6 mt-3'>
						<span class='list-group-item bold' style='border: 1px solid white;'>
							<div class='list-group-item-heading text-center' style='font-size:1.5em'>
								<img style='margin-right:5px;' src='static/images/duration.png' width='64' height='64' /> 
								<span data-i18n='lorgnette_averages_duration' >Duration</span>
								: " . floor($averages["Duration"] / $resultAmount) . "h " . ((($averages["Duration"] / $resultAmount)*60)%60) . "m
							</div>
						</span>
					</div>
					<div class='col-md-3'>
					</div>
				</div>";
			}
		echo "
		</div>
		
		<div id='no-more-tables'>
			<table id='done-table' class='table table-dark table-striped mb-0' border='1'>
				<thead class='thead-dark'>
					<tr class='text-nowrap'>
						<th data-i18n='lorgnette_table_level' class='level'>Level</th>
						<th data-i18n='lorgnette_table_duration' class='hour'>Duration</th>
						<th data-i18n='lorgnette_table_total_xp' class='xp'>Total XP</th>
						<th data-i18n='lorgnette_table_xp_per_hour' class='xph'>XP/h</th>
						<th data-i18n='lorgnette_table_spins' class='spins'>Spins</th>
						<th data-i18n='lorgnette_table_xp_per_spin' class='xpspins'>XP/Spin</th>
						<th data-i18n='lorgnette_table_spins_per_egg' class='eggspins'>Spins/Egg</th>
						<th data-i18n='lorgnette_table_user' class='username'>User</th>
						<th data-i18n='lorgnette_table_updated' class='updated'>Updated</th>
						<th data-i18n='lorgnette_table_transfer_level30' class='transfer'>Transfer to RDM</th>
					</tr>
				</thead>";
		foreach($result as $row){
			$usernameFull = $row['username'];
			$username = substr($usernameFull, 0, 6) . "...";
			$level = $row['level']; 
			$spins = $row['spins'];
			$device_id = $row['device_id'];
			$route = $row['route'];
			$xp = $row['xp'];
			$hour = floor($row['hour']);
			$xph = round($xp / $row['hour']);
			$avg_xp_stop = round($xp / $spins);
			$minute = ($row['hour']*60)%60;
			$updated = $row['updated'];
			$eggsGot = getEggAmount($level);
			$spinsPerEgg = round((($xp - ($spins * 250)) /250)/$eggsGot);
			
			//Build Table
			echo "
				<tr class='text-nowrap'>
					<td data-title='level'>" . $level . "</td>
					<td data-title='hour'>" . $hour . "h " .  $minute  ."m" ."</td>
					<td data-title='xp'>" . $xp . "</td>
					<td data-title='xph'>" . $xph . "</td>
					<td data-title='spins'>" . $spins . "</td>
					<td data-title='xpspins'>" . $avg_xp_stop . "</td>
					<td data-title='eggspins'>" . $spinsPerEgg . "</td>
					<td data-title='username'>" . $username . "</td>
					<td data-title='updated'>" . $updated . "</td>
					<td  class='text-center' data-title='transfer'><button class='btn btn-secondary' onclick='doSomething(\"" . $usernameFull . "\", this);'>Transfer</button></td>
				</tr>
			";
		}
		echo "
			</table>
		</div>
	</div>";
	// Close connection
	if($sqlType == 'mysql'){
		unset($pdo);
	} else if($sqlType == 'psql'){
		pg_close($pdo);
	}
	
echo "</div>";


function get_status($status, $sqlType){
	if($sqlType == 'mysql'){
		if($status == 0){
			return "<font color='#ff2c1e'>Offline</font>";
		} else if($status == 1){
			return "<font color='lime'>Online</font>";
		}
		else{
		return "Unknown";
		}
	}
	if($sqlType == 'psql'){
		if($status == 'f'){
			return "<font color='#ff2c1e'>Offline</font>";
		} else if($status == 't'){
			return "<font color='lime'>Online</font>";
		}
		else{
		return "Unknown";
		}
	}
	
}
function getEggAmount($level){
$eggs = 0;
if($level >= 9){
	$eggs++;
}
if($level >= 10){
	$eggs++;
}
if($level >= 15){
	$eggs++;
}
if($level >= 20){
	$eggs += 2;
}
if($level >= 20){
	$eggs++;
}
return $eggs;
}
?>

<link rel="stylesheet" href="./static/css/quests.css"/>
<link rel="stylesheet" href="./static/css/lorgnette.css"/>
<script type="text/javascript" src="static/js/tabs.js"></script>
<script type="text/javascript">
  document.getElementById('ongoingContainer').style.display = "block";
</script>
<script>

function doSomething(username, row){
	console.log('Trying to transfer user: ' + username + '...');
	
	if(confirm('Please Confirm to transfer the account \'' + username + '\' to your RDM DB')){
		return $.ajax({
				url: 'accounthandler.php',
				type: 'POST',
				timeout: 300000,
				dataType: 'json',
				data: {
					'action': 'transferAccount',
					'username': username
				},
				error: function (jqXhr, textStatus, errorMessage) {
					alert('ERROR' + errorMessage);
				},
				success: function (data, xhr) {
					alert(data.status)
					console.log(data.details);
					if(data.status == "Success"){
						document.getElementById("done-table").deleteRow(row.parentNode.parentNode.rowIndex);
					}
				}
				
			});
	}
}

function deleteTableNode(){
	console.log('completed. now deleting table node...')
}
</script>
