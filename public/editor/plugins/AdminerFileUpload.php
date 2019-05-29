<?php
//! delete

/** Edit fields ending with "_url" by <input type="file"> and link to the uploaded files from select
* @link https://www.adminer.org/plugins/#use
* @author Jakub Vrana, http://www.vrana.cz/
* @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
* @license http://www.gnu.org/licenses/gpl-2.0.html GNU General Public License, version 2 (one or other)
*/

use Aws\S3\S3Client;
use Aws\S3\Exception\S3Exception;
use Intervention\Image\ImageManager;

class AdminerFileUpload {
	/** @access protected */
	var $uploadPath, $displayPath, $extensions;

	/**
	* @param string prefix for uploading data (create writable subdirectory for each table containing uploadable fields)
	* @param string prefix for displaying data, null stands for $uploadPath
	* @param string regular expression with allowed file extensions
	*/
	function __construct($uploadPath = "../static/data/", $displayPath = null, $extensions = "[a-zA-Z0-9]+") {
		$this->uploadPath = $uploadPath;
		$this->displayPath = ($displayPath !== null ? $displayPath : $uploadPath);
		$this->extensions = $extensions;
	}

	function editInput($table, $field, $attrs, $value) {
		if (preg_match('~(.*)_url$~', $field["field"])) {
			return "<img src='$value' width='200'><br><input type='file' name='fields-$field[field]'>";
		}
	}

	function processInput($field, $value, $function = "") {
		if (!empty($_FILES) && preg_match('~(.*)_url$~', $field["field"], $regs)) {

	        $manager = new ImageManager();

			$table = ($_GET["edit"] != "" ? $_GET["edit"] : $_GET["select"]);
			$name = "fields-$field[field]";

			if ($_FILES[$name]["error"] || !preg_match("~(\\.($this->extensions))?\$~", $_FILES[$name]["name"], $regs2)) {
				return false;
			}
			
			$url = "";
			$key = uniqid() . $regs2[0];

		    try {

	            $url = getenv('UPLOADS_URL') . '/' . $key;
	            $orig = $manager->make($_FILES[$name]['tmp_name'])
	                ->orientate()
	                ->save(getenv('UPLOADS_PATH') . '/' . $key, (int) getenv('UPLOADS_QUALITY'));

	            if (preg_match('~(.*)1_url$~', $field["field"], $regs)) {
		            $resolutions = explode(',',getenv('UPLOADS_RESOLUTIONS'));
		            foreach($resolutions as $res){
		                $parts = explode('x',$res);
		                $resized = $manager->make($_FILES[$name]['tmp_name'])
		                    ->orientate()
		                    ->fit((int) $parts[0],(int) $parts[1])
		                    ->save(getenv('UPLOADS_PATH') . '/' . $parts[0] . 'x' . $parts[1] . $key, (int) getenv('UPLOADS_QUALITY'));
		            }
		        }

		    } catch (S3Exception $e) {
		      // Catch an S3 specific exception.
		        die($e->getMessage());
		    }

			return q($url);
		}
	}

	function selectVal($val, &$link, $field, $original) {
		if ($val != "&nbsp;" && preg_match('~(.*)_url$~', $field["field"], $regs)) {
			//$link = "$this->displayPath$_GET[select]/$regs[1]-$val";
			return "<a href='$val' target='_blank' title='$val'><div style='background-image: url($val); background-repeat: no-repeat;background-size: contain; width:80px;height:40px'></div></a>";
		}
	}
}