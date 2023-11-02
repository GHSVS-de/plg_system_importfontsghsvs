<?php
namespace GHSVS\Plugin\System\ImportfontsGhsvs\Field;

defined('_JEXEC') or die;

use Joomla\CMS\Form\FormField;
use Joomla\CMS\HTML\HTMLHelper;

class AssetsBeField extends FormField
{
	protected $type = 'AssetsBe';

	protected function getInput()
	{
		$loadjs = isset($this->element['loadjs'])
			? (string) $this->element['loadjs'] : true;

		$loadcss = isset($this->element['loadcss'])
			? (string) $this->element['loadcss'] : true;

		$file = 'plg_system_importfontsghsvs/backend';

		if ($loadcss !== 'false')
		{
			HTMLHelper::_(
				'stylesheet',
				$file . '.css',
				[
					//Allow template overrides in css/plg_system_charactercounterghsvs:
					'relative' => true,
					//'pathOnly' => false,
					//'detectBrowser' => false,
					//'detectDebug' => true,
				]
			);
		}

		if ($loadjs !== 'false')
		{
			HTMLHelper::_('jquery.framework');
			HTMLHelper::_(
				'script',
				$file . '.js',
				[
					//Allow template overrides in css/plg_system_charactercounterghsvs:
					'relative' => true,
					//'pathOnly' => false,
					//'detectBrowser' => false,
					//'detectDebug' => true,
				]
			);
		}

		return '';
	}

	protected function getLabel()
	{
		return '';
	}
}
