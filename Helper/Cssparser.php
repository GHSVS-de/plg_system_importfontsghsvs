<?php
/*
GHSVS 2018-05-05
https://github.com/intekhabrizvi/cssparser
aNGEPASST für ausschließliche Nutzung mit Google Web Fonts
*/
?>
<?php
defined('JPATH_BASE') or die;
class PlgImportFontsGhsvsCssparser
{
	protected $raw_css;
	protected $css;

	public function read_from_string($str)
	{
		$str = $this->cleanString($str);

		if (!empty($str))
		{
			$this->raw_css = $str;
			return $this->do_operation();
		}

		return false;
	}

	public function cleanString($str)
	{
		// Remove JS comments
		$str = preg_replace('#/\*[^/]*\*/#', '', $str);

		// Remove new lines etc.
		return trim(str_replace(array("\n", "\r", "\t"), '', $str));
	}

	private function do_operation()
	{
		preg_match_all('/(.+?)\s?\{\s?(.+?)\s?\}/', $this->raw_css, $level1);
		unset($this->raw_css);

		if (count($level1) == 3)
		{
			$parent = count($level1[1]);
			$parent_value = count($level1[2]);

			if ($parent == $parent_value)
			{
				for($i=0; $i < $parent; $i++)
				{
					//$this->css[trim($level1[1][$i])] = explode(";",trim($level1[2][$i]));
					$level2 = explode(";",trim($level1[2][$i]));

					foreach ($level2 as $l2)
					{
						if (!empty($l2))
						{
							$level3 = explode(":", trim($l2), 2);

							$this->css[$this->clean($level1[1][$i]) . '-' . $i][$this->clean($level3[0])] = $this->clean($level3[1]);

							$this->css[$level1[1][$i] . '-' . $i][$level3[0]] = $level3[1];

							unset($level3);
						}
					}
					unset($level2, $l2);
				}
			}
			else
			{
				return false;
			}
			/*echo "<pre>";
			var_dump($level1);
			var_dump($this->css);*/
			unset($level1);
		}
		else{
			return false;
		}

		return true;
	}

	public function find_parent_by_property($property)
	{
		$results  = array();
		$property = $this->clean($property);

		foreach ($this->css as $key1 => $css)
		{
			foreach ($css as $key2 => $value2)
			{
				if ($key2 == $property)
				{
					$results[$key1] = $css;
					break 1;
				}
			}
		}
		return $results;
	}

	private function clean($value)
	{
		return addslashes(trim($value));
	}
}
