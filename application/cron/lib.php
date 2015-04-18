<?php 

require 'config.php';
require '/usr/www/aodwebhost/public/tracker/application/uagent.php';

function dbConnect()
{
	global $pdo;
	$conn = '';

	$now = new DateTime();
	$mins = $now->getOffset() / 60;
	$sgn = ($mins < 0 ? -1 : 1);
	$mins = abs($mins);
	$hrs = floor($mins / 60);
	$mins -= $hrs * 60;
	$offset = sprintf('%+d:%02d', $hrs*$sgn, $mins);

	try {
		$pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
		$pdo->exec("SET time_zone='$offset';");
		$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	}

	catch (PDOException $e) {
		if (DEBUG_MODE)
			echo "<div class='alert alert-danger'><i class='fa fa-exclamation-circle'></i><strong>Database connection error</strong>: " . $e->getMessage() . "</div>";
	}

	return true;
}

function convertDivision($division) {

	$division = strtolower($division);
	switch ($division) {

		case "battlefield":
		$id = 2;
		break;

	}
	return $id;
}

function convertStatus($status) {

	$status = (stristr($status, "LOA")) ? "LOA" : $status;

	switch ($status) {

		case "Active":
		$id = 1;
		break;
		case "On Leave":
		case "Missing in Action":
		case "LOA":
		$id = 3;
		break;
		case "Retired":
		$id = 4;
		break;

	}
	return $id;
}

function newActivity($reports, $game, $member_id, $id) {
	global $pdo;
	if (dbConnect()) {
		foreach ($reports as $report) {
			try {
				$hash = hash("sha256", $member_id.$report['date']);
				$sql = "INSERT IGNORE INTO activity (member_id, server, datetime, hash, game_id, map_name, report_id) 
				VALUES ({$member_id}, '{$report['serverName']}', '{$report['date']}', '{$hash}', '{$game}', '{$report['map']}', '{$report['reportId']}')";
				$pdo->prepare($sql)->execute();
			} catch (PDOException $e) {
				echo $e->getMessage();
			}
		}
	}
}



function _doBattlelogIdUpdate() {
	$members = array();
	$battlelog_names = objectToArray(Member::find(array('status_id' => 1, 'battlelog_name !%' => 0, 'battlelog_id' => 0)));
	$countNames = count($battlelog_names);
	echo "Fetched battlelog names. ({$countNames})<br /><br />";
	foreach ($battlelog_names as $row) {
		$battlelog_id = Member::getBattlelogId($row['battlelog_name']);
		if (!$battlelog_id['error']) {
			$sql = "UPDATE member SET battlelog_id = {$battlelog_id['id']} WHERE battlelog_name = '{$row['battlelog_name']}'";
			Flight::aod()->sql($sql)->one();
			echo "Added ID {$battlelog_id['id']} to {$row['battlelog_name']}<br />";
		} else {
			echo "ERROR: {$row['battlelog_name']} - {$battlelog_id['message']}<br />";
		}
	}
	echo "done syncing battlelog ids.";
}

function _doArchUpdate($game) {
	echo(ArchUpdater::run($game));
}


function parse_battlelog_reports($personaId, $game) {

	$reports = download_bl_reports($personaId, $game);
	$arrayReports = array();
	$i = 1;

	if (!is_null(($reports))) {
		foreach ($reports as $report) {
			$unix_date = $report->createdAt;
			$date = DateTime::createFromFormat('U', $unix_date)->format('Y-m-d H:i:s');
			if ( strtotime($date) > strtotime('-90 days') ) {
				$arrayReports[$i]['reportId'] = $report->gameReportId;
				$arrayReports[$i]['serverName'] = $report->name;
				$arrayReports[$i]['map'] = $report->map;
				$arrayReports[$i]['date'] = $date;
				$i++;
			}
		}
	}

	return $arrayReports;
}


function download_bl_reports($personaId, $game) {

	$agent = random_uagent();

	$options = array(
		'http'=>array(
			'method'=>"GET",
			'header'=>"Accept-language: en\r\n" .
			"Cookie: foo=bar\r\n" .
			"User-Agent: {$agent}\r\n"
			)
		);

	$context = stream_context_create($options);

	switch ($game) {
		case 'bf4':
		$url = "http://battlelog.battlefield.com/bf4/warsawbattlereportspopulate/{$personaId}/2048/1/";
		break;
		case 'bfh':
		$url = "http://battlelog.battlefield.com/bfh/warsawbattlereportspopulate/{$personaId}/8192/1/";
	}

	$json = file_get_contents($url, false, $context);
	$data = json_decode($json);

	$reports = $data->data->gameReports;

	return $reports;
}


function getBattlelogId($battlelogName) {
		// check for bf4 entry
	$url = "http://api.bf4stats.com/api/playerInfo?plat=pc&name={$battlelogName}";
	$headers = get_headers($url); 
	if (stripos($headers[0], '40') !== false || stripos($headers[0], '50') !== false) { 
			// check for hardline entry
		$url = "http://api.bfhstats.com/api/playerInfo?plat=pc&name={$battlelogName}";
		$headers = get_headers($url);
		if (stripos($headers[0], '40') !== false || stripos($headers[0], '50') !== false) { 
			$result = array('error' => true, 'message' => 'Player not found, or BF Stats server down.');
		} else {
			$json = file_get_contents($url);
			$data = json_decode($json);
			$personaId = $data->player->id;
			$result = array('error' => false, 'id' => $personaId);
		}
	} else {
		$json = file_get_contents($url);
		$data = json_decode($json);
		$personaId = $data->player->id;
		$result = array('error' => false, 'id' => $personaId);
	}
	return $result;
}