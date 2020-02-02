<?php
include './config.php';
include './static/data/forms_' . $config['ui']['locale'] . '.php';
include './static/data/pokedex_' . $config['ui']['locale'] . '.php';
include './static/data/items_' . $config['ui']['locale'] . '.php';


// Establish connection to database
$pokeDatas = [];
$itemDatas = [];
$dustDatas = [];
$quests_amount_total = 0;
$quests_amount_pokemon = 0;
$quests_amount_items = 0;
$quests_amount_stardust = 0;

global $config;
$db = new DbConnector($config['db']);
$pdo = $db->getConnection();
//Quest Amount Data
try {
	$sql = "
	SELECT 
	COUNT(*) as total,
	SUM(quest_reward_type = 7) as pokeQuests,
	SUM(quest_reward_type = 2) as itemQuests,
	SUM(quest_reward_type = 3) as dustQuests
	from pokestop
	WHERE
	quest_rewards IS NOT NULL;
	";
	$result = $pdo->query($sql);
	if ($result->rowCount() > 0) {
		while ($row = $result->fetch()) {
			$quests_amount_total += $row["total"];
			$quests_amount_pokemon += $row["pokeQuests"];
			$quests_amount_items += $row["itemQuests"];
			$quests_amount_stardust += $row["dustQuests"];
		}
		// Free result set
		unset($result);
	}
} catch (PDOException $e) {
	die("ERROR: Could not able to execute $sql. " . $e->getMessage());
}
//Pokemon Data
try {
	$sql = "
	SELECT 
	quest_pokemon_id as pokeId,
	json_extract(json_extract(`quest_rewards`,'$[*].info.form_id'),'$[0]') AS pokeForm, 
	COUNT(*) as count
	FROM pokestop
	WHERE quest_pokemon_id is not null
	GROUP BY quest_pokemon_id,pokeForm
	ORDER BY pokeId asc;
	";
	$result = $pdo->query($sql);
	if ($result->rowCount() > 0) {
		while ($row = $result->fetch()) {
			$pokeDatas[$row['pokeId']][$row['pokeForm']]["id"] = $row['pokeId'];
			$pokeDatas[$row['pokeId']][$row['pokeForm']]["form"] = $row['pokeForm'];
			$pokeDatas[$row['pokeId']][$row['pokeForm']]["amount"] = $pokeDatas[$row['pokeId']][$row['pokeForm']]["amount"] + $row['count'];
		}
		// Free result set
		unset($result);
	}
	
} catch (PDOException $e) {
	die("ERROR: Could not able to execute $sql. " . $e->getMessage());
}
//Item Data
try {
	$sql = "
	SELECT 
	quest_item_id as itemId,
	COUNT(*) as count
	FROM pokestop
	WHERE quest_item_id is not null
	GROUP BY quest_item_id
	ORDER BY itemId asc;
	";
	$result = $pdo->query($sql);
	if ($result->rowCount() > 0) {
		while ($row = $result->fetch()) {
			$itemDatas[$row['itemId']]['id'] = $row['itemId'];
			$itemDatas[$row['itemId']]['amount'] += $row['count'];
		}
		// Free result set
		unset($result);
	}
	
} catch (PDOException $e) {
	die("ERROR: Could not able to execute $sql. " . $e->getMessage());
}
//Stardust Data
try {
	$sql = "
	SELECT 
	quest_reward_type,
	json_extract(json_extract(`quest_rewards`,'$[*].info.amount'),'$[0]') AS amount, 
	COUNT(*) as count
	FROM pokestop
	WHERE quest_reward_type = 3
    GROUP BY amount
	ORDER BY amount asc;
	";
	$result = $pdo->query($sql);
	if ($result->rowCount() > 0) {
		while ($row = $result->fetch()) {
			$dustDatas[$row['amount']]['rewardAmount'] = $row['amount'];
			$dustDatas[$row['amount']]['questAmount'] += $row['count'];
		}
		// Free result set
		unset($result);
	}
	
} catch (PDOException $e) {
	die("ERROR: Could not able to execute $sql. " . $e->getMessage());
}
// Close connection
unset($pdo);

// Write all Data
echo 
"<div style='max-width:1440px;margin: 0 auto !important;float: none !important;'>
	<div class='card text-center my-1 m-6'>
		<div class='card-header heading dark text-light' style='font-size:30px;'>
			<span data-i18n='overview' >Overview</span>
			
		</div>
	</div>
	<div class='row mb-4'>

		<div class='col-md-3'>
			<span class='list-group-item bold'>
				<h4 class='list-group-item-heading'>
					<img src='static/images/pokestop.png' width='50' height='50' /> 
					<span data-i18n='quests_total_amount' >Total Quests</span>
					: " . $quests_amount_total . "
				</h4>
			</span>
		</div>
		<div class='col-md-3'>
			<span class='list-group-item bold'>
				<h4 class='list-group-item-heading'>
					<img src='static/images/pokemon_icon.png' width='50' height='50' /> 
					<span data-i18n='quests_monster_amount' >Mon Quests</span>
					: " . $quests_amount_pokemon . "
				</h4>
			</span>
		</div>
		<div class='col-md-3'>
			<span class='list-group-item bold'>
				<h4 class='list-group-item-heading'>
					<img src='static/images/pokeball.png' width='50' height='50' /> 
					<span data-i18n='quests_item_amount' >Item Quests</span>
					: " . $quests_amount_items . "
					</h4>
			</span>
		</div>
		<div class='col-md-3'>
			<span class='list-group-item bold'>
				<h4 class='list-group-item-heading'>
					<img src='static/images/stardust.png' width='50' height='50' /> 
					<span data-i18n='quests_stardust_amount' >Stardust Quests</span>
					: " . $quests_amount_stardust . "
					</h4>
			</span>
		</div>

	</div>";
	echo "
	<div class='tab text-center m-2' style='font-size:20px;'>
			<button class='tablinks heading  active' onclick='switchContainer(event,\"pokemonContainer\")'><b><span data-i18n='quests_button_pokemon' >Pokemon</span></b></button>
			<button class='tablinks heading' onclick='switchContainer(event,\"itemContainer\")'><b><span data-i18n='quests_button_items' >Items</span></b></button>
			<button class='tablinks heading' onclick='switchContainer(event,\"stardustContainer\")'><b><span data-i18n='quests_button_stardust' >Stardust</span></b></button>
	</div>";
	
	//Pokemon Quest Statistics
	echo "
	<div id='pokemonContainer' class='tabcontent active'>
		<div class='card text-center m-6'>
			<div class='card-header heading dark text-light' style='font-size:30px;'>
				<span data-i18n='quests_pokemon_statistics' >Pokemon Quest Statistics</span>
			</div>
		</div>
		
		<div id='no-more-tables'>
			<table id='quest-table' class='table table-dark table-striped' border='1'>
				<thead class='thead-dark'>
					<tr class='text-nowrap'>
						<th class='pokemonID'>#</th>
						<th data-i18n='table_icon' class='icon'>Icon</th>
						<th data-i18n='table_pokemon' class='pokemon'>Pokemon</th>
						<th data-i18n='table_amount' class='amount'>Anzahl</th>
						<th data-i18n='table_rate_total' class='rate-t'>Rate (Total)</th>
						<th data-i18n='table_rate_mon_quest' class='rate-m'>Rate (Mon-Q)</th>
						<th data-i18n='table_form' class='form'>(Form)</th>
					</tr>
				</thead>";
				foreach ($pokeDatas as $data){
					foreach ($data as $mon){
						$pokemonID = $mon["id"];
						$pokemon = $pokedex[$pokemonID];					
						$form = $mon["form"];
						$amount = $mon["amount"];
						$rateTotal = round((($mon["amount"] / $quests_amount_total)*100),3) . '% (1:' . round((($quests_amount_pokemon/$mon["amount"]))) . ')';
						$rateMons = round((($mon["amount"] / $quests_amount_pokemon)*100),3) . '%';
						$icon = get_quest_icon('pokemon',$pokemonID,$form);
						$formStr = $forms[$mon["form"]] . ' (' . $mon["form"] . ')';
						if($form == 0){
							$form = '';
							$formStr = '';
						}
							echo "<tr class='text-nowrap'>";
								echo "<td data-title='pokemonID'>" . $pokemonID . "</td>";
								echo "<td data-title='icon'><img src='$icon' width='50' heigth='50' /></td>";
								echo "<td data-title='pokemon'>" . $pokemon . "</td>";
								echo "<td data-title='amount'>" . $amount . "</td>";
								echo "<td data-title='rate-t'>" .$rateTotal . "</td>";
								echo "<td data-title='rate-m'>" .$rateMons . "</td>";
								echo "<td data-title='form'>" . $formStr . "</td>";
							echo "</tr>";
					}
				}
			echo "
			</table>
		</div>
	</div>";
	
	//Item Statistics
	echo "
	<div id='itemContainer' class='tabcontent'>
		<div class='card text-center m-6'>
			<div class='card-header heading dark text-light' style='font-size:30px;'>
				<span data-i18n='quests_item_statistics' >Item Quest Statistics</span>
			</div>
		</div>
		
		<div id='no-more-tables'>
			<table id='quest-table-items' class='table table-dark table-striped' border='1'>
				<thead class='thead-dark'>
					<tr class='text-nowrap'>
						<th class='itemID'>#</th>
						<th data-i18n='table_icon' class='icon'>Icon</th>
						<th data-i18n='table_item' class='item'>Item</th>
						<th data-i18n='table_amount' class='amount'>Anzahl</th>
						<th data-i18n='table_rate_total' class='rate-t'>Rate (Total)</th>
						<th data-i18n='table_rate_item_quest' class='rate-m'>Rate (Item-Q)</th>
					</tr>
				</thead>";
				foreach ($itemDatas as $data){
					$itemID = $data["id"];
					$item = $itemdex[$itemID];
					$amount = $data["amount"];
					$rateTotal = round((($data["amount"] / $quests_amount_total)*100),3) . '% (1:' . round((($quests_amount_pokemon/$data["amount"]))) . ')';
					$rateMons = round((($data["amount"] / $quests_amount_items)*100),3) . '%';
					$icon = get_quest_icon('item',$itemID);
						echo "<tr class='text-nowrap'>";
							echo "<td data-title='pokemonID'>" . $itemID . "</td>";
							echo "<td data-title='icon'><img src='$icon' width='50' heigth='50' /></td>";
							echo "<td data-title='pokemon'>" . $item . "</td>";
							echo "<td data-title='amount'>" . $amount . "</td>";
							echo "<td data-title='rate-t'>" .$rateTotal . "</td>";
							echo "<td data-title='rate-m'>" .$rateMons . "</td>";
						echo "</tr>";
				}
			echo "
			</table>
		</div>
	</div>";
	

	
	//Stardust Statistics
	echo "
	<div id='stardustContainer' class='tabcontent'>
		<div class='card text-center m-6'>
			<div class='card-header heading dark text-light' style='font-size:30px;'>
				<span data-i18n='quests_stardust_statistics' >Stardust Quest Statistics</span>
			</div>
		</div>
	
		<div id='no-more-tables'>
			<table id='quest-table-items' class='table table-dark table-striped' border='1'>
				<thead class='thead-dark'>
					<tr class='text-nowrap'>
						<th data-i18n='table_icon' class='icon'>Icon</th>
						<th data-i18n='table_item' class='name'>Item</th>
						<th data-i18n='table_amount' class='amount'>Anzahl</th>
						<th data-i18n='table_rate_total' class='rate-t'>Rate (Total)</th>
						<th data-i18n='table_rate_item_stardust' class='rate-d'>Rate (Item-D)</th>
					</tr>
				</thead>";
				foreach ($dustDatas as $data){
					$rewardAmount = $data["rewardAmount"];
					if($config['ui']['locale'] == 'de'){
						$name = 'Sternenstaub';
					} else{
						$name = 'Stardust';
					}
					$amount = $data["questAmount"];
					$rateTotal = round((($amount / $quests_amount_total)*100),3) . '% (1:' . round((($quests_amount_pokemon/$amount))) . ')';
					$rateDust = round((($amount / $quests_amount_stardust)*100),3) . '%';
					$icon = get_quest_icon('stardust');
						echo "<tr class='text-nowrap'>";
							echo "<td data-title='icon'><img src='$icon' width='50' heigth='50' /></td>";
							echo "<td data-title='name'>" . $name . " (x" . $rewardAmount . ")</td>";
							echo "<td data-title='amount'>" . $amount . "</td>";
							echo "<td data-title='rate-t'>" .$rateTotal . "</td>";
							echo "<td data-title='rate-d'>" .$rateDust . "</td>";
						echo "</tr>";
				}
			echo "
			</table>
		</div>
	</div>";
	
echo "
</div>";

function get_quest_reward($rewards) {
	global $pokedex;
	global $itemdex;
    $reward = $rewards[0];
    switch ($reward->type) {
        case 5: //AvatarClothing
            return "Avatar Clothing";
        case 4: //Candy
            return sprintf("%s Rare Candy", $reward->info->amount);
        case 1: //Experience
            return sprintf("%s XP", $reward->info->amount);
        case 2: //Item
            return sprintf("%s %s", $reward->info->amount, $itemdex[$reward->info->item_id]);
        case 7: //PokemonEncounter
            return $pokedex[$reward->info->pokemon_id];
        case 6: //Quest
            return "Quest";
        case 3: //Stardust
            return sprintf("%s Sternenstaub", $reward->info->amount);
        default:
            return "Unknown";
    }
}
function get_quest_icon($object,$number = 0,$form = 0) {
    global $config;
    $icon_inde= 0;
    switch ($object) {
        case 'item'://Item
            $icon_index= $number;
			break;
        case 'pokemon'://Pokemon
			if($form > 0){
				$image = sprintf($config['urls']['images']['pokemon'], $number);
				$mon = str_replace('_00', '_' . $form, $image);
			} else{
				$mon = sprintf($config['urls']['images']['pokemon'], $number);
			}
            return $mon;
        case 'stardust'://Stardust
            $icon_index= -1;
            break;
        default: //Unset/Unknown
            break;
    }
    return "./static/images/quests/$icon_index.png";
}
?>

<link rel="stylesheet" href="./static/css/footerfix.css"/>
<link rel="stylesheet" href="./static/css/quests.css"/>
<script type="text/javascript" src="static/js/tabs.js"></script>
<script type="text/javascript">
  document.getElementById('pokemonContainer').style.display = "block";
</script>
