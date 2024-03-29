<?php
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Installer\InstallerScript;
use Joomla\CMS\Log\Log;

class plgSystemImportfontsGhsvsInstallerScript extends InstallerScript
{
	/**
	 * A list of files to be deleted with method removeFiles().
	 *
	 * @var    array
	 * @since  2.0
	 */
	protected $deleteFiles = [
		'/media/plg_system_importfonts/js/index.html',
		'/media/plg_system_importfonts/css/index.html',
		'/plugins/system/importfontsghsvs/language/en-GB/en-GB.plg_system_importfontsghsvs.ini',
		'/plugins/system/importfontsghsvs/language/en-GB/en-GB.plg_system_importfontsghsvs.sys.ini',
		'/plugins/system/importfontsghsvs/language/de-DE/de-DE.plg_system_importfontsghsvs.ini',
		'/plugins/system/importfontsghsvs/language/de-DE/de-DE.plg_system_importfontsghsvs.sys.ini',
		'/plugins/system/importfontsghsvs/importfontsghsvs.php',
		'/plugins/system/importfontsghsvs/src/Helper/FilterFieldJ3.php',
		'/plugins/system/importfontsghsvs/src/Helper/FilterFieldJ4.php',
		'/plugins/system/importfontsghsvs/src/Field/version.php',
		'/plugins/system/importfontsghsvs/src/Field/assetsbe.php',
	];

	/**
	 * A list of folders to be deleted with method removeFiles().
	 *
	 * @var    array
	 * @since  2.0
	 */
	protected $deleteFolders = [
		'/media/fontsghsvs',
		'/media/plg_system_importfonts/images',
		'/plugins/system/importfontsghsvs/Field',
		'/plugins/system/importfontsghsvs/Helper',
		'/plugins/system/importfontsghsvs/layouts',
		'/plugins/system/importfontsghsvs/myforms',
	];

	public function preflight($type, $parent)
	{
		$manifest = @$parent->getManifest();

		if ($manifest instanceof SimpleXMLElement)
		{
			if ($type === 'update' || $type === 'install' || $type === 'discover_install')
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
					Log::add($msg, Log::WARNING, 'jerror');
				}

				// Check for the maximum PHP version before continuing
				if ($maximumPhp && version_compare(PHP_VERSION, $maximumPhp, '>'))
				{
					$msg = 'Your PHP version (' . PHP_VERSION . ') is too high for this extension. Maximum PHP version is: ' . $maximumPhp . '.';

					Log::add($msg, Log::WARNING, 'jerror');
				}

				if (isset($msg))
				{
					return false;
				}
			}

			if (trim((string) $manifest->allowDowngrades))
			{
				$this->allowDowngrades = true;
			}
		}

		if (!parent::preflight($type, $parent))
		{
			return false;
		}

		if ($type === 'update')
		{
			$this->removeOldUpdateservers();
		}

		return true;
	}

	/**
	 * Runs right after any installation action is preformed on the component.
	 *
	 * @param  string    $type   - Type of PostFlight action. Possible values are:
	 *                           - * install
	 *                           - * update
	 *                           - * discover_install
	 * @param  \stdClass $parent - Parent object calling object.
	 *
	 * @return void
	 */
	function postflight($type, $parent)
	{
		if ($type === 'update')
		{
			$this->removeFiles();
		}
	}

	public function uninstall($parent)
	{
		$this->deleteFiles[] =
			str_replace(
				JPATH_ROOT,
				'',
				Factory::getApplication()->get('log_path') . '/plg_system_importfonts-log.txt'
			);
		$this->removeFiles();
	}

	/**
	* Remove the outdated updateservers.
	*
	* @return  void
	*
	* @since   version after 2021.12.21
	*/
	protected function removeOldUpdateservers()
	{
		$db = Factory::getDbo();

		try
		{
			$query = $db->getQuery(true);

			$query->select('update_site_id')
				->from($db->qn('#__update_sites'))
				/* ->where($db->qn('location') . ' = '
				. $db->q('https://raw.githubusercontent.com/GHSVS-de/upadateservers/master/bs3ghsvs2020-update.xml'), 'OR') */
				->where($db->qn('location') . ' = '
				. $db->q('https://raw.githubusercontent.com/GHSVS-de/upadateservers/master/importfonts-update.xml'));

			$ids = $db->setQuery($query)->loadAssocList('update_site_id');

			if (!$ids)
			{
				return;
			}

			$ids = array_keys($ids);
			$ids =implode(',', $ids);

			// Delete from update sites
			$db->setQuery(
				$db->getQuery(true)
					->delete($db->qn('#__update_sites'))
					->where($db->qn('update_site_id') . ' IN (' . $ids . ')')
			)->execute();

			// Delete from update sites extensions
			$db->setQuery(
				$db->getQuery(true)
				->delete($db->qn('#__update_sites_extensions'))
				->where($db->qn('update_site_id') . ' IN (' . $ids . ')')
			)->execute();
		}
		catch (Exception $e)
		{
			return;
		}
	}
}
