<?php
	require_once TOOLKIT . '/class.datasource.php';

	Class datasourceje_login_user_info extends Datasource {

		public $dsParamROOTELEMENT = 'je-login';

		public function __construct(array $env = null, $process_params=true) {
			parent::__construct($env, $process_params);
		}

		public function about() {
			return array(
				'name' => 'JE Login User Info',
				'version' => '0.1',
				'release-date' => '2013-04-28',
				'author' => array(
					'name' => 'Peter Skirenko',
					'website' => 'http://www.peter-s.me',
					'email' => 'petertron@mailservice.ms'
				)
			);
		}

		public function execute(array &$param_pool=NULL){
			$xml = new XMLElement($this->dsParamROOTELEMENT);
			$info_fields = Symphony::Database()->fetch("SELECT * FROM tbl_je_login_manager");
			$cookie = new Cookie('JE', TWO_WEEKS, __SYM_COOKIE_PATH__);
			$login_info = $cookie->get('login_info');
			if($login_info) {
				$xml->setAttribute('logged-in', 'yes');
				foreach($info_fields as $f) {
					extract($f, EXTR_PREFIX_ALL, 'f');
					if($f_include_in_xml == 'yes') {
						$xml->appendChild(new XMLElement($f_element_name, $login_info[$f_provider_info_field]));
					}
					if($f_create_ds_param == 'yes') {
						$param_pool['ds-' . $this->dsParamROOTELEMENT . '.' . $f_element_name] = $login_info[$f_provider_info_field];
					}
				}
			}
			else $xml->setAttribute('logged-in', 'no');
			return $xml;
		}
	} 
