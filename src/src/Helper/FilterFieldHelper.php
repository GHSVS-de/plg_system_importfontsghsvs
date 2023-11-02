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
namespace GHSVS\Plugin\System\ImportfontsGhsvs\Helper;

use Joomla\CMS\Form\FormField;

\defined('_JEXEC') or die;

class FilterFieldHelper extends FormField
{
	public function filterField($element, $value)
	{
		$this->element = $element;
		$this->type = (string) $element->attributes()->type;
		$result = parent::filter($value);

		return $result;
	}
}
