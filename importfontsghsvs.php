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
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
#use Joomla\CMS\Form\FormHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\Filesystem\Folder;
#use Joomla\Filesystem\File;
use Joomla\Registry\Registry;
use Joomla\Utilities\ArrayHelper;


JLoader::register('PlgImportFontsGhsvsHelper', __DIR__ . '/Helper/helper.php');
JLoader::register('PlgImportFontsGhsvsCssparser', __DIR__ . '/Helper/Cssparser.php');

class PlgSystemImportFontsGhsvs extends CMSPlugin
{
	protected $app;
	protected $autoloadLanguage = true;
	protected static $basepath = 'plg_system_importfontsghsvs';

	protected $execute = null;
	protected $fontPath = null;
	protected $renewalLog = null;
	protected $log = null;
	public static $logFile = null;
	protected $silent = null;

	// Custom subform extending field to clean by filter="something" when saving.
	private $usedSubforms = array(
		// subformFieldName => xml file (without .xml)
		'fonts' => 'fonts-subform'
	);

	// Marker in params to identify myself in back-end.
	private $meMarker = '"importfontsghsvsplugin":"1"';

	public static $import_lineCheck = 'https://fonts.googleapis.com/css?family=';

	public function onBeforeCompileHead()
	{
		if (!$this->goOn())
		{
			return;
		}

		$firstDate  = @file_get_contents($this->renewalLog);

		if (time() > ((int) $firstDate + $this->params->get('renewal', 30) * 24 * 60 * 60))
		{
			$success = PlgImportFontsGhsvsHelper::renewal($this->fontPath, $this->renewalLog);

			if ($success !== true)
			{
				if ($this->silent === 0)
				{
					$this->app->enqueueMessage($success, 'error');
				}
				
				if ($this->log)
				{
					PlgImportFontsGhsvsHelper::log($success);
				}
				return;
			}
		}

		if (!($fonts = PlgImportFontsGhsvsHelper::getFonts($this->params)))
		{
			if ($this->log)
			{
				PlgImportFontsGhsvsHelper::log(
					Text::sprintf('PLG_SYSTEM_IMPORTFONTSGHSVS_ERROR_NO_ACTIVE_FONT_LINK_FOUND')
				);
			}
			$this->goOn(true, false);
			return;
		}

		$combine    = array();
		$cssPath    = $this->fontPath . '/css';
		$fallbacks  = $fonts;
		$hash       = md5(Factory::getConfig()->get('secret'));
		// Extraction pattern for 'url(...)' parts in 'src' value.
		$urlPartPattern = '/url\(([^)]+)\)/';
		
		if ($this->params->get('runStandardAgents', 0) === 1)
		{
			$userAgents = array(
				// 'me' => $this->app->client->userAgent,
				'eot' => 'Mozilla/5.0 (compatible; MSIE 8.0; Windows NT 6.1; Trident/4.0)',
				'woff' => 'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:27.0) Gecko/20100101 Firefox/27.0',
				'woff2' => 'Mozilla/5.0 (Windows NT 6.3; rv:39.0) Gecko/20100101 Firefox/39.0',
				'svg' => 'Mozilla/4.0 (iPad; CPU OS 4_0_1 like Mac OS X) AppleWebKit/534.46 (KHTML, like Gecko) Version/4.1 Mobile/9A405 Safari/7534.48.3'
			);
		}
		else
		{
			$userAgents  = array($this->app->client->userAgent);
		}

		foreach ($userAgents as $userAgent)
		{
			$context    = array(
				'http' => array(
					'header' => 'User-Agent: ' . $userAgent,
				)
			);
			
			// For saving as comment in CSS file.
			$saveUserAgent = '';

			if ($this->params->get('writeAgentInCssFile', 0) === 1)
			{
				$saveUserAgent = '/* ' . str_replace(array('/*', '*/'), array('|*', '*|'), $userAgent) . " */\n";
			}
			
			$userAgent  = base64_encode($userAgent);
	
			foreach ($fonts as $fontKey => $fontArray)
			{
				$font = $fontArray['import_line'];
				
				
				$name = md5($hash . '-' . $userAgent . '-' . base64_encode($font) . '-' . self::$basepath) . '.css';
	
				//$cssRel = Uri::root(true) . $cssPath . '/' . $name;
				$cssAbs = JPATH_SITE . '/' . $cssPath . '/' . $name;
	
				// CSS exists already.
				if (file_exists($cssAbs))
				{
					$combine[] = file_get_contents($cssAbs);
					//HTMLHelper::_('stylesheet', $cssRel);
					unset($fallbacks[$fontKey]);
					continue;
				}
	
				/* Google Request necessary.
				   Get the basic CSS. Extract font path. Save font and manipulated CSS locally. */
				$response = @file_get_contents($font, false, stream_context_create($context));

				if ($response === false || !is_string($response) || !($response = trim($response)))
				{
					if ($this->log)
					{
						PlgImportFontsGhsvsHelper::log(
							Text::sprintf('PLG_SYSTEM_IMPORTFONTSGHSVS_ERROR_EMPTY_GOOGLEAPIS_RESPONSE', $font)
						);
					}
					continue;
				}

				// Extract specific parts from the received CSS.
				$cssparser = new PlgImportFontsGhsvsCssparser;
				$success   = $cssparser->read_from_string($response);

				if (!$success)
				{
					if ($this->log)
					{
						PlgImportFontsGhsvsHelper::log(
							Text::sprintf('PLG_SYSTEM_IMPORTFONTSGHSVS_ERROR_INADEQUATE_GOOGLEAPIS_RESPONSE',
								$font, __LINE__)
						);
					}
					continue;
				}
				
				// Get all @font-face blocks with 'src: ...' parts.
				if (!($parents = $cssparser->find_parent_by_property('src')))
				{
					if ($this->log)
					{
						PlgImportFontsGhsvsHelper::log(
							Text::sprintf('PLG_SYSTEM_IMPORTFONTSGHSVS_ERROR_INADEQUATE_GOOGLEAPIS_RESPONSE',
								$font, __LINE__)
						);
					}
					continue;
				}
				
				// For final paranoia cleanup.
				$foundGoogleUrls = array();
				
				// Identify and save font files and prepared CSS locally.
				foreach ($parents as $key => $fontFace)
				{
					// Extract google url of font.
					preg_match($urlPartPattern, $fontFace['src'], $matches);
					
					// Index paranoia.
					if (empty($matches[1]))
					{
						if ($this->log)
						{
							PlgImportFontsGhsvsHelper::log(
								Text::sprintf('PLG_SYSTEM_IMPORTFONTSGHSVS_ERROR_COULD_NOT_IDENTIFY_FONT_FILE',
									$font, __LINE__)
							);
						}
						continue;
					}
					$parents[$key]['urlGoogle'] = trim($matches[1], '"\'');

					if ($parents[$key]['urlGoogle'])
					{
						$foundGoogleUrls[] = $parents[$key]['urlGoogle'];
					}
					else
					{
						if ($this->log)
						{
							PlgImportFontsGhsvsHelper::log(
								Text::sprintf('PLG_SYSTEM_IMPORTFONTSGHSVS_ERROR_COULD_NOT_IDENTIFY_FONT_FILE',
									$font, __LINE__)
							);
						}
						continue;
					}

					// Get/Create font filepath and filename for local saving ($fontFile[0]).
					// Get fragment (#Roboto) if present ($fontFile[1]). E.g.needed for SVG.
					$fontFile = PlgImportFontsGhsvsHelper::check4svg(
						$parents[$key]['urlGoogle'],
						$parents[$key]['src']
					);
	
					if ($fontFile === false)
					{
						if ($this->log)
						{
							PlgImportFontsGhsvsHelper::log(
								Text::sprintf('PLG_SYSTEM_IMPORTFONTSGHSVS_ERROR_COULD_NOT_IDENTIFY_FONT_FILE',
									$font, __LINE__)
							);
						}
						continue;
					}

					/* Exchange google url with loacal one.
					   Do this as early as possible for paranoid users to go sure that google is away
					   even if something fails below. */
					$localUrl = '"' . Uri::root(true) . '/' . $this->fontPath
						. $fontFile[0] . $fontFile[1] . '"';
					$response = str_replace($parents[$key]['urlGoogle'], $localUrl, $response, $count);

					if ($this->log && !$count)
					{
						PlgImportFontsGhsvsHelper::log(
							Text::sprintf('PLG_SYSTEM_IMPORTFONTSGHSVS_ERROR_COULD_NOT_REPLACE_GOOGLE_URL',
								$font)
						);
					}

					// Download and save font file if not already exists.
					$localFile = JPATH_SITE . '/' . $this->fontPath . $fontFile[0];

					if (!is_file($localFile))
					{
						$downloadedFont = @file_get_contents($parents[$key]['urlGoogle']);

						if (empty($downloadedFont))
						{
							if ($this->log)
							{
								PlgImportFontsGhsvsHelper::log(
									Text::sprintf('PLG_SYSTEM_IMPORTFONTSGHSVS_ERROR_FONT_DOWNLOAD_FAILED',
										$font, $parents[$key]['urlGoogle'])
								);
							}
							continue;
						}

						if (!File::write($localFile, $downloadedFont))
						{
							if ($this->log)
							{
								PlgImportFontsGhsvsHelper::log(
									Text::sprintf('PLG_SYSTEM_IMPORTFONTSGHSVS_ERROR_FONT_SAVE_FAILED',
										$font, $localFile)
								);
							}
							continue;
						}
					}
				} // end - foreach ($parents as $key => $fontFace)

				// Hard core cleanup.
				$response = str_replace($foundGoogleUrls, '', $response);

				$response = $saveUserAgent . $cssparser->cleanString($response);
	
				if (!File::write($cssAbs, $response) && $this->log)
				{
					PlgImportFontsGhsvsHelper::log(
						Text::sprintf('PLG_SYSTEM_IMPORTFONTSGHSVS_ERROR_CSS_SAVE_FAILED',
							$font, $cssAbs)
					);
				}
				$combine[] = $response;
				unset($fallbacks[$fontKey]);
			} //foreach ($fonts as $fontKey => $font)
		} //foreach ($userAgents as $userAgent)

		if ($fallbacks)
		{
			if ($this->log)
			{
				foreach ($fallbacks as $fallbackItem)
				{
					PlgImportFontsGhsvsHelper::log(
						Text::sprintf('PLG_SYSTEM_IMPORTFONTSGHSVS_ERROR_FALLBACKS',
							implode(', ', $fallbackItem))
					);
				}
			}

			if ($this->params->get('fallback', 0) === 1)
			{
				$fallbackImports = array();
				
				foreach ($fallbacks as $key => $fallbackItem)
				{
					$fallbackImports[$key] = "@import url('" . $fallbackItem['import_line'] . "')";
				}
				$combine[] = implode(';', $fallbackImports);
			}
		}

		if ($combine)
		{
			Factory::getDocument()->addStyleDeclaration(implode('', $combine));
		}
	}

	public function onExtensionBeforeSave($context, $table, $isNew, $data = array())
	{
		// Sanitize subform fields and some special cleanups for plg_system_importfontsghsvs.
		if (
			$this->app->isClient('administrator')
			&& $context === 'com_plugins.plugin'
			&& !empty($table->params) && is_string($table->params)
			&& strpos($table->params, $this->meMarker) !== false
			&& !empty($this->usedSubforms)
			// && $table->element === $this->_name && $table->folder ===  $this->_type
		){
			$do = false;
			$excludeTypes = array(
				//'filelist'
			);

			foreach ($this->usedSubforms as $fieldName => $file)
			{
				$formFields  = array();
				$params      = new Registry($table->params);
				$subformData = $params->get($fieldName);
				$file        = __DIR__ . '/myforms/' . $file . '.xml';
				
				if (
					!is_object($subformData) || !count(get_object_vars($subformData))
					|| !is_file($file) 
				)
				{
					continue;
				}

				$subform = new PlgImportFontsGhsvsHelper('dummy');
				$subform->loadFile($file);
				$xml = $subform->getXml();
				$fieldsAsXMLArray = $xml->xpath('//field[@name=@name and not(ancestor::field/form/*)]');

				foreach ($fieldsAsXMLArray as $field)
				{
					if (in_array((string) $field->attributes()->type, $excludeTypes))
					{
						continue;
					}

					$formFields[(string) $field->attributes()->name] = $field;
				}

				foreach ($subformData as $key => $item)
				{
					foreach ($item as $property => $value)
					{
						if (array_key_exists($property, $formFields))
						{
							// Special for plg_system_importfontsghsvs
							if ($property === 'import_line')
							{
								$value = trim($value);
								$value = rtrim($value, ';');
								$value = str_replace(' ', '',$value);
								$value = str_replace(array('"', "'"), '', $value);
								$value = str_replace('&amp;', '&', $value);
								$value = str_replace('http://', 'https://', $value);
								$value = $subform->filterField($formFields[$property], $value);
								
								if (strpos($value, self::$import_lineCheck) !== 0)
								{
									$this->app->enqueueMessage(
										Text::sprintf('PLG_SYSTEM_IMPORTFONTSGHSVS_ERROR_NO_GOOGLEAPIS_URL',
											$value, self::$import_lineCheck), 'error'
									);
									return false;
								}
								
								$family = trim(Uri::getInstance($value)->getVar('family'));
								
								if (empty($family))
								{
									$msg = Text::sprintf('PLG_SYSTEM_IMPORTFONTSGHSVS_ERROR_NO_FAMILY',
											$value, self::$import_lineCheck);

									$this->app->enqueueMessage($msg, 'error');
									return false;
								}
								$subformData->$key->$property = $value;
								continue;
							}
							$subformData->$key->$property = $subform->filterField($formFields[$property], $value);
						}
					}
				}

				$collectItems = array();
				$x = 0;
				
				// Split family= with several fonts (Rooto|Tahoma) into several googleapis links.
				foreach ($subformData as $item)
				{
					$uri              = Uri::getInstance($item->import_line);
					$familyParameter  = $uri->getVar('family');

					if (strpos($familyParameter, '|') !== false)
					{
						$families  = explode('|', $familyParameter);
						$families  = array_map('TRIM', $families);
						$families_ = $families;

						foreach ($families as $k => $singleFamily)
						{
							if (!$singleFamily)
							{
								unset($families[$k]);
								continue;
							}

							$newItem = clone $item;
							$families[$k] = urlencode($singleFamily);
							$uri->setVar('family', $families[$k]);
							$newItem->import_line = $uri->toString();
							$newItem->family = $singleFamily;
							$collectItems['font' . $x] = $newItem;
							++$x;
						}

						if (count($families) > 1)
						{
							$this->app->enqueueMessage(
								Text::sprintf('PLG_SYSTEM_IMPORTFONTSGHSVS_IMPORT_LINE_SPLITTED',
									implode('|', $families)), 'notice'
							);
						}
						else
						{
							$this->app->enqueueMessage(
								Text::sprintf('PLG_SYSTEM_IMPORTFONTSGHSVS_IMPORT_LINE_CHECK_CLEANED',
									$item->import_line, implode('|', $families_)), 'notice'
							);
						}
					}
					else
					{
						$collectItems['font' . $x] = clone $item;
						$family = explode(':', $familyParameter)[0];
						$collectItems['font' . $x]->family = $family;
						++$x;
					}
				}

				$subformData = (object) $collectItems;
				$params->set($fieldName, $subformData);
				$do = true;
			} // foreach $this->usedSubforms

			if ($do)
			{
				$table->params = $params->toString();
			}
		}
	}

	protected function goOn($refresh = false, $force = null)
	{
		if (is_null($this->execute) || $refresh === true)
		{
			if (
				!$this->app->isClient('site')
				|| ($this->app->isClient('site') && !$this->params->get('frontendon', 0))
				|| (!$this->params->get('robots', 0) && $this->app->client->robot)
				|| $this->app->getDocument()->getType() !== 'html'
			){
				$this->execute = false;
			}
			else
			{
				$this->execute = is_bool($force) ? $force : true;
			}
			$this->fontPath   = 'media/fontsghsvs';
			$this->renewalLog = JPATH_SITE . '/' . $this->fontPath . '/renewal.log';
			$this->log = $this->params->get('log', 0);
			self::$logFile = $this->app->get('log_path') . '/' . self::$basepath . '-log.txt';
			$this->silent = $this->params->get('silent', 1);
		}
		return $this->execute;
	}

	public static function removeJPATH_SITE($str)
	{
		return str_replace(JPATH_SITE, '', $str);
	}

	public function onAjaxPlgSystemImportFontsGhsvsDeleteRenewalFile()
	{
		if (!$this->isAllowedUser() || !$this->isAjaxRequest())
		{
			throw new Exception(JText::_('JGLOBAL_AUTH_ACCESS_DENIED'), 403);
		}
		$this->goOn();

		$success = PlgImportFontsGhsvsHelper::renewal($this->fontPath, $this->renewalLog);

		if ($success !== true)
		{
			$html = $success;
		}
		else
		{
			$renewalLog = self::removeJPATH_SITE($this->renewalLog);
			$html = Text::sprintf('PLG_SYSTEM_IMPORTFONTSGHSVS_BUTTON_RENEWAL_FORCE_SUCCESS',
				$this->fontPath,
				self::removeJPATH_SITE($this->renewalLog)
			);
		}
		echo json_encode(array('html' => $html));
	}

	public function onAjaxPlgSystemImportFontsGhsvsFolderSize()
	{
		if (!$this->isAllowedUser() || !$this->isAjaxRequest())
		{
			throw new Exception(JText::_('JGLOBAL_AUTH_ACCESS_DENIED'), 403);
		}
		$this->goOn();
		$result = PlgImportFontsGhsvsHelper::getFolderSize(JPATH_SITE . '/' . $this->fontPath);
		$html   = Text::sprintf(
			'PLG_SYSTEM_IMPORTFONTSGHSVS_BUTTON_FOLDER_SIZE_RESULT',
			$result[0], $result[1], $this->fontPath
		);
		echo json_encode(array('html' => $html));
	}

	public function onAjaxPlgSystemImportFontsGhsvsDeleteLogFile()
	{
		if (!$this->isAllowedUser() || !$this->isAjaxRequest())
		{
			throw new Exception(JText::_('JGLOBAL_AUTH_ACCESS_DENIED'), 403);
		}
		$this->goOn();
		$file = self::$logFile;

		if (is_file($file))
		{
			$deleted = @unlink($file);
		}
		else
		{
			$deleted = true;
		}
		$file = self::removeJPATH_SITE($file);

		if (!$deleted)
		{
			$html = Text::sprintf('PLG_SYSTEM_IMPORTFONTSGHSVS_BUTTON_FILE_DELETE_ERROR', $file);
		}
		else
		{
			$html = Text::sprintf('PLG_SYSTEM_IMPORTFONTSGHSVS_BUTTON_FILE_DELETE_SUCCESS', $file);
		}
		echo json_encode(array('html' => $html));
	}

	/**
	 * Display content of log file
	*/
	public function onAjaxPlgSystemImportFontsGhsvsShowLogFile()
	{
		if (!$this->isAllowedUser() || !$this->isAjaxRequest())
		{
			throw new Exception(JText::_('JGLOBAL_AUTH_ACCESS_DENIED'), 403);
		}
		$this->goOn();
		$filePath = self::$logFile;
		$file = @file_get_contents($filePath);
		$filePath = self::removeJPATH_SITE($filePath);

		if ($file === false || !trim($file))
		{
			$html = Text::sprintf('PLG_SYSTEM_IMPORTFONTSGHSVS_BUTTON_FILE_SHOW_CONTENT_EMPTY', $filePath);;
		}
		else
		{
			$html = '** CONTENT OF FILE ' . $filePath . " **\n\n" . $file;
		}
		echo json_encode(array('html' => $html));
	}

	/**
	 * Show path and size and download of log file
	*/
	public function onAjaxPlgSystemImportFontsGhsvsShowLogFilePath()
	{
		if (!$this->isAllowedUser() || !$this->isAjaxRequest())
		{
			throw new Exception(JText::_('JGLOBAL_AUTH_ACCESS_DENIED'), 403);
		}
		$this->goOn();
		$filesize = 0;
		$file = self::$logFile;

		if (is_file($file))
		{
			$bytes = filesize($file);
			$filesize = HTMLHelper::_('number.bytes', $bytes);
		}
		$file = self::removeJPATH_SITE($file);
		
		if (isset($bytes))
		{
			$download = JUri::root() . '/' . ltrim($file, '/');
			$download = '<a href=' . $download . ' target=_blank download>Download</a>';
		}
		echo json_encode(array('html' => 'Path: ' . $file . "\nSize: " . $filesize
			. "\nDownload: " . (isset($download) ? $download : 'No file found')));
	}

	private function isAjaxRequest()
	{
		return strtolower($this->app->input->server->get('HTTP_X_REQUESTED_WITH', ''))
			=== 'xmlhttprequest';
	}

	private function isAllowedUser()
	{
		return Factory::getUser()->authorise('core.manage');
	}
}
