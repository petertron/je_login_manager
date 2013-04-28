<?php

	require_once(TOOLKIT . '/class.administrationpage.php');

	class contentExtensionje_login_managerDatasource_elements extends AdministrationPage {
		public $id = 'je_login_manager';
		private $settings_path;
		private $field_defs = array(
			array(
				'name' => 'element_name',
				'label' => 'Name',
				'help' => 'Words may be seperated with hyphens'
			),
			array(
				'name' => 'provider_info_field',
				'label' => 'Provider Field',
				'help' => 'Field in user information from provider'
			),
			array(
				'name' => 'include_in_xml',
				'label' => 'Include as XML element'
			),
			array(
				'name' => 'create_ds_param',
				'label' => 'Create datasource parameter'
			)
		);

		public function __construct() {
			parent::__construct();

			$this->_driver = Symphony::ExtensionManager()->create($this->id);
			$this->settings_path = 'settings[' . $this->id . '][info][]';
		}

		public function __actionIndex() {
			$this->_driver->saveInfoFields();
		}

		public function __viewIndex() {
			$this->setPageType('form');
			$this->addScriptToHead(URL . '/extensions/' . $this->id . '/assets/je_login_manager.preferences.js', 400, false);

			$this->setTitle(__('%1$s &ndash; %2$s', array(__('Symphony'), __('JE Login Manager'))));
			$this->appendSubheading(__('Janrain Engage Login Manager'));

			$fieldset = new XMLElement('fieldset');
			$fieldset->setAttribute('class', 'settings');
			$fieldset->appendChild(new XMLElement('legend', __('Data Source Elements')));

			$fieldset->appendChild(new XMLElement('p', __('Elements for the <strong>JE Login User Info</strong> data source.'), array('class' => 'help')));

			$group = new XMLElement('div');
			$group->setAttribute('class', 'frame');

			$ol = new XMLElement('ol');
			$ol->setAttribute('data-name', __('Add info'));
			$ol->setAttribute('data-type', __('Remove info'));

			//	Field Template
			$li = new XMLElement('li');
			$li->setAttribute('class', 'template');
			$li->setAttribute('data-name', 'Info');
			$li->setAttribute('data-type', 'info');
			$li->appendChild(new XMLElement('header', __('Element')));

			$div_content = new XMLElement('div');
			$div_content->setAttribute('class', 'content');

			$div_group = new XMLElement('div');
			$div_group->setAttribute('class', 'group');
			$div_group->appendChild($this->makeInputField($this->field_defs[0], $f));
			$div_group->appendChild($this->makeInputField($this->field_defs[1], $f));
			$div_content->appendChild($div_group);

			$div_group = new XMLElement('div');
			$div_group->setAttribute('class', 'group');
			$div_group->appendChild($this->makeCheckBox($this->field_defs[2], 'yes'));
			$div_group->appendChild($this->makeCheckBox($this->field_defs[3], 'no'));
			$div_content->appendChild($div_group);

			$li->appendChild($div_content);
			$ol->appendChild($li);

			if ($info_fields = $this->_driver->getInfoFields()) {
				if(is_array($info_fields)) {
					foreach($info_fields as $f) {
						$li = new XMLElement('li');
						$li->setAttribute('class', 'instance expanded');
						$li->appendChild(new XMLElement('header', __('Element')));

						$div_content = new XMLElement('div');
						$div_content->setAttribute('class', 'content');

						$div_group = new XMLElement('div');
						$div_group->setAttribute('class', 'group');
						$div_group->appendChild($this->makeInputField($this->field_defs[0], $f));
						$div_group->appendChild($this->makeInputField($this->field_defs[1], $f));
						$div_content->appendChild($div_group);

						$div_group = new XMLElement('div');
						$div_group->setAttribute('class', 'group');
						$div_group->appendChild($this->makeCheckBox($this->field_defs[2], $f['include_in_xml']));
						$div_group->appendChild($this->makeCheckBox($this->field_defs[3], $f['create_ds_param']));
						$div_content->appendChild($div_group);

						$li->appendChild($div_content);
						$ol->appendChild($li);
					}
				}
			}

			$group->appendChild($ol);
			$fieldset->appendChild($group);
			$this->Form->appendChild($fieldset);

			$div = new XMLElement('div');
			$div->setAttribute('class', 'actions');
			$div->appendChild(Widget::Input('action[save]', __('Save Changes'), 'submit'));

			$this->Form->appendChild($div);

		}

		function makeInputField($field_def, $info) {
			extract($field_def, EXTR_PREFIX_ALL, 'f');
			$label = Widget::Label(__($f_label));
			$input = Widget::Input($this->settings_path . '[' . $f_name . ']', is_array($info) ? General::sanitize($info[$f_name]) : '');
			$input->setAttribute('required', 'required');
			$label->appendChild($input);
			$label->appendChild(new XMLElement('p', __($f_help), array('class' => 'help', 'style' => 'margin: 0.5em 0 -0.5em;')));
			return $label;
		}

		function makeCheckBox($field_def,  $checked) {
			extract($field_def, EXTR_PREFIX_ALL, 'f');
			$label = Widget::Label();
			$input = Widget::Input($this->settings_path . '[' . $f_name . ']', 'yes', 'checkbox');
			if($checked == 'yes') $input->setAttribute('checked', 'checked');
			$label->setValue($input->generate() . ' ' . __($f_label));
			return $label;
		}
	}

?>
