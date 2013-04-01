<?php 
/**
 * @package Abricos
 * @subpackage Offline
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * @author Alexander Kuzmin <roosit@abricos.org>
 */

require_once 'dbquery.php';

class OfflineDir {
	
	/**
	 * @var OfflineDir
	 */
	public $parent;
	
	public $path;
	
	/**
	 * Имя директории
	 * @var string
	 */
	public $name;
	
	public $rootURI = "";
	
	public $rootPath = "";
	
	public $imagePath = "";
	
	public function __construct($parent, $name){
		$this->parent = $parent;
		
		if (empty($parent)){ // это рутовая директория
			$this->rootPath = $name;
			$this->path = $name;
			$this->name = "";
		}else{
			$this->rootPath = $parent->rootPath;
			$this->path = $parent->path."/".$name;
			$this->name = $name;
		}
		
		@mkdir($this->path);
		
		$this->rootURI = $this->BuildRootURI();
		
		$this->imagePath = $this->rootPath."/img";

		@mkdir($this->imagePath);
	}
	
	public function GetImageSrc($fname){
		return $this->rootURI."img/".$fname;
	}
	
	public function GetFileName($name, $ext = "html"){
		return $this->path."/".$name.".".$ext;
	}
	
	private function BuildRootURI(){
		
		if (empty($this->name)){
			return "";
		}
		$uri = "../";
		
		$puri = $this->parent->BuildRootURI();
		if (!empty($puri)){
			$uri = $puri.$uri;
		}
		
		return $uri;
	}
	
}


class OfflineConfig {

	/**
	 * @var OfflineConfig
	 */
	public static $instance;

	public function __construct($cfg){
		OfflineConfig::$instance = $this;

		if (empty($cfg)){
			$cfg = array();
		}

		/*
		 if (isset($cfg['subscribeSendLimit'])){
		$this->subscribeSendLimit = intval($cfg['subscribeSendLimit']);
		}
		/**/
	}
}

?>