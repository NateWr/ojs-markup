<?php

/**
* @file plugins/generic/markup/SettingsForm.inc.php
*
* Copyright (c) 2003-2013 John Willinsky
* Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
*
* @class SettingsForm
* @ingroup plugins_generic_markup
*
* @brief Form for Document Markup gateway plugin settings
*/

import('lib.pkp.classes.form.Form');

define('MARKUP_CSL_STYLE_DEFAULT', '674e1c66aa817a0713a410915ac1b298');
define('MARKUP_DOCUMENT_SERVER_URL_DEFAULT', 'http://pkp-udev.lib.sfu.ca/');

class SettingsForm extends Form {

	/** @var $journalId int */
	var $journalId;

	/** @var $plugin object */
	var $plugin;

	/** @var $settings array */
	var $settings;

	/**
	 * Constructor
	 * @param $plugin mixed Plugin object
	 * @param $journalId int JournalId
	 */
	function SettingsForm(&$plugin, $journalId) {
		$this->journalId = $journalId;
		$this->plugin =& $plugin;
		$journal =& Request::getJournal();
		parent::Form($plugin->getTemplatePath() . 'settingsForm.tpl');

		// Validation checks for this form
		$this->settings = array(
			'cslStyle' => 'string',
			'markupHostPass' => 'string',
			'markupHostURL' => 'string',
			'markupHostUser' => 'string',
			'reviewVersion' => 'bool',
		);
	}

	/**
	 * Validate the form
	 *
	 * @return bool Whether or not the form validated
	 */
	function validate() {
		$this->addCheck(new FormValidatorPost($this));
		$this->addCheck(new FormValidator($this, 'cslStyle', 'required', 'plugins.generic.markup.required.cslStyle'));
		$this->addCheck(new FormValidator($this, 'markupHostPass', 'optional', 'plugins.generic.markup.optional.markupHostPass'));
		$this->addCheck(new FormValidator($this, 'markupHostURL', 'required', 'plugins.generic.markup.required.markupHostURL'));
		$this->addCheck(new FormValidator($this, 'markupHostUser', 'optional', 'plugins.generic.markup.optional.markupHostUrl'));
		$this->addCheck(new FormValidator($this, 'reviewVersion', 'optional', 'plugins.generic.markup.optional.reviewVersion'));

		return parent::validate();
	}

	/**
	 * Initialize plugin settings form
	 *
	 * @return void
	 */
	function initData() {
		$journal =& Request::getJournal();
		$journalId = $this->journalId;
		$plugin =& $this->plugin;

		// User must at least load settings page for plugin to work with defaults.
		if ($plugin->getSetting($journalId, 'cslStyle') == '') {
			$plugin->updateSetting($journalId, 'cslStyle', MARKUP_CSL_STYLE_DEFAULT);
		}
		if ($plugin->getSetting($journalId, 'markupHostURL') == '') {
			$plugin->updateSetting($journalId, 'markupHostURL', MARKUP_DOCUMENT_SERVER_URL_DEFAULT);
		}

		$this->setData('cslStyle', $plugin->getSetting($journalId, 'cslStyle'));
		$this->setData('markupHostUser', $plugin->getSetting($journalId, 'markupHostUser'));
		$this->setData('reviewVersion', $plugin->getSetting($journalId, 'reviewVersion'));
		$this->setData('markupHostURL', $plugin->getSetting($journalId, 'markupHostURL'));
	}

	/**
	 * Populate and display settings form
	 *
	 * @return void
	 */
	function display() {
		$templateManager =& TemplateManager::getManager();

		// Signals indicating plugin compatibility
		$templateManager->assign('curlSupport', function_exists('curl_init') ? __('plugins.generic.markup.settings.installed') : __('plugins.generic.markup.settings.notInstalled'));
		$templateManager->assign('zipSupport', extension_loaded('zlib') ? __('plugins.generic.markup.settings.installed') : __('plugins.generic.markup.settings.notInstalled'));
		$templateManager->assign('php5Support', checkPhpVersion('5.0.0') ? __('plugins.generic.markup.settings.installed') : __('plugins.generic.markup.settings.notInstalled'));
		$templateManager->assign('pathInfo', Request::isPathInfoEnabled() ? __('plugins.generic.markup.settings.enabled') : __('plugins.generic.markup.settings.disabled'));

		$additionalHeadData = '<link rel="stylesheet" type="text/css" href="/' . $this->plugin->getPluginPath() . '/css/settingsForm.css" />';
		$additionalHeadData .= '<script type="text/javascript" src="/' . $this->plugin->getPluginPath() . '/js/settingsForm.js" />';
		$templateManager->assign('additionalHeadData', $additionalHeadData);

		parent::display();
	}

	/**
	 * Assign form data to user-submitted data
	 *
	 * @return void
	 */
	function readInputData() {
		$this->readUserVars(
			array(
				'cslStyle',
				'markupHostPass',
				'markupHostURL',
				'markupHostUser',
				'reviewVersion',
			)
		);
	}

	/**
	 * Save settings
	 *
	 * @return void
	 */
	function execute() {
		$plugin =& $this->plugin;
		$journalId = $this->journalId;

		$plugin->updateSetting($journalId, 'cslStyle', $this->getData('cslStyle'));

		$markupHostURL = $this->getData('markupHostURL');
		if ($markupHostURL) {
			if (!parse_url($markupHostURL, PHP_URL_SCHEME)) {
				$markupHostURL = 'http://' . $markupHostURL;
			}
			if (substr(parse_url($markupHostURL, PHP_URL_PATH), -1) != '/') {
				$markupHostURL .= '/';
			}
		}
		$plugin->updateSetting($journalId, 'markupHostURL', $markupHostURL);

		$plugin->updateSetting($journalId, 'markupHostUser', $this->getData('markupHostUser'));
		$plugin->updateSetting($journalId, 'markupHostPass', $this->getData('markupHostPass'));
		$plugin->updateSetting($journalId, 'reviewVersion', $this->getData('reviewVersion'));
	}

	/**
	 * Returns JournalFileManager instance
	 *
	 * @param $journal mixed Journal to get a fiel manager instance for
	 *
	 * @return mixed JournalFileManager instance
	 */
	function _getJournalFileManager($journal = null) {
		if (!$journal) { $journal =& Request::getJournal(); }
		import('classes.file.JournalFileManager');
		return new JournalFileManager($journal);
	}
}
