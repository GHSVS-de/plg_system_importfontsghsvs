<?php
/**
 * @package plugin.system importfontsghsvs for Joomla!
 * @version See importfontsghsvs.xml
 * @author G@HService Berlin Neukölln, Volkmar Volli Schlothauer
 * @copyright Copyright (C) 2019, G@HService Berlin Neukölln, Volkmar Volli Schlothauer. All rights reserved.
 * @license GNU General Public License version 3 or later; see LICENSE.txt
 * @authorUrl https://www.ghsvs.de
 * @link https://github.com/GHSVS-de/plg_system_importfontsghsvs
 */
/*
GHSVS 2019-02-01
Usage:
<field name="assetsbe" type="plgSystemHyphenateGhsvs.assetsbe" hidden="true"
	loadjs="false" loadcss="true" />

If attributs loadjs or loadcss are missing their default value is TRUE => Assets will be loaded.	

*/
?><?php
defined('JPATH_PLATFORM') or die;

use Joomla\CMS\Form\FormField;
use Joomla\CMS\HTML\HTMLHelper;

class plgSystemImportFontsGhsvsFormFieldAssetsBe extends FormField
{
	protected $type = 'assetsbe';

	protected function getInput()
	{
		$loadjs = isset($this->element['loadjs'])
			? (string) $this->element['loadjs'] : true;

		$loadcss = isset($this->element['loadcss'])
			? (string) $this->element['loadcss'] : true;
	
		$file = 'plg_system_importfontsghsvs/backend';
		
		if ($loadcss !== 'false')
		{
			HTMLHelper::_('stylesheet',
				$file . '.css',
				array(
					'relative' => true,
					'version' => 'auto'
				),
				array(
					'defer' => true
				)
			);
		}

		if ($loadjs !== 'false')
		{
			HTMLHelper::_('jquery.framework');
			HTMLHelper::_('script',
				$file . '.js',
				array(
					'relative' => true,
					'version' => 'auto'
				),
				array(
					'defer' => true
				)
			);
		}
		return '';
	}
	
	protected function getLabel()
	{
		return '';
	}
}
