<?php
/*
GHSVS 2019-02-01
Usage:
<field name="logbuttons" type="plgSystemHyphenateGhsvs.logbuttons" hiddenLabel="true"/>

Inserts Ajax-Buttons for Log File.	

*/
defined('JPATH_PLATFORM') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\FormField;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Layout\FileLayout;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\PluginHelper;

class plgSystemimportfontsghsvsFormFieldRenewalButtons extends FormField
{
	protected $type         = 'renewalbuttons';
	protected $renderLayout = 'ghsvs.renderfield';
	protected $myLayoutPath = 'plugins/system/importfontsghsvs/layouts';

	protected function getInput()
	{
		$offHint = '';
		
		if (PluginHelper::isEnabled('system', 'importfontsghsvs'))
		{
			HTMLHelper::_('behavior.core');
			$files = array(
				'buttons-ajax.js',
				'renewal-buttons.js',
			);
			
			foreach ($files as $file)
			{
				HTMLHelper::_('script',
					'plg_system_importfontsghsvs/' . $file,
					array(
						'relative' => true,
						'version' => 'auto'
					),
					array(
						'defer' => true
					)
				);
			}

			Factory::getDocument()->addScriptOptions(
				'plg_system_importfontsghsvs',
				array(
					'ajaxError' => Text::sprintf(
						'PLG_SYSTEM_IMPORTFONTSGHSVS_AJAX_ERROR'),
					'bePatient' => Text::sprintf(
						'PLG_SYSTEM_IMPORTFONTSGHSVS_BE_PATIENT')
					
				)
			);
		}
		else
		{
			$offHint = Text::_('PLG_SYSTEM_IMPORTFONTSGHSVS_BUTTONS_INACTIVE');
			return $offHint;
		}

		return '
		<div id=renewalButtonsContainer>
			<div><button class=folderSize type=button>'
			. Text::_('PLG_SYSTEM_IMPORTFONTSGHSVS_BUTTON_FOLDER_SIZE')
			. '</button></div>
			<div><br><button class=deleteFile type=button>'
			. Text::_('PLG_SYSTEM_IMPORTFONTSGHSVS_BUTTON_RENEWAL_FORCE')
			. '</button></div>
			<div class=ajaxOutput></div>
		</div>';
	}

	public function getLayoutPaths()
	{
		$customPaths      = array(JPATH_SITE . '/' . $this->myLayoutPath);
		$defaultPaths     = new FileLayout('');
		$defaultPaths     = $defaultPaths->getDefaultIncludePaths();
		$parentFieldPaths = parent::getLayoutPaths();
		return array_merge($customPaths, $defaultPaths, $parentFieldPaths);
	}
	
}
