<?php

defined('V3S3') or die('access denied');

class translator {
	private $translations = [];
	private $strings = [];
	public function __construct() {
		if ($modules = opendir(DIR_MODULES)) {
			while (false !== ($module = readdir($modules))) {
				if(($module == '.') || ($module == '..') || !is_dir(DIR_MODULES.DS.$module)) {
					continue;
				}
				if($languages = opendir(DIR_MODULES.DS.$module.DS.'Language')) {
					while (false !== ($lang = readdir($languages))) {
						if (($lang == '.') || ($lang == '..') || !is_dir(DIR_MODULES.DS.$module.DS.'Language'.DS.$lang)) {
							continue;
						}
						if($files = opendir(DIR_MODULES.DS.$module.DS.'Language'.DS.$lang)) {
							while (false !== ($file = readdir($files))) {
								if (is_file($langfile = DIR_MODULES.DS.$module.DS.'Language'.DS.$lang.DS.$file)) {
									if(empty($this->translations[$lang])) {
										$this->translations[$lang] = [];
									}
									$this->translations[$lang] = array_replace($this->translations[$lang], include($langfile));
								}
							}

							closedir($files);
						}
					}

					closedir($languages);
				}
			}

			closedir($modules);
		}

		if(empty($_COOKIE['language'])) {
			$this->strings = $this->translations['en_US'];
		} else {
			switch($_COOKIE['language']) {
				case 'en_US':
				default:
				$this->strings = $this->translations['en_US'];
					break;
			}
		}
	}

	public function translate($key) {
		return (isset($this->strings[$key])?$this->strings[$key]:$key);
	}
}