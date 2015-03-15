<?php

class Platoon extends Application {

	public $id;
	public $number;
	public $name;
	public $game_id;
	public $leader_id;

	static $id_field = "id";
	static $table = "platoon";

	public static function find_all($gid) {
		$sql = "SELECT platoon.id, platoon.number, platoon.name, platoon.leader_id, member.forum_name, rank.abbr FROM platoon LEFT JOIN member on platoon.leader_id = member.member_id LEFT JOIN rank on member.rank_id = rank.id WHERE platoon.game_id = {$gid} ORDER BY number";
		$params = Flight::aod()->sql($sql)->many();
		return arrayToObject($params);
	}

	public static function findById($id) {
		$sql = "SELECT platoon.id, platoon.number, platoon.name, platoon.leader_id, member.forum_name, rank.abbr FROM platoon LEFT JOIN member on platoon.leader_id = member.member_id LEFT JOIN rank on member.rank_id = rank.id WHERE platoon.id = {$id}";
		$params = Flight::aod()->sql($sql)->one();
		return arrayToObject($params);
	}

	public static function Leader($leader_id) {
		$params = Member::findById($leader_id);
		return arrayToObject($params);
	}

	public static function SquadLeaders($pid, $order_by_rank = false) {
		$sql = "SELECT member.id, last_activity, rank.abbr, member_id, forum_name, platoon.name, member.battlelog_name FROM member LEFT JOIN platoon ON platoon.id = member.platoon_id LEFT JOIN rank ON rank.id = member.rank_id WHERE position_id = 5 AND platoon_id = {$pid}";

		if ($order_by_rank) {
			$sql .= " ORDER BY member.rank_id DESC, member.forum_name ASC ";
		} else {
			$sql .= "  ORDER BY platoon.id, forum_name";
		}

		$params = Flight::aod()->sql($sql)->many();
		return arrayToObject($params);

	}

	public static function GeneralPop($pid, $order_by_rank = false) {
		$sql = "SELECT member.id, member.forum_name, member.member_id, member.last_activity, member.battlelog_name, member.bf4db_id, member.rank_id, rank.abbr as rank FROM `member` LEFT JOIN `rank` on member.rank_id = rank.id WHERE member.position_id = 7 AND (status_id = 1 OR status_id = 999) AND platoon_id = {$pid}";

		if ($order_by_rank) {
			$sql .= " ORDER BY member.rank_id DESC, member.join_date ASC ";
		} else {
			$sql .= " ORDER BY member.last_activity ASC ";
		}

		$params = Flight::aod()->sql($sql)->many();
		return arrayToObject($params);
	}

	public static function countSquadLeaders($pid) {
		$sql = "SELECT count(*) as count FROM member WHERE position_id = 5 AND platoon_id = {$pid}";
		$params = Flight::aod()->sql($sql)->one();
		return $params['count'];
	}

	public static function countSquadMembers($pid) {
		$sql = "SELECT count(*) as count FROM member WHERE position_id = 6 AND platoon_id = {$pid}";
		$params = Flight::aod()->sql($sql)->one();
		return $params['count'];
	}

	public static function countGeneralPop($pid) {
		$sql = "SELECT count(*) as count FROM member WHERE member.position_id = 7 AND (status_id = 1 OR status_id = 999) AND platoon_id = {$pid}";
		$params = Flight::aod()->sql($sql)->one();
		return $params['count'];
	}

	public static function countPlatoon($pid) {
		$genPopCount = self::countGeneralPop($pid);
		$squadLeaderCount = self::countSquadLeaders($pid);
		$squadMemberCount = self::countSquadMembers($pid);
		$total = $genPopCount + $squadLeaderCount + $squadMemberCount + 1;
		return $total;
	}



}
