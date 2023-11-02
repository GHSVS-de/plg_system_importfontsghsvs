<?php
namespace GHSVS\Plugin\System\ImportfontsGhsvs\Extension;

use Joomla\CMS\Factory;
use Joomla\CMS\Filesystem\File;

use Joomla\CMS\Filesystem\Folder;
use Joomla\CMS\Form\Form;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Uri\Uri;
use GHSVS\Plugin\System\ImportfontsGhsvs\Helper\Cssparser;
use GHSVS\Plugin\System\ImportfontsGhsvs\Helper\ImportfontsGhsvsHelper;
use GHSVS\Plugin\System\ImportfontsGhsvs\Helper\FilterFieldHelper;
use Joomla\Registry\Registry;
use Joomla\Event\DispatcherInterface;
use Exception;

\defined('_JEXEC') or die;

final class ImportfontsGhsvs extends CMSPlugin
{
	protected $autoloadLanguage = true;

	protected $basepath = 'plg_system_importfontsghsvs';

	protected $execute = null;

	protected $fontPath = null;

	/*
		File that contains renewal time stamp.
	*/
	protected $renewalLog = null;

	protected $log = null;

	protected $logFile = null;

	/**
		* The ImportfontsGhsvsHelper helper
		*
		* @var    ImportfontsGhsvsHelper
		* @since 2023.11.01
		*/
	private $helper;

	/**
		* The Cssparser helper
		*
		* @var    Cssparser
		* @since 2023.11.01
		*/
	private $cssparser;

	// Custom subform extending field to clean by filter="something" when saving.
	private $usedSubforms = [
		// subformFieldName => xml file (without .xml)
		'fonts' => 'fonts-subform',
	];

	// Marker in params to identify myself in back-end.
	private $meMarker = '"importfontsghsvsplugin":"1"';

	public $import_lineCheck = [
		'https://fonts.googleapis.com/css',
		'family=',
	];

	public function __construct(
		DispatcherInterface $dispatcher,
		array $config,
		ImportfontsGhsvsHelper $helper,
		Cssparser $cssparser
	){
		parent::__construct($dispatcher, $config);

		$this->cssparser = $cssparser;
		$this->helper = $helper;
	}

	public function onBeforeCompileHead()
	{
		if (!$this->goOn())
		{
			return;
		}

		$firstDate = 0;

		if (!empty($this->renewalLog) && is_file($this->renewalLog))
		{
			$firstDate = file_get_contents($this->renewalLog);
		}

		if (time() > ((int) $firstDate + $this->params->get('renewal', 30) * 24 * 60 * 60))
		{
			$success = $this->helper->renewal(
				$this->fontPath,
				$this->renewalLog
			);

			if ($success !== true)
			{
				if ($this->log)
				{
					$this->helper->log($success);
				}

				return;
			}
		}

		if (!($fonts = $this->helper->getFonts($this->params)))
		{
			if ($this->log)
			{
				$this->helper->log(
					Text::sprintf('PLG_SYSTEM_IMPORTFONTSGHSVS_ERROR_NO_ACTIVE_FONT_LINK_FOUND')
				);
			}
			// goOn($refresh = false, $force = null)
			$this->goOn(true, false);

			return;
		}

		$combine    = [];
		$cssPath    = $this->fontPath . '/css';
		$fallbacks  = $fonts;
		$hash       = md5($this->getApplication()->get('secret'));
		// Extraction pattern for 'url(...)' parts in 'src' value.
		$urlPartPattern = '/url\(([^)]+)\)/';

		// Kept here ONLY for debugging purposes of author.
		if ($this->params->get('runStandardAgents', 0) === 100)
		{
			$userAgents = [
				// 'me' => $this->getApplication()->client->userAgent,
				'eot' => 'Mozilla/5.0 (compatible; MSIE 8.0; Windows NT 6.1; Trident/4.0)',
				'woff' => 'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:27.0) Gecko/20100101 Firefox/27.0',
				'woff2' => 'Mozilla/5.0 (Windows NT 6.3; rv:39.0) Gecko/20100101 Firefox/39.0',
				'svg' => 'Mozilla/4.0 (iPad; CPU OS 4_0_1 like Mac OS X) AppleWebKit/534.46 (KHTML, like Gecko) Version/4.1 Mobile/9A405 Safari/7534.48.3',
				'ttf' => 'Mozilla/5.0 (Unknown; Linux x86_64) AppleWebKit/538.1 (KHTML, like Gecko) Safari/538.1 Daum/4.1',
			];
		}
		else
		{
			$userAgents  = [$this->getApplication()->client->userAgent];
		}

		// Curl options.
		$options = [
			'referer' => $_SERVER['REQUEST_URI'],
		];

		foreach ($userAgents as $userAgent)
		{
			// Original $userAgent for curl.
			$userAgent_ = $userAgent;

			// "allow_url_fopen options".
			$context    = [
				'http' => [
					'header' => 'User-Agent: ' . $userAgent,
					'method' => 'GET',
				],
			];

			// Save UserAgent as comment in CSS file.
			$saveUserAgent = '';

			if ($this->params->get('writeAgentInCssFile', 0) === 1)
			{
				$saveUserAgent = '/* ' . str_replace(['/*', '*/'], ['|*', '*|'], $userAgent) . " */\n";
			}

			$userAgent  = base64_encode($userAgent);

			foreach ($fonts as $fontKey => $fontArray)
			{
				$font = $fontArray['import_line'];
				$name = md5($hash . '-' . $userAgent . '-' . base64_encode($font) . '-' . $this->basepath) . '.css';
				$cssAbs = JPATH_SITE . '/' . $cssPath . '/' . $name;

				// CSS exists already.
				if (file_exists($cssAbs))
				{
					$combine[] = file_get_contents($cssAbs);
					unset($fallbacks[$fontKey]);
					continue;
				}

				if (ini_get('allow_url_fopen'))
				{
					/* Google Request necessary.
						Get the basic CSS. Extract font path. Save font and manipulated CSS
						locally. */
					$response = @file_get_contents($font, false, stream_context_create($context));

					if ($response === false || !is_string($response) || !($response = trim($response)))
					{
						if ($this->log)
						{
							$this->helper->log(
								Text::sprintf(
									'PLG_SYSTEM_IMPORTFONTSGHSVS_ERROR_EMPTY_GOOGLEAPIS_RESPONSE',
									$font
								)
							);
						}
						continue;
					}
				}
				elseif (function_exists('curl_init') && function_exists('curl_exec'))
				{
					$ch = curl_init();
					curl_setopt($ch, CURLOPT_URL, $font);
					curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
					curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20);
					curl_setopt($ch, CURLOPT_TIMEOUT, 30);
					curl_setopt($ch, CURLOPT_USERAGENT, $userAgent_);

					if (!empty($options['referer']))
					{
						curl_setopt($ch, CURLOPT_REFERER, $options['referer']);
					}
					$response = curl_exec($ch);

					$error = curl_error($ch);
					$curlErrorNumber = curl_errno($ch);
					curl_close($ch);

					if (
						$curlErrorNumber
						|| empty($response)
						|| !is_string($response)
						|| !($response = trim($response))
					) {
						if ($this->log)
						{
							$this->helper->log(
								Text::sprintf('PLG_SYSTEM_IMPORTFONTSGHSVS_ERROR_EMPTY_GOOGLEAPIS_RESPONSE', $font)
							);
							$this->helper->log(
								'Curl request failed with ' . $curlErrorNumber . ':' . $error
							);
						}

						continue;
					}
				}
				else
				{
					if ($this->log)
					{
						$this->helper->log(
							'Can\'t request data from Google because neither allow_url_fopen nor curl_init/curl_exec is activated on your server.'
						);
					}

					continue;
				}

				// Extract specific parts from the received CSS.
				$success   = $this->cssparser->read_from_string($response);

				if (!$success)
				{
					if ($this->log)
					{
						$this->helper->log(
							Text::sprintf(
								'PLG_SYSTEM_IMPORTFONTSGHSVS_ERROR_INADEQUATE_GOOGLEAPIS_RESPONSE',
								$font,
								__LINE__
							)
						);
					}
					continue;
				}

				// Get all @font-face blocks with 'src: ...' parts.
				if (!($parents = $this->cssparser->find_parent_by_property('src')))
				{
					if ($this->log)
					{
						$this->helper->log(
							Text::sprintf(
								'PLG_SYSTEM_IMPORTFONTSGHSVS_ERROR_INADEQUATE_GOOGLEAPIS_RESPONSE',
								$font,
								__LINE__
							)
						);
					}
					continue;
				}

				// For final paranoia cleanup.
				$foundGoogleUrls = [];

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
							$this->helper->log(
								Text::sprintf(
									'PLG_SYSTEM_IMPORTFONTSGHSVS_ERROR_COULD_NOT_IDENTIFY_FONT_FILE',
									$font,
									__LINE__
								)
							);
						}
						continue;
					}
					$parents[$key]['urlGoogle'] = trim($matches[1], '"\'');

					if ($parents[$key]['urlGoogle'])
					{
						$foundGoogleUrls[$parents[$key]['urlGoogle']] = 0;
					}
					else
					{
						if ($this->log)
						{
							$this->helper->log(
								Text::sprintf(
									'PLG_SYSTEM_IMPORTFONTSGHSVS_ERROR_COULD_NOT_IDENTIFY_FONT_FILE',
									$font,
									__LINE__
								)
							);
						}
						continue;
					}

					// Get/Create font filepath and filename for local saving ($fontFile[0]).
					// Get fragment (#Roboto) if present ($fontFile[1]). E.g.needed for SVG.
					$fontFile = $this->helper->check4svg(
						$parents[$key]['urlGoogle'],
						$parents[$key]['src']
					);

					if ($fontFile === false)
					{
						if ($this->log)
						{
							$this->helper->log(
								Text::sprintf(
									'PLG_SYSTEM_IMPORTFONTSGHSVS_ERROR_COULD_NOT_IDENTIFY_FONT_FILE',
									$font,
									__LINE__
								)
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

					$foundGoogleUrls[$parents[$key]['urlGoogle']] += $count;

					if ($this->log && !$foundGoogleUrls[$parents[$key]['urlGoogle']])
					{
						$this->helper->log(
							Text::sprintf(
								'PLG_SYSTEM_IMPORTFONTSGHSVS_ERROR_COULD_NOT_REPLACE_GOOGLE_URL',
								$font
							)
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
								$this->helper->log(
									Text::sprintf(
										'PLG_SYSTEM_IMPORTFONTSGHSVS_ERROR_FONT_DOWNLOAD_FAILED',
										$font,
										$parents[$key]['urlGoogle']
									)
								);
							}
							continue;
						}

						if (!File::write($localFile, $downloadedFont))
						{
							if ($this->log)
							{
								$this->helper->log(
									Text::sprintf(
										'PLG_SYSTEM_IMPORTFONTSGHSVS_ERROR_FONT_SAVE_FAILED',
										$font,
										$localFile
									)
								);
							}
							continue;
						}
					}
				} // end - foreach ($parents as $key => $fontFace)

				// Hard core cleanup.
				foreach ($foundGoogleUrls as $gUrl => $anzahl)
				{
					$response = str_replace($gUrl, '', $response);
				}

				$response = $saveUserAgent . $this->cssparser->cleanString($response);

				if (!File::write($cssAbs, $response) && $this->log)
				{
					$this->helper->log(
						Text::sprintf(
							'PLG_SYSTEM_IMPORTFONTSGHSVS_ERROR_CSS_SAVE_FAILED',
							$font,
							$cssAbs
						)
					);
				}
				$combine[] = $response;
				unset($fallbacks[$fontKey]);
			} //foreach ($fonts as $fontKey => $font)
		} //foreach ($userAgents as $userAgent)

		// Any fonts left?
		if ($fallbacks)
		{
			if ($this->log)
			{
				foreach ($fallbacks as $fallbackItem)
				{
					$this->helper->log(
						Text::sprintf(
							'PLG_SYSTEM_IMPORTFONTSGHSVS_ERROR_FALLBACKS',
							implode(', ', $fallbackItem)
						)
					);
				}
			}

			// User selected to insert "@import url(" with Google url for failed fonts?
			if ($this->params->get('fallback', 0) === 1)
			{
				$fallbackImports = [];

				foreach ($fallbacks as $key => $fallbackItem)
				{
					$fallbackImports[$key] = "@import url('" . $fallbackItem['import_line'] . "')";
				}
				$combine[] = implode(';', $fallbackImports);
			}
		}

		if ($combine)
		{
			Factory::getDocument()->getWebAssetManager()->addInlineStyle(
				implode('', $combine) . '',
				[],
				['name' => $this->basepath . '.combined'],
			);
		}
	}

	/**
	* $table is the part that will be saved after this routine.
	* $data is not relevant here for me.
	*/
	public function onExtensionBeforeSave($context, $table, $isNew, $data = [])
	{
		// Sanitize subform fields and some special cleanups for plg_system_importfontsghsvs.
		if (
			$this->getApplication()->isClient('administrator')
			&& $context === 'com_plugins.plugin'
			&& !empty($table->params) && is_string($table->params)
			&& strpos($table->params, $this->meMarker) !== false
			&& !empty($this->usedSubforms)
			// && $table->element === $this->_name && $table->folder ===  $this->_type
		) {
			$do = false;
			$excludeTypes = [
				//'filelist'
			];


				$constructorVariable = null;

			foreach ($this->usedSubforms as $fieldName => $file)
			{
				$formFields  = [];
				$params      = new Registry($table->params);

				// What the user has entered in the subform fields.
				$subformData = $params->get($fieldName);

				// Absolute path to subform xml.
				$file        = __DIR__ . '/../Form/' . $file . '.xml';

				if (
					!is_object($subformData) || !count(get_object_vars($subformData))
					|| !is_file($file)
				) {
					continue;
				}

				$subform = new \Joomla\CMS\Form\Form('dummy');
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

				// Walk the subform rows.
				foreach ($subformData as $key => $item)
				{
					foreach ($item as $property => $value)
					{
						// I don't know if it's placed correctly inside foreach.
						$filterFieldHelper = new FilterFieldHelper($constructorVariable);

						if (array_key_exists($property, $formFields))
						{
							// Special for plg_system_importfontsghsvs
							if ($property === 'import_line')
							{
								$value = trim($value);
								$value = rtrim($value, ';');
								$value = str_replace(' ', '', $value);
								$value = str_replace(['"', "'"], '', $value);
								$value = str_replace('&amp;', '&', $value);
								$value = str_replace('http://', 'https://', $value);
								$value = $filterFieldHelper->filterField($formFields[$property], $value);

								// There are new links (see 'css2') like fonts.googleapis.com/css2?family=
								// Therefore new check since 2020.05.19.
								$parts = explode('?', $value, 2);

								if (
									count($parts) !== 2
									|| (strpos($parts[0], $this->import_lineCheck[0]) !== 0
										&& strpos($parts[1], $this->import_lineCheck[1]) !== 0)
								) {
									$this->getApplication()->enqueueMessage(
										Text::sprintf(
											'PLG_SYSTEM_IMPORTFONTSGHSVS_ERROR_NO_GOOGLEAPIS_URL',
											$value,
											implode('?', $this->import_lineCheck)
										),
										'error'
									);

									return false;
								}

								$family = trim(Uri::getInstance($value)->getVar('family'));

								if (empty($family))
								{
									$msg = Text::sprintf(
										'PLG_SYSTEM_IMPORTFONTSGHSVS_ERROR_NO_FAMILY',
										$value
									);

									$this->getApplication()->enqueueMessage($msg, 'error');

									return false;
								}
								$subformData->$key->$property = $value;
								continue;
							}

							$subformData->$key->$property = $filterFieldHelper->filterField($formFields[$property], $value);
						}
					}
				}

				$collectItems = [];
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
							$this->getApplication()->enqueueMessage(
								Text::sprintf(
									'PLG_SYSTEM_IMPORTFONTSGHSVS_IMPORT_LINE_SPLITTED',
									implode('|', $families)
								),
								'notice'
							);
						}
						else
						{
							$this->getApplication()->enqueueMessage(
								Text::sprintf(
									'PLG_SYSTEM_IMPORTFONTSGHSVS_IMPORT_LINE_CHECK_CLEANED',
									$item->import_line,
									implode('|', $families_)
								),
								'notice'
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
				!$this->getApplication()->isClient('site')
				|| ($this->getApplication()->isClient('site') && !$this->params->get('frontendon', 0))
				|| (!$this->params->get('robots', 0) && $this->getApplication()->client->robot)
				|| $this->getApplication()->getDocument()->getType() !== 'html'
			) {
				$this->execute = false;
			}
			else
			{
				$this->execute = is_bool($force) ? $force : true;
			}
			$this->fontPath   = 'media/' . $this->basepath . '/font';
			$this->renewalLog = JPATH_SITE . '/' . $this->fontPath . '/renewal.log';
			$this->log = $this->params->get('log', 0);
			$this->logFile = $this->helper->getLogFile()['logFile'];

			if ($this->log)
			{
				$this->helper->initLogger();
				/* $this->helper->log(
					'Log wurde in function goOn aktiviert. Diese Zeile ist nur zum Debuggen.',
					\Joomla\CMS\Log\Log::INFO
				); */
			}
		}

		return $this->execute;
	}

	public function onAjaxPlgSystemImportFontsGhsvsDeleteRenewalFile()
	{
		if (!$this->isAllowedUser() || !$this->isAjaxRequest())
		{
			throw new Exception(Text::_('JGLOBAL_AUTH_ACCESS_DENIED'), 403);
		}
		$this->goOn();

		$success = $this->helper->renewal($this->fontPath, $this->renewalLog);

		if ($success !== true)
		{
			$html = $success;
		}
		else
		{
			$html = Text::sprintf(
				'PLG_SYSTEM_IMPORTFONTSGHSVS_BUTTON_RENEWAL_FORCE_SUCCESS',
				$this->fontPath,
				$this->helper->removeJPATH_SITE($this->renewalLog)
			);
		}
		echo json_encode(['html' => $html]);
	}

	public function onAjaxPlgSystemImportFontsGhsvsFolderSize()
	{
		if (!$this->isAllowedUser() || !$this->isAjaxRequest())
		{
			throw new Exception(Text::_('JGLOBAL_AUTH_ACCESS_DENIED'), 403);
		}

		$this->goOn();
		$result = $this->helper->getFolderSize(JPATH_SITE . '/' . $this->fontPath);
		$html   = Text::sprintf(
			'PLG_SYSTEM_IMPORTFONTSGHSVS_BUTTON_FOLDER_SIZE_RESULT',
			$result[0],
			$result[1],
			$this->fontPath
		);

		echo json_encode(['html' => $html]);
	}

	public function onAjaxPlgSystemImportFontsGhsvsDeleteLogFile()
	{
		if (!$this->isAllowedUser() || !$this->isAjaxRequest())
		{
			throw new Exception(Text::_('JGLOBAL_AUTH_ACCESS_DENIED'), 403);
		}

		$this->goOn();
		$file = $this->logFile;

		if (is_file($file))
		{
			$deleted = @unlink($file);
		}
		else
		{
			$deleted = true;
		}

		$file = $this->helper->removeJPATH_SITE($file);

		if (!$deleted)
		{
			$html = Text::sprintf('PLG_SYSTEM_IMPORTFONTSGHSVS_BUTTON_FILE_DELETE_ERROR', $file);
		}
		else
		{
			$html = Text::sprintf('PLG_SYSTEM_IMPORTFONTSGHSVS_BUTTON_FILE_DELETE_SUCCESS', $file);
		}
		echo json_encode(['html' => $html]);
	}

	/**
	 * Display content of log file
	*/
	public function onAjaxPlgSystemImportFontsGhsvsShowLogFile()
	{
		if (!$this->isAllowedUser() || !$this->isAjaxRequest())
		{
			throw new Exception(Text::_('JGLOBAL_AUTH_ACCESS_DENIED'), 403);
		}

		$this->goOn();
		$file = '';
		$filePath = $this->helper->removeJPATH_SITE($this->logFile);

		if (is_file($this->logFile))
		{
			$file = file_get_contents($this->logFile);
		}

		if (trim($file) === '')
		{
			$html = Text::sprintf(
				'PLG_SYSTEM_IMPORTFONTSGHSVS_BUTTON_FILE_SHOW_CONTENT_EMPTY',
				$filePath
			);
		}
		else
		{
			$html = '** CONTENT OF FILE ' . $filePath . " **\n\n" . $file;
		}

		echo json_encode(['html' => $html]);
	}

	/**
	 * Show path and size and download of log file
	*/
	public function onAjaxPlgSystemImportFontsGhsvsShowLogFilePath()
	{
		if (!$this->isAllowedUser() || !$this->isAjaxRequest())
		{
			throw new Exception(Text::_('JGLOBAL_AUTH_ACCESS_DENIED'), 403);
		}
		$this->goOn();
		$filesize = 0;
		$file = $this->logFile;

		if (is_file($file))
		{
			$bytes = filesize($file);
			$filesize = HTMLHelper::_('number.bytes', $bytes);
		}
		$file = $this->helper->removeJPATH_SITE($file);

		if (isset($bytes))
		{
			$download = Uri::root() . '/' . ltrim($file, '/');
			$download = '<a href=' . $download . ' target=_blank download>Download</a>';
		}
		echo json_encode(['html' => 'Path: ' . $file . "\nSize: " . $filesize
			. "\nDownload: " . (isset($download) ? $download : 'No file found'), ]);
	}

	private function isAjaxRequest()
	{
		return strtolower($this->getApplication()->input->server->get('HTTP_X_REQUESTED_WITH', ''))
			=== 'xmlhttprequest';
	}

	private function isAllowedUser()
	{
		return $this->getApplication()->getIdentity() && $this->getApplication()->getIdentity()->authorise('core.manage');
	}
}
