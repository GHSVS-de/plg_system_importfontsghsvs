<?php

namespace GHSVS\Plugin\System\ImportfontsGhsvs\Helper;

\defined('_JEXEC') or die;

use Joomla\CMS\Application\ApplicationHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\Filesystem\Folder;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Uri\Uri;
use Joomla\Registry\Registry;
use GHSVS\Plugin\System\ImportfontsGhsvs\Extension\ImportfontsGhsvs;
use Joomla\CMS\Log\Log;

class ImportfontsGhsvsHelper
{
	protected $fileCount = 0;

	protected $basepath = 'plg_system_importfontsghsvs';

	public function getFonts($params, $key = 'fonts') : array
	{
		$require = [];
		$fonts   = $params->get($key, null);
		$i = 0;

		if (\is_object($fonts) && \count(get_object_vars($fonts)))
		{
			// Bug fix: Some stupid plugins still "destroy" $templateStyle->id.
			$templateStyle = Factory::getApplication()->getTemplate(true);
			$templateStyle = isset($templateStyle->id) ? $templateStyle->id : 0;

			foreach ($fonts as $font)
			{
				$font = new Registry($font);
				$load_in_templates = (array) $font->get('load_in_templates');

				if (
					$font->get('active', 0) === 1
					&& ($import_line = $font->get('import_line', ''))
					&& (empty($load_in_templates) || \in_array($templateStyle, $load_in_templates))
				) {
					$require[$i]['import_line'] = $import_line;
					$require[$i]['family'] = ApplicationHelper::stringURLSafe($font->get('family', ''));
					$i++;
				}
			}
		}
		sort($require);

		return $require;
	}

	/*
	Google returned a CSS with URLs to web font files.
	SVG files don't have a file extender but query and fragment
	https://fonts.gstatic.com/l/font?kit=KFOmCnqEu92Fr1Mu4mxN&skey=a0a0114a1dcab3ac&v=v18#Roboto
	Thus I have to create a filename for the replacement of google URL with local path.

	Addition: https://fonts.googleapis.com/css?family=Inconsolata&text=Hello%20World
	returns a similiar response:
	src: ..., url(https://fonts.gstatic.com/l/font?kit=QldKNThLqRwH-OJ1UHjlKFlb9KVOj66UafhJuQ&skey=20fa6569a31c71ee&v=v17) format('woff2');
	That's a woff2 file!
	*/
	public function check4svg($urlGoogle, $src)
	{
		$uri      = Uri::getInstance($urlGoogle);
		$path     = $uri->getPath();
		$query    = $uri->getQuery();
		$fragment = $uri->getFragment();

		if ($query || $fragment)
		{
			$ext = File::getExt($path);

			if (!$ext)
			{
				$muster = '/ format\(([^)]+)\)/';
				preg_match($muster, $src, $matches);

				if (!empty($matches[1]))
				{
					$ext = trim($matches[1], '"\'');
				}
				else
				{
					$ext = 'EOT';
				}
			}
			$fontFile = $path . '/' . md5($query) . $fragment . '.' . $ext;

			return [$fontFile, $fragment ? '#' . $fragment : ''];
		}
		elseif (!empty($path))
		{
			return [$path, ''];
		}

		return false;
	}

	public function log($data, $priority = Log::ERROR)
	{
		Log::add($data, $priority, $this->basepath);
			}

	public function initLogger()
			{
		$options = $this->getLogFile();

		Log::addLogger(
			$options,
			Log::ALL,
			[$this->basepath]
		);
	}

	public function getFolderSize($dir)
	{
		$folderSize = $this->folderSize($dir);
		$folderSize = HTMLHelper::_('number.bytes', $folderSize);
		$folderSize = [$folderSize, $this->fileCount];
		$this->fileCount = 0;

		return $folderSize;
	}

	/*
	https://gist.github.com/eusonlito/5099936
	*/
	protected function folderSize($dir)
	{
		$bytes = 0;

		foreach (glob(rtrim($dir, '/') . '/*', GLOB_NOSORT) as $each)
		{
			$this->fileCount++;
			$bytes += is_file($each) ? (int) filesize($each) : (int) $this->folderSize($each);
		}

		return $bytes;
	}

	public function renewal($fontPath, $renewalLog)
	{
		$fontPath = JPATH_SITE . '/' . $fontPath;

		if (\is_dir($fontPath))
		{
			Folder::delete($fontPath);
		}

		if (\is_dir($fontPath))
		{
			$msg = Text::sprintf(
				'PLG_SYSTEM_IMPORTFONTSGHSVS_ERROR_DELETE_FONT_PATH',
				$this->removeJPATH_SITE($fontPath)
			);

			return $msg;
		}

		if (!File::write($renewalLog, time()))
		{
			$msg = Text::sprintf(
				'PLG_SYSTEM_IMPORTFONTSGHSVS_ERROR_WRITE_RENEWAL_FILE',
				$this->removeJPATH_SITE($renewalLog)
			);

			return $msg;
		}

		return true;
	}

	public function removeJPATH_SITE($str)
	{
		return str_replace(JPATH_SITE, '', $str);
	}

	public function getLogFile()
	{
		$options = [
			'text_file' => $this->basepath . '-log.php',
			'text_file_path' => Factory::getApplication()->get('log_path') . '/' . $this->basepath,
			'text_entry_format' => '{DATETIME}  {PRIORITY}  {MESSAGE}',
			'text_file_no_php' => true,
		];

		$options['logFile'] = $options['text_file_path'] . '/' . $options['text_file'];
		return $options;
	}
}
