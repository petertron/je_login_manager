<?php

require_once TOOLKIT . '/class.event.php';

class eventje_auth extends Event {

	public static function about() {
		return array(
			'name' => 'JE Auth',
			'author' => array(
				'name' => 'Peter Skirenko',
				'email' => 'petertron@mailservice.ms'
			),
			'version' => '0.1',
			'release-date' => '2013-04-28'
		);
	}

	public static function allowEditorToParse() {
		return false;
	}

	public function load() {	
		if (isset($_POST['token'])) {
			$result = $this->__trigger();
			$xml = new XMLElement('je-auth');
			$xml->setAttribute('status', $result['status']);
			$xml->appendChild(new XMLElement('message', $result['message']));
		}
		//Frontend::instance()->Page()->Params();
		$cookie = new Cookie('JE', TWO_WEEKS, __SYM_COOKIE_PATH__);
		//$login_info = $cookie->get('login_info');
		return $xml;
	}

	protected function __trigger() {
		$token = $_POST['token'];
		if (strlen($token) == 40) { // token should be 40 characters
			$post_data = array(
				'token' => $token,
				'apiKey' => Symphony::Configuration()->get('api_key', 'je_login_manager'),
				'format' => 'json',
				'extended' => 'false'
			);
			$curl = curl_init();
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($curl, CURLOPT_URL, 'https://rpxnow.com/api/v2/auth_info');
			curl_setopt($curl, CURLOPT_POST, true);
			curl_setopt($curl, CURLOPT_POSTFIELDS, $post_data);
			curl_setopt($curl, CURLOPT_HEADER, false);
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($curl, CURLOPT_FAILONERROR, true);
			$auth_json = curl_exec($curl);
			curl_close($curl);
			if (!$auth_json) return array('status' => "failed", 'message' =>  "Authentication failed");

			$auth_info = json_decode($auth_json, true);
			if ($auth_info['stat'] == "ok") {
				//echo "\n auth_info:\n";
				//echo "\n"; var_dump($auth_info);
				//exit();
				$profile = $auth_info['profile'];
				$cookie = new Cookie('JE', TWO_WEEKS, __SYM_COOKIE_PATH__);
				$cookie->set('login_info', $auth_info['profile']);
				return array('status' => "success", 'message' => "Login successful");
			} else {
				return array('status' => "failed", 'message' => $auth_info['err']['msg']);
			}
		} else return array('status' => "failed", 'message' => "Authentication canceled");
	}

	public static function documentation(){
		return '
			<p>
				<a href="'. SYMPHONY_URL. 'blueprints/events/info/je_login_manager/">Janrain Engage login authentication.</a>.
			</p>
		';
	}

/*
* Please look at the PHP SDK if you are looking for somthing suited to a new project.
* https://github.com/janrain/Janrain-Sample-Code/tree/master/php/janrain-engage-php-sdk
*/
	//Some output to help debugging
//echo "SERVER VARIABLES:\n";
//var_dump($_SERVER);
//echo "HTTP POST ARRAY:\n";
//var_dump($_POST);

		/*require_once TOOLKIT. '/class.extensionmanager.php';
		Symphony::ExtensionManager()->notifyMembers(
			'openidAuthComplete', '/frontend/',	array(
				'openid-data' => $openid_data
		));*/
}
