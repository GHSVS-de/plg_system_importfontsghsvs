<?php
/*
GHSVS 2019-02-01
Usage:
<field name="LongDescription001" 
		type="plgSystemHyphenateGhsvs.longdescription" // Mandatory.
		hiddenLabel="true" // Recommended.
		additionalClass="optional irgendwas" // Optional-
		descriptiontext="PLG_SYSTEM_PLGSYSTEMHYPHENATEGHSVS_XML_DESCRIPTION_LONG" // mandatory.
		contentToggler="true" // Optional. Hide the Description plus a show/hide button.
		contentTogglerLabel="WAHT_EVER_STRING" // Optional. Default PLG_HYPHENATEGHSVS_SHOW_HIDE_BUTTON
		/>
		
Language string/descriptiontext can contain something like

{INCLUDE-FILE:/plugins/system/hyphenateghsvs/LICENSE_Hyphenator.txt}

Start with "/" if file is somewhere in the JoomlaROOT and not in /language/.
Just file name 
{INCLUDE-FILE:LICENSE_Hyphenator.txt}
if file is in language/xy-XY/ folder.

if you don't want nl2br for thr output:
{INCLUDE-FILE:plugins/system/hyphenateghsvs/LICENSE_Hyphenator.txt:no-nl}
or shortcut (just trailing colon ':')
{INCLUDE-FILE:plugins/system/hyphenateghsvs/LICENSE_Hyphenator.txt:}

to include a text file.addParams

Language string/descriptiontext can contain something like
{HEAD-LINE:Setting <code>Fallback Languages</code> <b>(Hyphenopoly)</b>:}
Only 1 occurence is allowed.
To create a headline. 
*/

defined('JPATH_PLATFORM') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\FormField;
#use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\FileLayout;

class plgSystemImportFontsGhsvsFormFieldLongDescription extends FormField
{
	protected $type = 'longdesription';

	protected $renderLayout = 'ghsvs.renderfield';

	protected $myLayoutPath = 'plugins/system/importfontsghsvs/layouts';
	
	protected $myLanguagePath = 'plugins/system/importfontsghsvs/language';

	// Zeigt Render-Pfade der Felder-Layouts, if TRUE.
	protected $debugLayouts = false;
	
	protected static $loaded;
	
	protected function getInput()
	{
		$additionalClass = isset($this->element['additionalClass'])
			? (string) $this->element['additionalClass'] : '';

		$descriptiontext = Text::_((string) $this->element['descriptiontext']);

		$contentToggler = $this->element['contentToggler'] ?? '';

		$additionalClass = !$contentToggler ?: $additionalClass . ' withContentToggler';
		
		$contentTogglerLabel = isset($this->element['contentTogglerLabel'])
			? (string) $this->element['contentTogglerLabel'] : 'PLG_SYSTEM_IMPORTFONTSGHSVS_SHOW_HIDE_BUTTON';
		
		$contentTogglerLabel = Text::_($contentTogglerLabel);

		// Easier handling for License texts etc.:
		if (strpos($descriptiontext, '{INCLUDE-FILE:') !== false)
		{
			preg_match_all('/{INCLUDE-FILE:([^}]+)}/', $descriptiontext, $matches);

			if (!empty($matches[1]))
			{
				foreach ($matches[1] as $key => $file)
				{
					$no_nl = explode(':', $file);

					if (strpos($no_nl[0], '/') === 0)
					{
					 $content = file_get_contents(JPATH_SITE . '/' . $no_nl[0]);
					}
					else
					{
						$lang = Factory::getLanguage();
						$paths = array(
							JPATH_SITE . '/' . $this->myLanguagePath . '/' . $lang->getTag(),
							JPATH_SITE . '/' . $this->myLanguagePath . '/' . $lang->getDefault(),
						);
						foreach ($paths as $path)
						{
							$path .= '/' . $no_nl[0];
							if (false !== ($content = @file_get_contents($path)))
							{
								break;
							}
						}
					}
					
					// No trailing : means do nl2br.
					if (!isset($no_nl[1]))
					{
						$content = nl2br($content);
					}
					$descriptiontext = str_replace($matches[0][$key], $content, $descriptiontext);
				}
			}
		}

		$headline = '';
		if (strpos($descriptiontext, '{HEAD-LINE:') !== false)
		{
			preg_match('/{HEAD-LINE:([^}]+)}/', $descriptiontext, $matches);

			if (!empty($matches[1]))
			{
				$headline = '<span class=headline>' . $matches[1] . '</span> ';
				$descriptiontext = str_replace($matches[0], '', $descriptiontext);
			}
		}
		unset($matches);

		$html = array('<div class="longdesription descriptiontext ' . $additionalClass . '">');
		$html[] = $headline;

		if ($contentToggler)
		{
			$target = $this->id . 'contentToggler';
			
			// Don't use childs like icon SPANs here because reading data-togglerContent
			// fails then if you click on the child. That's why I love JQuery.
			$html[] = '<button type="button" class=contentToggler data-togglerContent=' . $target . '>';
			$html[] = $contentTogglerLabel;
			$html[] = '</button>';
			$html[] = '<div class=togglerContent id=' . $target . '>';
		}
		$html[] = $descriptiontext;
		
		if ($contentToggler)
		{
			$html[] = '</div>';
		}
		$html[] = '</div>';

		if ($contentToggler && !self::$loaded)
		{
			$js = str_replace(array("\n", "\t"), '', ';document.addEventListener("click", function(event)
{
	var element = event.target;
	var content = document.getElementById(element.getAttribute("data-togglercontent"));
	if (content)
	{
		event.preventDefault();
		content.classList.toggle("isVisible");
	}
}, false);');
			$css = str_replace(array("\n", "\t", " "), '', '.togglerContent
{
	display: none;
}

.togglerContent.isVisible
{
	display: block;
}');
			$document = Factory::getDocument();
			$document->addScriptDeclaration($js);
			$document->addStyleDeclaration($css);
			self::$loaded = 1;
		}
		
		return implode('', $html);
	}
	
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
