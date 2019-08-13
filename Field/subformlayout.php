<?php
/*
GHSVS 2019-02-01
Usage:

Use type="plgSystemHyphenateGhsvs.subformlayout" instead of type="subform"
and hiddenLabel="true"

to get rid of this f**** <div class="controls">

by using custom JLayout plugins/system/hyphenateghsvs/layouts/renderfield.php
*/


defined('JPATH_PLATFORM') or die;

use Joomla\CMS\Form\FormHelper;
use Joomla\CMS\Layout\FileLayout;

FormHelper::loadFieldClass('subform');

class plgSystemImportFontsGhsvsFormFieldSubformLayout extends JFormFieldSubform
{
	protected $type = 'subformlayout';

	protected $renderLayout = 'ghsvs.renderfield';
	protected $myLayoutPath = 'plugins/system/importfontsghsvs/layouts';

	// Debugge Render-Pfade der Felder-Layouts und Fehler:
	protected $debugLayouts = false;

	/**
	 * Allow to override renderer include paths in child fields
	 *
	 * @return  array
	 *
	 * @since   3.5
	 */
	public function getLayoutPaths()
	{
		$customPaths = array(JPATH_SITE . '/' . $this->myLayoutPath);

		$defaultPaths = new FileLayout('');
		$defaultPaths = $defaultPaths->getDefaultIncludePaths();

		$parentFieldPaths = parent::getLayoutPaths();

		return array_merge($customPaths, $defaultPaths, $parentFieldPaths);
	}

	protected function isDebugEnabled()
	{
		return $this->debugLayouts;
	}
}
