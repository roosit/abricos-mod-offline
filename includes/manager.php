<?php
/**
 * @package Abricos
 * @subpackage Offline
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * @author Alexander Kuzmin <roosit@abricos.org>
 */

require_once 'classes.php';

class OfflineManager extends Ab_ModuleManager {
	
	/**
	 * 
	 * @var OfflineModule
	 */
	public $module = null;
	
	/**
	 * @var OfflineManager
	 */
	public static $instance = null;
	
	/**
	 * Конфиг
	 * @var OfflineConfig
	 */
	public $config = null;
	
	public function __construct($module){
		parent::__construct($module);

		OfflineManager::$instance = $this;

		$this->config = new OfflineConfig(Abricos::$config['module']['offline']);
	}
	
	public function IsAdminRole(){
		return $this->IsRoleEnable(OfflineAction::ADMIN);
	}
	
	public function IsWriteRole(){
		if ($this->IsAdminRole()){ return true; }
		return $this->IsRoleEnable(OfflineAction::WRITE);
	}
	
	public function IsViewRole(){
		if ($this->IsWriteRole()){ return true; }
		return $this->IsRoleEnable(OfflineAction::VIEW);
	}

	public function AJAX($d){

		switch($d->do){
			case "build": return $this->BuildOffline();
		}

		return null;
	}
	
	public function ParamToObject($o){
		if (is_array($o)){
			$ret = new stdClass();
			foreach($o as $key => $value){
				$ret->$key = $value;
			}
			return $ret;
		}else if (!is_object($o)){
			return new stdClass();
		}
		return $o;
	}
	
	public function ToArray($rows, &$ids1 = "", $fnids1 = 'uid', &$ids2 = "", $fnids2 = '', &$ids3 = "", $fnids3 = ''){
		$ret = array();
		while (($row = $this->db->fetch_array($rows))){
			array_push($ret, $row);
			if (is_array($ids1)){
				$ids1[$row[$fnids1]] = $row[$fnids1];
			}
			if (is_array($ids2)){
				$ids2[$row[$fnids2]] = $row[$fnids2];
			}
			if (is_array($ids3)){
				$ids3[$row[$fnids3]] = $row[$fnids3];
			}
		}
		return $ret;
	}
	
	public function ToArrayId($rows, $field = "id"){
		$ret = array();
		while (($row = $this->db->fetch_array($rows))){
			$ret[$row[$field]] = $row;
		}
		return $ret;
	}
	
	private $_tplPage = "";
	public function WritePage(OfflineDir $dir, $fname, $content, $title = ''){
		$filename = $dir->GetFileName($fname);
		
		if (file_exists($filename)){
			unlink($filename);
		}
			
		$fh = fopen($filename, 'a');
		
		if (!$fh){ return false; }
		
		$css = "";

		$str = Brick::ReplaceVarByData($this->_tplPage, array(
			"title" => $title,
			"content" =>  $content,
			"rooturi" => $dir->rootURI,
			"csslinks" => $css
		));
		
		fwrite($fh, $str);
		fflush($fh);
		fclose($fh);
	}
	
	public function BuildOffline(){
		if (!$this->IsAdminRole()){ return null; }
		
		$brick = Brick::$builder->LoadBrickS("offline", "page", null);
		$this->_tplPage = $brick->content;
		
		$brickIndex = Brick::$builder->LoadBrickS("offline", "index", null);
		$vIndex = &$brickIndex->param->var;
		
		// зарегистрировать все модули
		Abricos::$instance->modules->RegisterAllModule();
		$modules = Abricos::$instance->modules->GetModules();
		
		$rootDir = new OfflineDir(null, CWD."/cache/offline");
		
		$modList = "";
		$modAList = array();
		
		// произвести выгрузку в модулях где есть реализация
		$sqls = array();
		foreach ($modules as $name => $module){
			if (!method_exists($module, 'Offline_IsBuild')){ continue; }
			if (!$module->Offline_IsBuild()){ continue; }
			
			$manager = 	$module->GetManager();
			if (!method_exists($manager, 'Offline_Build')){ continue; }

			$modDir = new OfflineDir($rootDir, $module->takelink);

			$manager->Offline_Build($modDir);
			
			$link = $name."/index.html";
			
			if (!empty($vIndex['menuitem-'.$name])){
				$modList .= Brick::ReplaceVarByData($vIndex['menuitem-'.$name], array(
					"url" => $link
				));
			}else{
				$modList .= Brick::ReplaceVarByData($vIndex['menuitem'], array(
					"url" => $link
				));
			}
			$modAList["url".$name] = $link;
		}
		$modAList["menulist"] = $modList;
		
		$brickIndex->content = Brick::ReplaceVarByData($brickIndex->content, $modAList);
		$this->WritePage($rootDir, "index", $brickIndex->content);
		
		return true;
	}
	
	
}

?>