<?php
/*
GHSVS 2019-02-01
Usage:
<field name="logbuttons" type="plgSystemHyphenateGhsvs.logbuttons" hiddenLabel="true"/>

Inserts Ajax-Buttons for Log File.

*/
defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\FormField;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\FileLayout;
use Joomla\CMS\Plugin\PluginHelper;

class plgSystemimportfontsghsvsFormFieldLogButtons extends FormField
{
	protected $type         = 'logbuttons';

	protected $renderLayout = 'renderfield';

	protected $myLayoutPath = 'plugins/system/importfontsghsvs/src/Layout';

	protected function getInput()
	{
		$offHint = '';

		if (PluginHelper::isEnabled('system', 'importfontsghsvs'))
		{
			HTMLHelper::_('behavior.core');
			$files = [
				'buttons-ajax.js',
				'log-buttons.js',
			];

			foreach ($files as $file)
			{
				HTMLHelper::_(
					'script',
					'plg_system_importfontsghsvs/' . $file,
					[
						'relative' => true,
						'version' => 'auto',
					],
					[
						'defer' => true,
					]
				);
			}

			Factory::getDocument()->addScriptOptions(
				'plg_system_importfontsghsvs',
				[
					'ajaxError' => Text::sprintf(
						'PLG_SYSTEM_IMPORTFONTSGHSVS_AJAX_ERROR'
					),
					'bePatient' => Text::sprintf(
						'PLG_SYSTEM_IMPORTFONTSGHSVS_BE_PATIENT'
					),

				]
			);
		}
		else
		{
			$offHint = Text::_('PLG_SYSTEM_IMPORTFONTSGHSVS_BUTTONS_INACTIVE');

			return $offHint;
		}

		return '
		<div id=logButtonsContainer>
			<div><button class=showFilePath>'
			. Text::_('PLG_SYSTEM_IMPORTFONTSGHSVS_BUTTON_LOG_FILE_INFOS')
			. '</button></div>
			<div><br><button class=showFile>'
			. Text::_('PLG_SYSTEM_IMPORTFONTSGHSVS_BUTTON_LOG_FILE_SHOW')
			. '</button></div>
			<div><br><button class=deleteFile>'
			. Text::_('PLG_SYSTEM_IMPORTFONTSGHSVS_BUTTON_LOG_FILE_DELETE')
			. '</button></div>
			<div class=ajaxOutput></div>
		</div>';
	}

	public function getLayoutPaths()
	{
		$customPaths      = [JPATH_SITE . '/' . $this->myLayoutPath];
		$defaultPaths     = new FileLayout('');
		$defaultPaths     = $defaultPaths->getDefaultIncludePaths();
		$parentFieldPaths = parent::getLayoutPaths();

		return array_merge($customPaths, $defaultPaths, $parentFieldPaths);
	}
}
