<?php
/*
This script is a crucial component of BlogVault, MalCare, and WP Remote,
which work together to automatically protect your site against crashes, hacks, and other issues.

Things to know:

* The script runs in the background and won't have any impact on site performance or user experience.
* It plays an essential role in our backup service, ensuring the best protection for your site.
* The script is safe, secure, and won't interfere with other plugins or scripts.

Learn more about how this script helps recover crashed
WordPress sites by visiting our website at https://blogvault.net/recover-crashed-wordpress-site/.
*/

class BVGenericCallbackBase {
	public function objectToArray($obj) {
		return json_decode(json_encode($obj), true);
	}

	public function base64Encode($data, $chunk_size) {
		if ($chunk_size) {
			$out = "";
			$len = strlen($data);
			for ($i = 0; $i < $len; $i += $chunk_size) {
				$out .= base64_encode(substr($data, $i, $chunk_size));
			}
		} else {
			$out = base64_encode($data);
		}
		return $out;
	}
}

class BVGenericCallbackResponse extends BVGenericCallbackBase {
	public $status;
	public $bvb64cksize;

	public function __construct($bvb64cksize) {
		$this->status = array("blogvault" => "response");
		$this->bvb64cksize = $bvb64cksize;
	}

	public function addStatus($key, $value) {
		$this->status[$key] = $value;
	}

	public function addArrayToStatus($key, $value) {
		if (!isset($this->status[$key])) {
			$this->status[$key] = array();
		}
		$this->status[$key][] = $value;
	}

	public function terminate($resp = array()) {
		$resp = array_merge($this->status, $resp);
		$resp["signature"] = "Blogvault API";
		$response = "bvbvbvbvbv".serialize($resp)."bvbvbvbvbv";
		$response = "bvb64bvb64".$this->base64Encode($response, $this->bvb64cksize)."bvb64bvb64";
		die($response);

		exit;
	}
}

class BVGenericCallbackRequest {
	public $params;
	public $method;
	public $wing;
	public $is_afterload;
	public $is_admin_ajax;
	public $is_debug;
	public $account;
	public $calculated_mac;
	public $sig;
	public $time;
	public $version;
	public $bvb64stream;
	public $bvb64cksize;
	public $checksum;

	public function __construct($account, $in_params) {
		$this->params = array();
		$this->account = $account;
		$this->wing = $in_params['wing'];
		$this->method = $in_params['bvMethod'];
		$this->is_afterload = array_key_exists('afterload', $in_params);
		$this->is_admin_ajax = array_key_exists('adajx', $in_params);
		$this->is_debug = array_key_exists('bvdbg', $in_params);
		$this->sig = $in_params['sig'];
		$this->time = intval($in_params['bvTime']);
		$this->version = $in_params['bvVersion'];
		$this->bvb64stream = isset($in_params['bvb64stream']);
		$this->bvb64cksize = array_key_exists('bvb64cksize', $in_params) ? intval($in_params['bvb64cksize']) : null;
		$this->checksum = array_key_exists('checksum', $in_params) ? $in_params['checksum'] : false;
	}

	public function isAPICall() {
		return array_key_exists('apicall', $this->params);
	}

	public function info() {
		$info = array(
			"requestedsig" => $this->sig,
			"requestedtime" => $this->time,
			"requestedversion" => $this->version
		);
		if ($this->is_debug) {
			$info["inreq"] = $this->params;
		}
		if ($this->is_admin_ajax) {
			$info["adajx"] = true;
		}
		if ($this->is_afterload) {
			$info["afterload"] = true;
		}
		if ($this->calculated_mac) {
			$info["calculated_mac"] = $this->calculated_mac;
		}
		return $info;
	}

	public function processParams($in_params) {
		$params = array();

		if (array_key_exists('obend', $in_params) && function_exists('ob_end_clean'))
			@ob_end_clean();

		if (array_key_exists('op_reset', $in_params) && function_exists('output_reset_rewrite_vars'))
			@output_reset_rewrite_vars();

		if (array_key_exists('concat', $in_params)) {
			foreach ($in_params['concat'] as $key) {
				$concated = '';
				$count = intval($in_params[$key]);
				for ($i = 1; $i <= $count; $i++) {
					$concated .= $in_params[$key."_bv_".$i];
				}
				$in_params[$key] = $concated;
			}
		}

		if (array_key_exists('bvprms', $in_params) && isset($in_params['bvprms']) &&
			array_key_exists('bvprmsmac', $in_params) && isset($in_params['bvprmsmac'])) {
			$digest_algo = 'SHA512';
			$sent_mac = BVGenericAccount::sanitizeKey($in_params['bvprmsmac']);

			if (array_key_exists('bvprmshshalgo', $in_params) && isset($in_params['bvprmshshalgo'])) {
				$digest_algo = $in_params['bvprmshshalgo'];
			}

			$calculated_mac = hash_hmac($digest_algo, $in_params['bvprms'], $this->account->secret);
			$this->calculated_mac = substr($calculated_mac, 0, 6);

			if ($this->account->compare_mac($sent_mac, $calculated_mac) === true) {

				if (array_key_exists('b64', $in_params)) {
					foreach ($in_params['b64'] as $key) {
						if (is_array($in_params[$key])) {
							$in_params[$key] = array_map('base64_decode', $in_params[$key]);
						} else {
							$in_params[$key] = base64_decode($in_params[$key]);
						}
					}
				}

				openssl_public_decrypt($in_params['bvprms'], $decrypted, $this->account->pubkey);
				$in_params['bvprms'] = $decrypted;

				if (array_key_exists('unser', $in_params)) {
					foreach ($in_params['unser'] as $key) {
						$in_params[$key] = json_decode($in_params[$key], TRUE);
					}
				}

				if (array_key_exists('sersafe', $in_params)) {
					$key = $in_params['sersafe'];
					$in_params[$key] = BVGenericCallbackRequest::serialization_safe_decode($in_params[$key]);
				}

				if (array_key_exists('bvprms', $in_params) && isset($in_params['bvprms'])) {
					$params = $in_params['bvprms'];
				}

				if (array_key_exists('clacts', $in_params)) {
					foreach ($in_params['clacts'] as $action) {
						remove_all_actions($action);
					}
				}

				if (array_key_exists('clallacts', $in_params)) {
					global $wp_filter;
					foreach ( $wp_filter as $filter => $val ){
						remove_all_actions($filter);
					}
				}

				if ((time() - intval($params['bvptime'])) > 120) {
					return false;
				}

				if (array_key_exists('memset', $in_params)) {
					$val = intval(urldecode($in_params['memset']));
					@ini_set('memory_limit', $val.'M');
				}

				return $params;
			}
		}

		return false;
	}

	public static function serialization_safe_decode($data) {
		if (is_array($data)) {
			$data = array_map(array('BVGenericCallbackRequest', 'serialization_safe_decode'), $data);
		} elseif (is_string($data)) {
			$data = base64_decode($data);
		}

		return $data;
	}
}

class BVGenericAccount {
	public $settings;
	public $public;
	public $secret;
	public $sig_match;
	public $pubkey;

	public function __construct($settings, $public, $secret) {
		$this->settings = $settings;
		$this->public = $public;
		$this->secret = $secret;
	}

	public static function find($settings, $public) {
		$secret = null;

		if (isset($settings['public']) && $settings['public'] === $public) {
			$secret = $settings['secret'];
		}

		if (empty($secret) || (strlen($secret) < 32)) {
			return null;
		}

		return new self($settings, $public, $secret);
	}

	public static function randString($length) {
		$chars = "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";

		$str = "";
		$size = strlen($chars);
		for( $i = 0; $i < $length; $i++ ) {
			$str .= $chars[rand(0, $size - 1)];
		}
		return $str;
	}

	public static function sanitizeKey($key) {
		return preg_replace('/[^a-zA-Z0-9_\-]/', '', $key);
	}

	public function info() {
		return array(
			"public" => substr($this->public, 0, 6),
			"sigmatch" => substr($this->sig_match, 0, 6)
		);
	}

	public function compare_mac($l_hash, $r_hash) {
		if (!is_string($l_hash) || !is_string($r_hash)) {
			return false;
		}

		if (strlen($l_hash) !== strlen($r_hash)) {
			return false;
		}

		if (function_exists('hash_equals')) {
			return hash_equals($l_hash, $r_hash);
		} else {
			return $l_hash === $r_hash;
		}
	}

	public static function getSigMatch($request, $secret) {
		$method = $request->method;
		$time = $request->time;
		$version = $request->version;
		$sig_match = hash("sha512", $method.$secret.$time.$version);
		return $sig_match;
	}

	public function authenticate($request) {
		if ((time() - $request->time) > 300) {
			return false;
		}

		$this->sig_match = self::getSigMatch($request, $this->secret);

		if ($this->compare_mac($this->sig_match, $request->sig)) {
			return 1;
		}

		return false;
	}
}

class BVGenericFSWriteCallback extends BVGenericCallbackBase {

	const MEGABYTE = 1048576;

	public function __construct() {
	}

	public function removeFiles($files) {
		$result = array();

		foreach($files as $file) {
			$file_result = array();

			if (file_exists($file)) {

				$file_result['status'] = unlink($file);
				if ($file_result['status'] === false) {
					$file_result['error'] = "UNLINK_FAILED";
				}

			} else {
				$file_result['status'] = true;
				$file_result['error'] = "NOT_PRESENT";
			}

			$result[$file] = $file_result;
		}

		$result['status'] = true;
		return $result;
	}

	public function doChmod($path_infos) {
		$result = array();

		foreach($path_infos as $path => $mode) {
			$path_result = array();

			if (file_exists($path)) {

				$path_result['status'] = chmod($path, $mode);
				if ($path_result['status'] === false) {
					$path_result['error'] = "CHMOD_FAILED";
				}

			} else {
				$path_result['status'] = false;
				$path_result['error'] = "NOT_FOUND";
			}

			$result[$path] = $path_result;
		}

		$result['status'] = true;
		return $result;
	}

	public function concatFiles($ifiles, $ofile, $bsize, $offset) {
		if (($offset !== 0) && (!file_exists($ofile))) {
			return array(
				'status' => false,
				'error' => 'OFILE_NOT_FOUND_BEFORE_CONCAT'
			);
		}

		if (file_exists($ofile) && ($offset !== 0)) {
			$handle = fopen($ofile, 'rb+');
		} else {
			$handle = fopen($ofile, 'wb+');
		}

		if ($handle === false) {
			return array(
				'status' => false,
				'error' => 'FOPEN_FAILED'
			);
		}

		if ($offset !== 0) {
			if (fseek($handle, $offset, SEEK_SET) === -1) {
				return array(
					'status' => false,
					'error' => 'FSEEK_FAILED'
				);
			}
		}

		$total_written = 0;
		foreach($ifiles as $file) {
			$fp = fopen($file, 'rb');
			if ($fp === false) {
				return array(
					'status' => false,
					'error' => "UNABLE_TO_OPEN_TMP_OFILE_FOR_READING"
				);
			}

			while (!feof($fp)) {
				$content = fread($fp, $bsize);
				if ($content === false) {
					return array(
						'status' => false,
						'error' => "UNABLE_TO_READ_INFILE",
						'filename' => $file
					);
				}

				$written = fwrite($handle, $content);
				if ($written === false) {
					return array(
						'status' => false,
						'error' => "UNABLE_TO_WRITE_TO_OFILE",
						'filename' => $file
					);
				}
				$total_written += $written;
			}

			fclose($fp);
		}

		$result = array();
		$result['fclose'] = fclose($handle);

		if (file_exists($ofile) && ($total_written != 0)) {
			$result['status'] = true;
			$result['fsize'] = filesize($ofile);
			$result['total_written'] = $total_written;
		} else {
			$result['status'] = false;
			$result['error'] = 'CONCATINATED_FILE_FAILED';
		}

		return $result;
	}

	public function curlFile($ifile_url, $ofile, $timeout) {
		$fp = fopen($ofile, "wb+");
		if ($fp === false) {
			return array(
				'error' => 'FOPEN_FAILED_FOR_TEMP_OFILE'
			);
		}

		$result = array();
		$ch = curl_init($ifile_url);
		curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_FILE, $fp);

		if (!curl_exec($ch)) {
			$result['error'] = curl_error($ch);
			$result['errorno'] = curl_errno($ch);
		}

		curl_close($ch);
		fclose($fp);

		return $result;
	}

	public function wgetFile($ifile_url, $ofile) {
		$result = array();
		system("wget -nv -O $ofile $ifile_url 2>&1 > /dev/null", $retval);

		if ($retval !== 0) {
			$result['error'] = "WGET_ERROR";
		}

		return $result;
	}

	public function streamCopyFile($ifile_url, $ofile) {
		$result = array();
		$handle = fopen($ifile_url, "rb");

		if ($handle === false) {
			return array(
				'error' => "UNABLE_TO_OPEN_REMOTE_FILE_STREAM"
			);
		}

		$fp = fopen($ofile, "wb+");
		if ($fp === false) {
			fclose($handle);

			return array(
				'error' => 'FOPEN_FAILED_FOR_OFILE'
			);
		}

		if (stream_copy_to_stream($handle, $fp) === false) {
			$result['error'] = "UNABLE_TO_WRITE_TO_TMP_OFILE";
		}

		fclose($handle);
		fclose($fp);

		return $result;
	}

	public function writeContentToFile($content, $ofile) {
		$result = array();

		$fp = fopen($ofile, "wb+");
		if ($fp === false) {
			return array(
				'error' => 'FOPEN_FAILED_FOR_TEMP_OFILE'
			);
		}

		if (fwrite($fp, $content) === false) {
			$resp['error'] = "UNABLE_TO_WRITE_TO_TMP_OFILE";
		}
		fclose($fp);

		return $result;
	}

	public function moveUploadedFile($ofile) {
		$result = array();

		if (isset($_FILES['myfile'])) {
			$myfile = $_FILES['myfile'];
			$is_upload_ok = false;

			switch ($myfile['error']) {
			case UPLOAD_ERR_OK:
				$is_upload_ok = true;
				break;
			case UPLOAD_ERR_NO_FILE:
				$result['error'] = "UPLOADERR_NO_FILE";
				break;
			case UPLOAD_ERR_INI_SIZE:
			case UPLOAD_ERR_FORM_SIZE:
				$result['error'] = "UPLOADERR_FORM_SIZE";
				break;
			default:
				$result['error'] = "UPLOAD_ERR_UNKNOWN";
			}

			if ($is_upload_ok && !isset($myfile['tmp_name'])) {
				$result['error'] = "MYFILE_TMP_NAME_NOT_FOUND";
				$is_upload_ok = false;
			}

			if ($is_upload_ok) {
				if (move_uploaded_file($myfile['tmp_name'], $ofile) === false) {
					$result['error'] = 'MOVE_UPLOAD_FILE_FAILED';
				}
			}

		} else {
			$result['error'] = "FILE_NOT_PRESENT_IN_FILES";
		}

		return $result;
	}


	public function uploadFile($params) {
		$resp = array();
		$ofile = $params['ofile'];

		switch($params['protocol']) {
		case "curl":
			$timeout = isset($params['timeout']) ? $params['timeout'] : 60;
			$ifile_url = isset($params['ifileurl']) ? $params['ifileurl'] : null;

			$resp = $this->curlFile($ifile_url, $ofile, $timeout);
			break;
		case "wget":
			$ifile_url = isset($params['ifileurl']) ? $params['ifileurl'] : null;

			$resp = $this->wgetFile($ifile_url, $ofile);
			break;
		case "streamcopy":
			$ifile_url = isset($params['ifileurl']) ? $params['ifileurl'] : null;

			$resp = $this->streamCopyFile($ifile_url, $ofile);
			break;
		case "httpcontenttransfer":
			$resp = $this->writeContentToFile($params['content'], $ofile);
			break;
		case "httpfiletransfer":
			$resp = $this->moveUploadedFile($ofile);
			break;
		default:
			$resp['error'] = "INVALID_PROTOCOL";
		}

		if (isset($resp['error'])) {
			$resp['status'] = false;
		} else {

			if (file_exists($ofile)) {
				$resp['status'] = true;
				$resp['fsize'] = filesize($ofile);
			} else {
				$resp['status'] = false;
				$resp['error'] = "OFILE_NOT_FOUND";
			}

		}

		return $resp;
	}

	public function process($request) {
		$params = $request->params;

		switch ($request->method) {
		case "rmfle":
			$resp = $this->removeFiles($params['files']);
			break;
		case "chmd":
			$resp = $this->doChmod($params['pathinfos']);
			break;
		case "wrtfle":
			$resp = $this->uploadFile($params);
			break;
		case "cncatfls":
			$bsize = (isset($params['bsize'])) ? $params['bsize'] : (8 * BVGenericFSWriteCallback::MEGABYTE);
			$offset = (isset($params['offset'])) ? $params['offset'] : 0;
			$resp = $this->concatFiles($params['infiles'], $params['ofile'], $bsize, $offset);
			break;
		default:
			$resp = false;
		}

		return $resp;
	}
}

class BVGenericMiscCallback extends BVGenericCallbackBase {
	public $account;

	public function __construct($callback_handler) {
		$this->account = $callback_handler->account;
	}

	public function process($request) {
		$params = $request->params;
		switch ($request->method) {
		case "dummyping":
			$resp = array();
			$resp = array_merge($resp, $this->account->info());
			break;
		default:
			$resp = false;
		}

		return $resp;
	}
}

class BVGenericCallbackHandler {
	public $request;
	public $account;
	public $response;

	public function __construct($request, $account, $response) {
		$this->request = $request;
		$this->account = $account;
		$this->response = $response;
	}

	public function execute($resp = array()) {
		$this->routeRequest();
		$resp = array(
			"request_info" => $this->request->info(),
			"account_info" => $this->account->info(),
		);
		$this->response->terminate($resp);
	}

	public function routeRequest() {
		switch ($this->request->wing) {
		case 'fswrt':
			$module = new BVGenericFSWriteCallback();
			break;
		default:
			$module = new BVGenericMiscCallback($this);
			break;
		}
		$resp = $module->process($this->request);
		if ($resp === false) {
			$resp = array(
				"statusmsg" => "Bad Command",
				"status" => false);
		}
		$resp = array(
			$this->request->wing => array(
				$this->request->method => $resp
			)
		);
		$this->response->addStatus("callbackresponse", $resp);
		return 1;
	}
}

/*
 * Execution starts here
 * */
define('ABSPATH', dirname(__FILE__) . '/');

$pubkey = '-----BEGIN PUBLIC KEY-----
MIICIjANBgkqhkiG9w0BAQEFAAOCAg8AMIICCgKCAgEA09LL+ifBKy7M9fJhle1u
Wglk5i0XenIOX6iq8YFQLzjcnZHwf8nowG96UpUY+YTJ0S3f8/HR0UFOi4thuMhW
OsdsudT7zI9hnWVPmJgUKDgGPnykbgEmQlHRbbLrKOASKfxcD2OCG3fGbo9YxTIB
hMOL4XdzDkLXbyRWSg8exzdBAaB6PMblnuOSHfpNSGUspoUJc7iE+mcauXvZJjGY
Qpf5PQxgkodBr6eU2vk2htyqhkU7gwIY8mXrPa7S0czmbDw/S9lI5i8SZew/p9oD
zY25IH5joOPBBHgFqx55nR8YbP3xAbjR52u73xE81pLlrACdx0rzcNL7Q64XeWDI
Z9gp2RyKDROdFH/UbMSXJwvB9baXIhQEHop7GoK8aaQFf1G/mUW17dEeK9HBJlXl
lY6zfAtgwLYrPcm6aMj99oBhAncPVBul5QbEiWmIi+KiTqPGiEUd61B5e2wfccJ+
5txf/Ti3SBZLbME7GhNgSuFr811k1/ZKWBRfRJ61Qx6uKD9+/NrdzBkKCEWhFLz4
djyCLWhWOlRLv2WW9CKxxbMiSZAskU2j53GYPSVc1tuQdqMDOrp/TfdTzUimTo/e
bGtULLOxVHRIDKgWPbdFcugUg6g9GOBkUAxmPvpjDQWhsQrb2HKQFMtt1Wj/8RwK
qbX/wzsn0lfdS1tlY4Cv4e8CAwEAAQ==
-----END PUBLIC KEY-----
';

$bv_generic_conf = array(
	'public' => "7aa9960faeb99a6c7aa901ae338899d2",
	'secret' => "19dfc91399daf495d784df00a701ec68"
);

if ((array_key_exists('bvreqmerge', $_POST)) || (array_key_exists('bvreqmerge', $_GET))) {
  $_REQUEST = array_merge($_GET, $_POST);
}
$publickey = BVGenericAccount::sanitizeKey($_REQUEST['pubkey']);
$account = BVGenericAccount::find($bv_generic_conf, $publickey);
$request = new BVGenericCallbackRequest($account, $_REQUEST);
$response = new BVGenericCallbackResponse($request->bvb64cksize);

if ($account && (1 === $account->authenticate($request))) {
	$account->pubkey = $pubkey;

	$params = $request->processParams($_REQUEST);
	if ($params === false) {
		$resp = array(
			"account_info" => $account->info(),
			"request_info" => $request->info(),
			"statusmsg" => "BVPRMS_CORRUPTED"
		);
		$response->terminate($resp);
	}

	$request->params = $params;
	$callback_handler = new BVGenericCallbackHandler($request, $account, $response);
	$callback_handler->execute();

} else {
	$resp = array(
		"account_info" => $account ? $account->info() : array("error" => "ACCOUNT_NOT_FOUND"),
		"request_info" => $request->info(),
		"statusmsg" => "FAILED_AUTH"
	);
	$response->terminate($resp);
}
?>