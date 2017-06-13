<?php

defined('V3S3') or die('access denied');

use v3s3\Controller\v3s3_Controller;
use v3s3\Exception\v3s3_Exception;

class router {
	public static function route() {
		global $translator;
		$method = strtolower($_SERVER['REQUEST_METHOD']);

		try {
			switch ($method) {
				case 'put':
					return v3s3_Controller::put($vars);
					break;
				case 'delete':
					return v3s3_Controller::delete($vars);
					break;
				case 'get':
					return v3s3_Controller::get($vars);
					break;
				case 'post':
					return v3s3_Controller::post($vars);
					break;
				default:
					throw new v3s3_Exception($translator->translate('V3S3_EXCEPTION_INVALID_METHOD'), v3s3_Exception::V3S3_EXCEPTION_INVALID_METHOD);
					break;
			}
		} catch(v3s3_Exception $e) {
			header('Content-type: application/json; charset=utf-8');
			echo json_encode(array('status'=>0, 'code'=>$e->getCode(), 'message'=>$e->getMessage()));

			return 0;
		}
	}
}