<?php

class LeaveOfAbsence extends Application {

	public $member_id;
	public $date_end;
	public $reason;
	public $approved;
	public $approved_by;
	public $comment;
	public $game_id;

	static $table = 'loa';
	static $id_field = 'member_id';

	public static function findAll($game_id) {
		return self::find_each(array('game_id' => $game_id, 'approved' => 1));
	}

	public static function count_active($game_id) {
		return count(self::find(array('game_id' => $game_id, 'approved' => 1)));
	}

	public static function count_expired($gid) {
		return count(self::find(array("date_end <" => date('Y-m-d H:i:s'), 'game_id' => $gid)));
	}

	public static function find_expired($gid) {
		return self::find_each(array("date_end <" => date('Y-m-d H:i:s'), 'game_id' => $gid));
	}

	public static function count_pending($gid) {
		return count(self::find(array('game_id' => $gid, 'approved' => 0)));
	}

	public static function find_pending($gid) {
		return self::find_each(array('game_id' => $gid, 'approved' => 0));
	}

	public static function add($member_id, $date, $reason, $comment) {
		$member = Member::profileData($member_id);

		try {

			$sql = "INSERT INTO loa ( member_id, date_end, reason, comment, game_id ) VALUES ( {$member_id}, '{$date}', '{$reason}', '{$comment}', {$member->game_id} )";
			Flight::aod()->sql($sql)->one();
		}

		catch (PDOException $e) {
			if ($e->errorInfo[1] == 1062) {
				return array('success' => false, 'message' => 'Member already has an LOA!');
			} else {
				return array('success' => false, 'message' => $e->getMessage());
			}

		}
		return array('success' => true);
		
	}

	public static function remove($member_id) {
		global $pdo;
		if (dbConnect()) {
			try {
				$query = $pdo->prepare("DELETE FROM loa WHERE member_id = :mid LIMIT 1");
				$query->execute(array(':mid' => $mid));
			}
			catch (PDOException $e) {
				return array('success' => false, 'message' => $e->getMessage());
			}
		} 
		return array('success' => true);

	}

	public static function approve($member_id, $approvingId) {
		try {
			self::modify(array('member_id'=>$member_id, 'approved'=>1, 'approved_by'=>$approvingId));        
			var_dump(Flight::aod()->last_query);die;
		}
		catch (PDOException $e) {
			return array('success' => false, 'message' => $e->getMessage());
		}

		return array('success' => true);
	}

	public static function modify($params) {
		$member = new self();
		foreach ($params as $key=>$value) {
			$member->$key = $value;
		}
		$member->update($params);
	}


}