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
// Get Transfer Amounts
$transferAccounts = get_transferable_amount($sqlType,$pdo);
$transferableAmount = $transferAccounts["transferable"];
$failedAmount = $transferAccounts["failed"];
$cooldownedAmount = $transferAccounts["cooldowned"];
$recordAmount = $transferAccounts["record"];

// Explanation Modal
echo "<div class='modal fade' id='explainStatusModal' tabindex='-1' role='dialog' aria-labelledby='explainStatusModalLabel' aria-hidden='true'>
  <div class='modal-dialog' role='document'>
    <div class='modal-content'>
      <div class='modal-header'>
        <h5 class='modal-title' id='explainStatusModalLabel'>Account Status</h5>
        <button type='button' class='close' data-dismiss='modal' aria-label='Close'>
          <span aria-hidden='true'>&times;</span>
        </button>
      </div>
      <div class='modal-body'>
        <b><span data-i18n='lorgnette_table_explain_1st'>1st Indicator: Account Status</span></b><br>
		<font color='green' data-i18n='lorgnette_table_explain_green'>Green</font>: <span data-i18n='lorgnette_table_explain_status_fine'>The account is fine</span><br>
		<font color='orange' data-i18n='lorgnette_table_explain_yellow'>Yellow</font>: <span data-i18n='lorgnette_table_explain_status_warned'>The account has a warning</span><br>
		<font color='red' data-i18n='lorgnette_table_explain_red'>Red</font>: <span data-i18n='lorgnette_table_explain_status_failed'>The account failed (ban or invalid cred)</span><br>
		<br>
		<b><span data-i18n='lorgnette_table_explain_2nd'>2nd Indicator: Cooldown Status</span></b><br>
		<font color='green' data-i18n='lorgnette_table_explain_green'>Green</font>: <span data-i18n='lorgnette_table_explain_cd_fine'>The account logged out more than 2h before now</span><br>
		<font color='red' data-i18n='lorgnette_table_explain_red'>Red</font>: <span data-i18n='lorgnette_table_explain_cd_oncd'>The account logged out less than 2h before now</span><br>
		<br>
		<b><span data-i18n='lorgnette_table_explain_3rd'>3rd Indicator(optional): Favorite Status</span></b><br>
		<span data-i18n='lorgnette_table_explain_favo'>If a star is in the status, it indicates that this account is marked as a favorite/record account and cannot be transfered with the masstransfer tool</span>
	  </div>
    </div>
  </div>
</div>";
//Mass Transfer Modal

echo "<div class='modal fade' id='massTransferModal' tabindex='-1' role='dialog' aria-labelledby='massTransferModalLabel' aria-hidden='true'>
  <div class='modal-dialog' role='document'>
    <div class='modal-content'>
      <div class='modal-header'>
        <h5 class='modal-title' id='massTransferModalLabel'><span data-i18n='lorgnette_masstransfer_header'>Mass Account Transfer</span></h5>
        <button type='button' class='close' data-dismiss='modal' aria-label='Close'>
          <span aria-hidden='true'>&times;</span>
        </button>
      </div>
      <div class='modal-body' style='font-size:14px;'>
		<p>
			<span data-i18n='lorgnette_masstransfer_description1'>This tool will transfer accounts</span> <b data-i18n='lorgnette_masstransfer_description2'>from your Lorgnette-DB to your RDM-DB</b><br>
			<span data-i18n='lorgnette_masstransfer_description3'>The below displayed accounts are not warned,banned and already rested 2 hours</span><br>
			<span data-i18n='lorgnette_masstransfer_description4'>To keep it easy, you can only transfer those accounts</span> <br>
			<b data-i18n='lorgnette_masstransfer_description5'>Accounts with a warning, ban or still in cooldown are not mass transferable</b><br>
		</p>
        <div class='input-group mb-3'>
          <div class='input-group-prepend'>
            <span class='input-group-text' style='background-color:#404040;color:white;' data-i18n='lorgnette_masstransfer_transfer'>Transfer</span>
          </div>
          <input id='transferAccountAmount' style='background-color:#2a2a2a;color:white;' name='transferAccountAmount' type='text' class='form-control' aria-label='Account Amount'>
          <div class='input-group-append'>
            <span class='input-group-text' style='background-color:#404040;color:white;'>Account(s)</span>
          </div>
        </div>
		<div>
			<center>
			<b><font color='green' data-i18n='lorgnette_masstransfer_transferable'>Currently Transferable</font></b>:  $transferableAmount Accounts<br>
			<hr class='hr-white hr-smaller' />
			<b><font color='cornflowerblue' data-i18n='lorgnette_masstransfer_cooldowned'>Cooldown Highlevel</font></b>: $cooldownedAmount Accounts<br>
			<b><font color='firebrick' data-i18n='lorgnette_masstransfer_failed'>Failed Highlevel</font></b>: $failedAmount Accounts <br>
			<b><font color='gold' data-i18n='lorgnette_masstransfer_record'>Record Highlevel</font></b>: $recordAmount Accounts <br>
			</center>
		</div>

		<div class='mt-4' style='font-size:12px;'>
			<span data-i18n='lorgnette_masstransfer_record_description'>If you want to keep an account and ignore it from masstransfer, enter 'record' in the reason-column of the account. It will be ignored from the transferable accounts</span>
		</div>
      </div>
	  
      <div class='modal-footer'>
        <button type='button' class='btn btn-dark' style='border: 1px solid white;' onclick='massTransfer($transferableAmount)' data-i18n='lorgnette_masstransfer_transfer'>Transfer</button>
		<button type='button' class='btn btn-secondary' style='border: 1px solid white;' data-dismiss='modal' data-i18n='lorgnette_masstransfer_close'>Close</button>
	  </div>
    </div>
  </div>
</div>";

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
		<div class='col-md-3 mt-3'>
		</div>
		<div class='col-md-6 mt-3'>
			<span class='list-group-item bold' style='border: 1px solid white;'>
				<h4 class='list-group-item-heading'>
					<center>
					<button type='button' class='btn btn-secondary masstransfer' data-toggle='modal' data-target='#massTransferModal'>Mass Transfer Accounts</button>
					</center>
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
			$instance = $row['instance'];
			$device_id = $row['device_id'];
			$route = $row['route'];
			$updated = $row['updated'];
			$hour = floor($row['hour']);
			$minute = ($row['hour']*60)%60;
			$status = get_status($row['online'], $sqlType);
			$spinsPerHour = 0;
			if($spins > 0 && $row['hour'] > 0){
				$spinsPerHour = round($spins / $row['hour']);
			}
			
			$xp = $row['xp'];
			$xph = 0;
			if($xp > 0 && $row['hour'] > 0){
				$xph = round(($xp / $row['hour'])/1000,1);
			}
			
			$avg_xp_stop = 0;
			if($xp > 0 && $spins > 0){
				$avg_xp_stop = round($xp / $spins);
			}
			
			//Estimates
			$xpNeeded = 2000000;
			$estXpPerHour = 0;
			if($xp > 0 && $row['hour'] > 0){
				$estXpPerHour = ($xp / $row['hour']);
			}
			$estFinish = 0;
			if(($xpNeeded - $xp) > 0 && $estXpPerHour > 0){
				$estFinish = ($xpNeeded - $xp)/($estXpPerHour);
			}
			//Estimated Finish
			$estFinishHours = floor($estFinish);
			$estFinishMinutes = ($estFinish*60)%60;
			$estFinishString = $estFinishHours . "h " .  $estFinishMinutes  ."m";
			
			//Estimated Time passed
			$estTime = $row['hour'] + $estFinish;
			$estTimeHours = floor($estTime);
			$estTimeMinutes = ($estTime*60)%60;
			$estTimeString = $estTimeHours . "h " . $estTimeMinutes . "m";
			
			//Edge Case
			if ($instance == "Test"){
				$estFinishString = "None";
				$estTimeString = "None";
			}
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
					<td data-title='estfinish'>" . $estFinishString ."</td>
					<td data-title='esttime'>" . $estTimeString . "</td>
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
			if(is_array($result) && sizeOf($result) > 1){
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
			if (is_array($result) || is_object($result)){
					$averages["XPH"] = 0;
					$averages["Duration"] = 0;
					$averages["Spins"] = 0;
					$averages["SpinsPerEgg"] = 0;
					$averages["XPS"] = 0;
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
						<th class='status'>Status <button type='button' class='btn explain' data-toggle='modal' data-target='#explainStatusModal'>?</button></th>
					</tr>
				</thead>";
		if (is_array($result) || is_object($result)){
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
				$accountStatus = getAccountStatus($row['failed'], $row['logout']);
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
						<td data-title='status'><center>" . $accountStatus . "</center></td>
					</tr>
				";
			}
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

function getAccountStatus($failed, $logout){
	if($failed == "banned" || $failed == "invalid_cred"){
		$statusFailed = "<img src='./static/images/offline.png' width='24' height='auto' style='margin-right:5px;' />";
	} else if($failed == "warning"){
		$statusFailed = "<img src='./static/images/unstable.png' width='24' height='auto' style='margin-right:5px;' />";
	} else{
		$statusFailed = "<img src='./static/images/online.png' width='24' height='auto' style='margin-right:5px;' />";
	}

	if ($failed == "record") {
		$statusFavorite = "<img src='./static/images/star.png' width='24' height='auto' style='margin-left:10px;' />";
	} else{
		$statusFavorite = "";
	}
	
	if (strpos($failed,";") !== false){
		// Detect Misc entrys
		switch($failed){
			case ";red":
				$statusCategory = "<font color='red' style='width:24px;height:auto;margin-left:10px;vertical-align: middle;'>■</font>";
				break;
			case ";green":
				$statusCategory = "<font color='limegreen' style='width:24px;height:auto;margin-left:10px;vertical-align: middle;'>■</font>";
				break;
			case ";blue":
				$statusCategory = "<font color='cornflowerblue' style='width:24px;height:auto;margin-left:10px;vertical-align: middle;'>■</font>";
				break;
			case ";yellow":
				$statusCategory = "<font color='orange' style='width:24px;height:auto;margin-left:10px;vertical-align: middle;'>■</font>";
				break;
		}
		
	} else{
		$statusCategory = "";
	}
	
	$now = new DateTime();
	if($logout > strtotime("-2 hour")){
		$statusCooldown = "<img src='./static/images/offline.png' width='24' height='auto' style='margin-left:5px;' />";
	} else if($logout < strtotime("-2 hour")){
		$statusCooldown = "<img src='./static/images/online.png' width='24' height='auto' style='margin-left:5px;' />";
	}
	return $statusFailed . $statusCooldown . $statusFavorite . $statusCategory;
}

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
function massTransfer(amount){
	var transferAmount = $('#transferAccountAmount').val();
	console.log('Trying to transfer ' + transferAmount + ' accounts');
	if (transferAmount > 0 && transferAmount <= amount){
		//Continue
		if(confirm('Confirm to transfer ' + transferAmount + ' account(s) to your RDM DB')){
			return $.ajax({
					url: 'accounthandler.php',
					type: 'POST',
					timeout: 300000,
					dataType: 'json',
					data: {
						'action': 'massTransferAccounts',
						'amount': transferAmount
					},
					error: function (jqXhr, textStatus, errorMessage) {
						alert('ERROR' + errorMessage);
					},
					success: function (data, xhr) {
						console.log(data.details);
						if(data.status == "Success"){
						//Refresh Page
						alert('Successfully transfered. The Page will refresh now.');
						location.reload();
						} else{
						alert(data.status);
						}
					}
					
				});
		}
	} else if(transferAmount <= 0 || transferAmount > amount){
		console.log('Wrong Account Amount');
		alert('You entered a Wrong amount. Please adjust your transfer amount!');
	}
}

function deleteTableNode(){
	console.log('completed. now deleting table node...')
}
</script>
