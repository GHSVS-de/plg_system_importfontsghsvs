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
?><?php
defined('_JEXEC') or die;

use Joomla\CMS\Form\Form;

class FilterFieldHelper extends Form
{
	// Extended Form class for sanitizing subforms.
	public function filterField($element, $value)
	{
		return parent::filterField($element, $value);
	}
}