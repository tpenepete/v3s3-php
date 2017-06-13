<?php
namespace v3s3\Controller;

defined('V3S3') or die('access denied');

use finfo;

use v3s3\Model\v3s3;

use v3s3\Exception\v3s3_Exception;

use v3s3\Helper\v3s3_html;
use v3s3\Helper\v3s3_xml;

class v3s3_Controller {
	static function put() {
		global $translator;

		$name = parse_url($_SERVER['REQUEST_URI'])['path'];

		try {
			if (empty($name) || ($name == '/')) {
				throw new v3s3_Exception($translator->translate('V3S3_EXCEPTION_PUT_EMPTY_OBJECT_NAME'), v3s3_Exception::PUT_EMPTY_OBJECT_NAME);
			} else if (strlen($name) > 1024) {
				throw new v3s3_Exception($translator->translate('V3S3_EXCEPTION_OBJECT_NAME_TOO_LONG'), v3s3_Exception::OBJECT_NAME_TOO_LONG);
			}
		} catch(v3s3_Exception $e) {
			header('Content-type: application/json; charset=utf-8');
			echo json_encode(
				[
					'status'=>0,
					'code'=>$e->getCode(),
					'message'=>$e->getMessage()
				]
			);

			return 0;
		}

		$data = file_get_contents('php://input');
		$content_type = (!empty(getallheaders()['Content-type'])?getallheaders()['Content-type']:null);
		$mime_type = (is_null($content_type)?(new finfo(FILEINFO_MIME))->buffer($data):$content_type);
		$row = v3s3::put(
			[
				'ip'=>$_SERVER['REMOTE_ADDR'],
				'name'=>$name,
				'data'=>$data,
				'mime_type'=>$mime_type,
			]
		);

		header('v3s3-object-id: '.$row['id']);
		header('Content-type: application/json; charset=utf-8');
		echo json_encode(
			[
				'status'=>1,
				'message'=>$translator->translate('V3S3_MESSAGE_PUT_OBJECT_ADDED_SUCCESSFULLY')
			]
		);

		return 1;
	}

	static function get() {
		global $translator;

		$name = parse_url($_SERVER['REQUEST_URI'])['path'];

		try {
			if (strlen($name) > 1024) {
				throw new v3s3_Exception($translator->translate('V3S3_EXCEPTION_OBJECT_NAME_TOO_LONG'), v3s3_Exception::OBJECT_NAME_TOO_LONG);
			}
		} catch(v3s3_Exception $e) {
			header('Content-type: application/json; charset=utf-8');
			echo json_encode(
				[
					'status'=>0,
					'code'=>$e->getCode(),
					'message'=>$e->getMessage()
				]
			);

			return 0;
		}

		$input = $_GET;
		unset($input['download']);
		$row = v3s3::get(
			array_replace(
				$input,
				[
					'name'=>$name,
				]
			)
		);

		if(!empty($row['status'])) {
			if(empty($row['mime_type'])) {
				$row['mime_type'] = (new finfo(FILEINFO_MIME))->buffer($row['data']);
			}
			header('v3s3-object-id: ' . $row['id']);
			header('Content-type: ' . $row['mime_type']);
			header('Content-length: ' . strlen($row['data']));
			if(!empty($_GET['download'])) {
				$filename = basename($name);
				header('Content-Disposition: attachment; filename="'.$filename.'"');
			}
			echo $row['data'];
		} else {
			http_response_code(404);
			header('Content-type: application/json; charset=utf-8');
			echo json_encode(
				[
					'status'=>1,
					'results'=>0,
					'message'=>$translator->translate('V3S3_MESSAGE_404')
				]
			);
		}

		return 1;
	}

	static function delete() {
		global $translator;

		$name = parse_url($_SERVER['REQUEST_URI'])['path'];

		try {
			if (empty($name) || ($name == '/')) {
				throw new v3s3_Exception($translator->translate('V3S3_EXCEPTION_DELETE_EMPTY_OBJECT_NAME'), v3s3_Exception::DELETE_EMPTY_OBJECT_NAME);
			} else if (strlen($name) > 1024) {
				throw new v3s3_Exception($translator->translate('V3S3_EXCEPTION_OBJECT_NAME_TOO_LONG'), v3s3_Exception::OBJECT_NAME_TOO_LONG);
			}
		} catch(v3s3_Exception $e) {
			header('Content-type: application/json; charset=utf-8');
			echo json_encode(array('status'=>0, 'code'=>$e->getCode(), 'message'=>$e->getMessage()));

			return 0;
		}

		$input = $_GET;
		$timestamp = time();
		$row = v3s3::api_delete(
			array_replace(
				$input,
				[
					'name'=>$name,
					'ip_deleted_from'=>$_SERVER['REMOTE_ADDR']
				]
			)
		);

		if(empty($row)) {
			http_response_code(404);
			header('Content-type: application/json; charset=utf-8');
			echo json_encode(
				[
					'status'=>1,
					'results'=>0,
					'message'=>$translator->translate('V3S3_MESSAGE_NO_MATCHING_RESOURCES')
				]
			);
		} else {
			header('Content-type: application/json; charset=utf-8');
			echo json_encode(
				[
					'status'=>1,
					'results'=>1,
					'message'=>$translator->translate('V3S3_MESSAGE_DELETE_OBJECT_DELETED_SUCCESSFULLY')
				]
			);
		}

		return 1;
	}

	static function post() {
		global $translator;

		$name = parse_url($_SERVER['REQUEST_URI'])['path'];

		$input = file_get_contents("php://input");
		$parsed_input = (!empty($input)?json_decode($input, true):[]);
		if(!empty($input) && empty($parsed_input)) {
			try {
				throw new v3s3_Exception($translator->translate('v3s3_Translation.V3S3_EXCEPTION_POST_INVALID_REQUEST'), v3s3_Exception::POST_INVALID_REQUEST);
			} catch(v3s3_Exception $e) {
				return json_encode(
					[
						'status'=>0,
						'code'=>$e->getCode(),
						'message'=>$e->getMessage(),
					]
				);
			}
		}

		$attr = (!empty($parsed_input['filter'])?$parsed_input['filter']:[]);
		if(!empty($name) && ($name != '/')) {
			$attr['name'] = $name;
		}

		$rows = v3s3::post(
			$attr
		);


		if(!empty($rows)) {
			foreach ($rows as &$_row) {
				unset($_row['id']);
				unset($_row['timestamp']);
				unset($_row['hash_name']);
				unset($_row['timestamp_deleted']);
				if(empty($_row['mime_type'])) {
					$_row['mime_type'] = (new finfo(FILEINFO_MIME))->buffer($_row['data']).' (determined using PHP finfo)';
				}
				unset($_row['data']);
			}

			$format = ((!empty($parsed_input['format'])&&in_array($parsed_input['format'], ['json', 'xml', 'html']))?strtolower($parsed_input['format']):'json');
			switch($format) {
				case 'xml':
					$rows = v3s3_xml::simple_xml($rows);
					header('Content-type: text/xml; charset=utf-8');
					echo $rows;
					break;
				case 'html':
					$rows = v3s3_html::simple_table($rows);
					header('Content-type: text/html; charset=utf-8');
					echo $rows;
					break;
				case 'json':
				default:
					$rows = json_encode($rows, JSON_PRETTY_PRINT);
					header('Content-type: application/json; charset=utf-8');
					echo $rows;
					break;
			}

		} else {
			header('Content-type: application/json; charset=utf-8');
			echo json_encode(
				[
					'status'=>1,
					'results'=>0,
					'message'=>$translator->translate('V3S3_MESSAGE_NO_MATCHING_RESOURCES')
				]
			);
		}

		return 1;
	}
}