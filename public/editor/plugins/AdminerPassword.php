<?php
//! delete

/** Edit fields ending with "_url" by <input type="file"> and link to the uploaded files from select
* @link https://www.adminer.org/plugins/#use
* @author Jakub Vrana, http://www.vrana.cz/
* @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
* @license http://www.gnu.org/licenses/gpl-2.0.html GNU General Public License, version 2 (one or other)
*/

use Slim\Views\Twig;
use Tuupola\Base62;
use App\Config;

class AdminerPassword {
	/** @access protected */
	var $uploadPath, $displayPath, $extensions;

	/**
	* @param string prefix for uploading data (create writable subdirectory for each table containing uploadable fields)
	* @param string prefix for displaying data, null stands for $uploadPath
	* @param string regular expression with allowed file extensions
	*/
	function __construct($uploadPath = "../static/data/", $displayPath = null, $extensions = "[a-zA-Z0-9]+") {

		global $app;

		$this->uploadPath = $uploadPath;
		$this->displayPath = ($displayPath !== null ? $displayPath : $uploadPath);
		$this->extensions = $extensions;

		$container = $app->getContainer();
		$config = $container['spot']->mapper("App\Config")
		    ->all();

		foreach($config as $item){
		    putenv("$item->config_key=$item->config_value");
		}
	}
	
	function editInput($table, $field, $attrs, $value) {
		if (preg_match('~(.*)_hash$~', $field["field"])) {
			if(empty($_GET['where']['id'])){
				//return "<img src='$value' width='200'><br><input type='file' name='fields-$field[field]'>";
				return "<p>La contraseña será creada aleatoriamemente y enviada por email.</p>";
			}
			
			return "<input type='text' data-maxlength='32' size='40' name='fields[$field[field]]' value='$value' readonly>";
			//return q($value);
		}
	}

	function processInput($field, $value, $function = "") {
		if (preg_match('~(.*)_hash$~', $field["field"], $regs)) {
			if(empty($_GET['where']['id'])){ // sets hash and send email if create.
				$data = [];
				$password = strtolower(Base62::encode(random_bytes(16)));
				$recipient = (object) $_REQUEST['fields'];
				$data['readable_password'] = $password;
				$data['email_encoded'] = Base62::encode($recipient->email);
				$email = \send_email_template('EMAIL_WELCOME',$recipient,$data);
				$hash = sha1($password.getenv('APP_HASH_SALT'));
				return q($hash);
			} // otherwise returns hash
			return q($value);
		}
	}
	/*
	function selectVal($val, &$link, $field, $original) {
		if ($val != "&nbsp;" && preg_match('~(.*)_hash$~', $field["field"], $regs)) {
			//$link = "$this->displayPath$_GET[select]/$regs[1]-$val";
			return "<a href='$val' target='_blank' title='$val'><div style='background-image: url($val); background-repeat: no-repeat;background-size: contain; width:80px;height:40px'></div></a>";
		}
	}*/
}