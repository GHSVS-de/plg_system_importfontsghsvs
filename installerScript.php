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
/**
 * Use in your extension manifest file (any tag is optional!!!!!):
 * <minimumPhp>7.0.0</minimumPhp>
 * <minimumJoomla>3.9.0</minimumJoomla>
 * Yes, use 999999 to match '3.9'. Otherwise comparison will fail.
 * <maximumJoomla>3.9.999999</maximumJoomla>
 * <maximumPhp>7.3.999999</maximumPhp>
 * <allowDowngrades>1</allowDowngrades>
 */
?><?php
defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\Installer\InstallerScript;

class plgSystemImportfontsGhsvsInstallerScript extends InstallerScript
{
	public function __construct()
	{
		$this->deleteFolders = array(
			'/media/fontsghsvs',
		);
		
		$this->deleteFiles = array(
			Factory::getApplication()->get('log_path') . '/plg_system_importfontsghsvs-log.txt',
		);
	}

	public function preflight($type, $parent)
	{
		$manifest = @$parent->getManifest();
		
		if ($manifest instanceof SimpleXMLElement)
		{
			$minimumPhp = trim((string) $manifest->minimumPhp);
			$minimumJoomla = trim((string) $manifest->minimumJoomla);
			
			// Custom
			$maximumPhp = trim((string) $manifest->maximumPhp);
			$maximumJoomla = trim((string) $manifest->maximumJoomla);
			
			$this->minimumPhp = $minimumPhp ? $minimumPhp : $this->minimumPhp;
			$this->minimumJoomla = $minimumJoomla ? $minimumJoomla : $this->minimumJoomla;

			if ($maximumJoomla && version_compare(JVERSION, $maximumJoomla, '>'))
			{
				$msg = 'Your Joomla version (' . JVERSION . ') is too high for this extension. Maximum Joomla version is: ' . $maximumJoomla . '.';
				JLog::add($msg, JLog::WARNING, 'jerror');
			}

			// Check for the maximum PHP version before continuing
			if ($maximumPhp && version_compare(PHP_VERSION, $maximumPhp, '>'))
			{
				$msg = 'Your PHP version (' . PHP_VERSION . ') is too high for this extension. Maximum PHP version is: ' . $maximumPhp . '.';
				
				JLog::add($msg, JLog::WARNING, 'jerror');
			}
			
			if (isset($msg))
			{
				return false;
			}
			
			if (trim((string) $manifest->allowDowngrades))
			{
				$this->allowDowngrades = true;
			}
		}
		return true;
	}

	public function uninstall($parent)
	{
		$this->removeFiles();
	}
}
