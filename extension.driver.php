<?php

	Class extension_je_login_manager extends Extension {

		public static function home() {
			return dirname(__FILE__);
		}

		public $name;

		public $id;

		public function __construct() {
			$extension = simplexml_load_file(dirname(__FILE__). '/extension.meta.xml');
			$this->name = (string)$extension->name;
			$this->id = (string)$extension->attributes()->id;
		}

		function __autoload($class) {
			$directories = array('', 'data-sources', 'content');
			foreach ($directories as $dir) {
				if (file_exists(self::home . '/' . $dir . '/' . $class . '.php')) {
					require_once self::home . '/' . $dir . '/' . $class . '.php';
					break;
				}
			}
		}

		public function getSubscribedDelegates() {
			return array(
				array(
					'page' => '/system/preferences/',
					'delegate' => 'AddCustomPreferenceFieldsets',
					'callback' => 'appendPreferences'
				),
				/*array(
					'page' => '/system/preferences/',
					'delegate' => 'Save',
					'callback' => 'save'
				),*/
				array(
					'page' => '/backend/',
					'delegate' => 'AdminPagePostCallback',
					'callback' => 'adminPagePostCallback'
				),
				array(
					'page' => '/frontend/',
					'delegate' => 'FrontendInitialised',
					'callback' => 'frontendInitialised'
				),
				array(
					'page' => '/frontend/',
					'delegate' => 'FrontendOutputPostGenerate',
					'callback' => 'addScriptTagToHead'
				)
			);
		}

		public function install()
		{
			Symphony::Database()->query(
				"CREATE TABLE IF NOT EXISTS `tbl_" . $this->id . "` (
					`id` int(6) NOT NULL auto_increment,
					`element_name` varchar(255) NOT NULL,
					`provider_info_field` varchar(255) NOT NULL,
					`include_in_xml` enum('yes','no') DEFAULT 'yes',
					`create_ds_param` enum('yes','no') DEFAULT 'no',
					PRIMARY KEY (`id`)
				)"
			);
			$defaults = array(
				array(
					'element_name' => 'provider',
					'provider_info_field' => 'providerName',
					'include_in_xml' => 'yes',
					'create_ds_param' => 'no'
				),
				array(
					'element_name' => 'name',
					'provider_info_field' => 'displayName',
					'include_in_xml' => 'yes',
					'create_ds_param' => 'no'
				),
				array(
					'element_name' => 'email',
					'provider_info_field' => 'verifiedEmail',
					'include_in_xml' => 'yes',
					'create_ds_param' => 'no'
				)
			);
			Symphony::Database()->insert($defaults, 'tbl_' . $this->id);
		}

		public function uninstall()
		{
			Symphony::Database()->query("DROP TABLE `tbl_" . $this->id . "`");
		}

		public function fetchNavigation() {
			return array(
				array(
					'location' => __('Blueprints'),
					'name' => __('JE Login Manager'),
					'link' => '/datasource_elements/'
				)
			);
		}

		public function appendPreferences(&$context) {
			$fieldset = new XMLElement('fieldset');
			$fieldset->setAttribute('class', 'settings');
			$fieldset->appendChild(new XMLElement('legend', $this->name));

			$div = new XMLElement('div');
			$div->setAttribute('class', 'two columns');
			$label = Widget::Label(__('Subdomain Of <strong>rpxnow.com</strong>'));
			$label->setAttribute('class', 'column');
			$label->appendChild(
					Widget::Input('settings[' . $this->id . '][subdomain_name]', Symphony::Configuration()->get('subdomain_name', $this->id)
				)
			);
			$div->appendChild($label);

			$label = Widget::Label(__('40-Digit API Key'));
			$label->setAttribute('class', 'column');
			$label->appendChild(
				Widget::Input('settings[' . $this->id . '][api_key]', Symphony::Configuration()->get('api_key', $this->id)
				)
			);
			$div->appendChild($label);
			$fieldset->appendChild($div);

			$label = Widget::Label(__('Token URL (optional)'));
			$label->appendChild(
				Widget::Input(
					'settings[' . $this->id . '][token_url]', Symphony::Configuration()->get('token_url', $this->id)
				)
			);
			$fieldset->appendChild($label);
			$fieldset->appendChild(new XMLElement('p', __('Any page with the <strong>JE Auth</strong> event attached. Leave blank to use the same page as the current page.'), array('class' => 'help')));

			$context['wrapper']->appendChild($fieldset);
		}

		public function save($context) {
		}

		public function adminPagePostCallback(&$context) {
			$parts = $context['parts'];
			if (implode("/", $parts) == 'blueprints/datasources/info/' . $this->handle) {
				$this->generating_datasource_info_page = true;
				if (isset($_POST['fields'])) {
					data_outputs::save_included_fields($_POST['fields']);
				}
			}
		}

		public function frontendInitialised($context) {
			if (isset($_POST['log-out'])) {
				$cookie = new Cookie('JE', TWO_WEEKS, __SYM_COOKIE_PATH__);
				$cookie->set('login_info', null);
			}
		}
		
		public function addScriptTagToHead(&$context) {
			$output = $context['output'];
			if (strpos($output, 'id="janrainEngageEmbed"')) {
				extract(Symphony::Configuration()->get($this->id), EXTR_PREFIX_ALL, 'f');
				if($f_token_url == '') $token_url = 'document.URL';
				else $token_url = '\'' . $f_token_url . '\'';

				$head_end = strpos($output, "\n", strpos($output, '</head>'));
				$head_array = explode("\n", substr($output, 0, $head_end));
				$length = count($head_array);
				$head_array[$length] = $head_array[$length - 1];
				$head_array[$length - 1] = '    <script type="text/javascript">
	(function() {
		if (typeof window.janrain !== \'object\') window.janrain = {};
		if (typeof window.janrain.settings !== \'object\') window.janrain.settings = {};

		janrain.settings.tokenUrl = ' . $token_url . '
		function isReady() { janrain.ready = true; };
		if (document.addEventListener) {
			document.addEventListener(\'DOMContentLoaded\', isReady, false);
		} else {
			window.attachEvent(\'onload\', isReady);
		}

		var e = document.createElement(\'script\');
		e.type = \'text/javascript\';
		e.id = \'janrainAuthWidget\';

		if (document.location.protocol === \'https:\') {
			e.src = \'https://rpxnow.com/js/lib/' . $f_subdomain_name . '/engage.js\' 
		} else {
			e.src = \'http://widget-cdn.rpxnow.com/js/lib/' . $f_subdomain_name . '/engage.js\';
		}

		var s = document.getElementsByTagName(\'script\')[0];
		s.parentNode.insertBefore(e, s);
	})();
	</script>';
				$context['output'] = implode("\n", $head_array) . substr($output, $head_end);
			}
		}

		public function getInfoFields() {
			return Symphony::Database()->fetch("SELECT * FROM tbl_je_login_manager");
		}

		/**
		 * Save the routes from the preferences into the database
		 *
		 * @param unknown $context Symphony context
		 */
		public function saveInfoFields() {
			$output = array();
			$row = array();
			Symphony::Database()->query("DELETE FROM tbl_je_login_manager");
			//echo var_dump($_POST['settings'][$this->id]['info']);die();
			if(!empty($_POST['settings'][$this->id]['info'])) {
				foreach($_POST['settings'][$this->id]['info'] as $item) {
					if(isset($item['element_name'])) {
						if(!empty($row)) array_push($output, $row); // Add last row to output array before starting new row
						$row = array(
							'element_name' => trim($item['element_name']),
							'provider_info_field' => '',
							'include_in_xml' => 'no',
							'create_ds_param' => 'no'
						);
					}
					elseif(isset($item['provider_info_field'])) {
						$row['provider_info_field'] = trim($item['provider_info_field']);
					}
					elseif(isset($item['include_in_xml'])) {
						$row['include_in_xml'] = $item['include_in_xml'];
					}
					elseif(isset($item['create_ds_param'])) {
						$row['create_ds_param'] = $item['create_ds_param'];
					}
					//echo "---<br><br>".var_dump($row)."<br><br>---";
				}
				$output[] = $row;
				//echo var_dump($_POST)."<br><br><br>";
				//echo var_dump($output); die();
				Symphony::Database()->insert($output, 'tbl_je_login_manager');
				unset($_POST['settings'][$this->id]['info']);
			}
		}
	}
?>