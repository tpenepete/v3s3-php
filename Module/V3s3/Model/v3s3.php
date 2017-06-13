<?php
namespace v3s3\Model;

defined('V3S3') or die('access denied');

class v3s3 {
	static function put(Array $attr) {
		global $config, $db;

		$table = $config['v3s3']['table'];

		$attr['timestamp'] = (isset($attr['timestamp'])?$attr['timestamp']:time());
		$attr['date_time'] = date('Y-m-d H:i:s O', $attr['timestamp']);
		if(isset($attr['name'])) {
			$attr['hash_name'] = sha1($attr['name']);
		} else {
			unset($attr['hash_name']);
		}
		$attr['status'] = (isset($attr['status'])?$attr['status']:1);
		unset($attr['id']);

		$row = array_replace(
			$attr,
			[
				'id'=>$db->insertData($table, $db->get_columns($table), $attr)
			]
		);


		return $row;
	}

	static function get(Array $attr) {
		global $config, $db;

		$table = $config['v3s3']['table'];

		if(isset($attr['name'])) {
			$attr['hash_name'] = sha1($attr['name']);
		} else {
			unset($attr['hash_name']);
		}
		unset($attr['name']);

		$row = $db->selectData($table, '*', $db->get_columns($table), $attr, '`id` DESC', '1');

		$rows_count = count($row);
		if(empty($rows_count)) {
			return false;
		}

		return reset($row);
	}

	static function api_delete(Array $attr) {
		global $config, $db;

		$table = $config['v3s3']['table'];

		$attr['timestamp_deleted'] = (isset($attr['timestamp_deleted'])?$attr['timestamp_deleted']:time());
		$attr['date_time_deleted'] = date('Y-m-d H:i:s O', $attr['timestamp_deleted']);
		if(isset($attr['name'])) {
			$attr['hash_name'] = sha1($attr['name']);
		} else {
			unset($attr['hash_name']);
		}
		$attr['status'] = (isset($attr['status'])?$attr['status']:0);
		unset($attr['name']);

		$where = $attr;
		unset($where['status']);
		unset($where['timestamp_deleted']);
		unset($where['date_time_deleted']);
		unset($where['ip_deleted_from']);
		$row = $db->selectData($table, '*', $db->get_columns($table), $where, '`id` DESC', '1');

		$rows_count = count($row);
		if(empty($rows_count)) {
			return false;
		}

		$row = array_replace(reset($row), $attr);
		$db->updateData($table, $db->get_columns($table), $attr, $db->get_columns($table), ['id'=>$row['id']]);

		return $row;
	}

	static function post(Array $attr) {
		global $config, $db;

		$table = $config['v3s3']['table'];

		if(isset($attr['name'])) {
			$attr['hash_name'] = sha1($attr['name']);
		} else {
			unset($attr['hash_name']);
		}
		unset($attr['name']);

		$rows = $db->selectData($table, '*', $db->get_columns($table), $attr);

		return (!empty($rows)?$rows:[]);
	}
}