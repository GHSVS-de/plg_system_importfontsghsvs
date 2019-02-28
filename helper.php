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
defined('JPATH_BASE') or die;
use Joomla\CMS\Application\ApplicationHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\Filesystem\Folder;
use Joomla\CMS\Form\Form;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Uri\Uri;
use Joomla\Registry\Registry;

class PlgImportFontsGhsvsHelper extends Form
{
	protected static $fileCount = 0;

	// Extended Form class for sanitizing subforms.
	public function filterField($element, $value)
	{
		return parent::filterField($element, $value);
	}

	public static function getFonts($params, $key = 'fonts')
	{
		$require = array();
		$fonts   = $params->get($key, null);

		if (!empty($fonts) && is_object($fonts))
		{
			$templateStyle = Factory::getApplication()->getTemplate(true)->id;

			foreach ($fonts as $font)
			{
				$font = new Registry($font);
				$load_in_templates = (array) $font->get('load_in_templates');

				if (
					$font->get('active', 0) === 1
					&& ($import_line = $font->get('import_line', ''))
					&& (empty($load_in_templates) || in_array($templateStyle, $load_in_templates))
				){
					$require[] = $import_line;
				}
			}
		}
		sort($require);
		return $require;
	}

	/**
	Google returned a CSS with URLs to web font files.
	SVG files don't have a file extender but query and fragment
	https://fonts.gstatic.com/l/font?kit=KFOmCnqEu92Fr1Mu4mxN&skey=a0a0114a1dcab3ac&v=v18#Roboto
	Thus I have to create a filename for the replacement of google URL with local path.
	
	Addition: https://fonts.googleapis.com/css?family=Inconsolata&text=Hello%20World
	returns a similiar response:
	src: ..., url(https://fonts.gstatic.com/l/font?kit=QldKNThLqRwH-OJ1UHjlKFlb9KVOj66UafhJuQ&skey=20fa6569a31c71ee&v=v17) format('woff2');
	That's a woff2 file!
	*/
	public static function check4svg($urlGoogle, $src)
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
			 $ext = trim($matches[1], '"\'');
			}
			$fontFile = $path . '/' . md5($query) . $fragment . '.' .$ext;
			return array($fontFile, $fragment ? '#' . $fragment : '');
		}
		else
		{
			return array($path, '');
		}
	}

	public static function log($data)
	{
		$logFile = PlgSystemImportFontsGhsvs::$logFile;
		
		if ($logFile)
		{
			$data = PlgSystemImportFontsGhsvs::removeJPATH_SITE(strip_tags($data));
			
			$lines = array();

			if (is_file($logFile))
			{
				$lines = file($logFile);
				$lines = array_map('TRIM', $lines);
			}
			
			if (!in_array($data, $lines))
			{
				$date = '--DATE: ' . date('Y-m-d', time());

				if (!in_array($date, $lines))
				{
					file_put_contents($logFile, $date . "\n", FILE_APPEND);
				}
				file_put_contents($logFile, $data . "\n", FILE_APPEND);
			}
		}
	}

	public static function getFolderSize($dir)
	{
    $folderSize = self::folderSize($dir);
		$folderSize = HTMLHelper::_('number.bytes', $folderSize);
		$folderSize = array($folderSize, self::$fileCount);
		self::$fileCount = 0;
    return $folderSize;
	}

	/*
	https://gist.github.com/eusonlito/5099936
	*/
	protected static function folderSize($dir)
	{
    $bytes = 0;

    foreach (glob(rtrim($dir, '/') . '/*', GLOB_NOSORT) as $each)
		{	
			self::$fileCount++;
			$bytes += is_file($each) ? (int) filesize($each) : (int) self::folderSize($each);
    }
    return $bytes;
	}

	public static function renewal($fontPath, $renewalLog)
	{
		$fontPath = JPATH_SITE . '/' . $fontPath;

		if (Folder::exists($fontPath))
		{
			Folder::delete($fontPath);
		}

		if (Folder::exists($fontPath))
		{
			$msg = Text::sprintf('PLG_SYSTEM_IMPORTFONTSGHSVS_ERROR_DELETE_FONT_PATH',
				PlgSystemImportFontsGhsvs::removeJPATH_SITE($fontPath));
			return $msg;
		}
		
		if (!File::write($renewalLog, time()))
		{
			$msg = Text::sprintf('PLG_SYSTEM_IMPORTFONTSGHSVS_ERROR_WRITE_RENEWAL_FILE',
				PlgSystemImportFontsGhsvs::removeJPATH_SITE($renewalLog));
			return $msg;
		}
    return true;
	}
}
