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
	
	private function CSSLinksHTML($dir){
		$css = "";
		$mfiles = Brick::$builder->GetCSSModFiles();

		foreach ($mfiles as $srcCssFile => $modname){
				
			$fi = pathinfo($srcCssFile);
			$cssFName = $fi['basename'];// .".".$fi['extension'];
			$dstCssFName = $modname."-".$cssFName;
				
			$dstCssFile = $dir->cssPath."/".$dstCssFName;
				
			if (!file_exists($dstCssFile)){
				@copy(CWD.$srcCssFile, $dstCssFile);
			}
			$mod = Abricos::GetModule($modname);
				
			$css .= "<link href='".$dir->GetCSSSrc($dstCssFName."?v=".$mod->version)."' type='text/css' rel='stylesheet' />\n";
		}
		return $css;
	}
	
	private $_tplPage = "";
	public function WritePage(OfflineDir $dir, $fname, $content, $title = ''){
		$filename = $dir->GetFileName($fname);
		
		if (file_exists($filename)){
			@unlink($filename);
		}

		$fh = fopen($filename, 'a');

		if (!$fh){ return false; }

		$str = Brick::ReplaceVarByData($this->_tplPage, array(
			"title" => $title,
			"content" =>  $content,
			"rooturi" => $dir->rootURI,
			"csslinks" => $this->CSSLinksHTML($dir)
		));
		
		fwrite($fh, $str);
		fflush($fh);
		fclose($fh);
	}
	
	public function WriteImage(OfflineDir $dir, $fhash, $w=0, $h=0){
		$manFM = FileManagerModule::$instance->GetFileManager();
		
		$fhash = $manFM->ImageConvert($fhash, $w, $h, "");
		
		$finfo = $manFM->GetFileInfo($fhash);
		
		$fname = $fhash.".".$finfo['ext'];

		$imgSrc = $dir->GetImageSrc($fname);
		
		$file = $dir->imagePath."/".$fname;
		
		if (file_exists($file)){ return $imgSrc; }
		
		if (!($handle = fopen($file, 'w'))){
			return $imgSrc;
		}
		$fileinfo = $manFM->GetFileData($fhash);
		$count = 1;
		while (!empty($fileinfo['filedata']) && connection_status() == 0) {
			fwrite($handle, $fileinfo['filedata']);
			if (strlen($fileinfo['filedata']) == 1048576) {
				$startat = (1048576 * $count) + 1;
				$fileinfo =  $manFM->GetFileData($fhash, $startat);
				$count++;
			} else {
				$fileinfo['filedata'] = '';
			}
		}
		fclose($handle);
		return $imgSrc;
	}
	
	public function CopyDir($src, $dst){
		if (!is_dir($src)){ return; }
		@mkdir($dst);
		
		if (!($dh = opendir($src))) { return; }
		
		while (($file = readdir($dh)) !== false) {
			if ($file == "." || $file == ".."){ continue; }
			
			$srcFile = $src."/".$file;
			$dstFile = $dst."/".$file;
			
			if (filetype($srcFile) == "file"){
				@copy($srcFile, $dstFile);
			}else if (filetype($srcFile) == "dir"){
				$this->CopyDir($srcFile, $dstFile);
			}
		}
		closedir($dh);
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
		
		$rootPath = CWD."/cache/offline";
		
		$rootDir = new OfflineDir(null, $rootPath);
		
		// сформировать стуктуру
		// клонировать базовую структуру
		$this->CopyDir(CWD."/modules/offline/template", $rootPath);
		
		// клонировать перегруженную структуру
		$this->CopyDir(CWD."/tt/".Brick::$style."/override/offline/template", $rootPath);
		
		$modList = "";
		$modAList = array();
		
		// произвести выгрузку в модулях где есть реализация
		$sqls = array();
		foreach ($modules as $name => $module){
			if (!method_exists($module, 'Offline_IsBuild') 
				|| !$module->Offline_IsBuild()){ 
				continue; 
			}
			
			$manager = 	$module->GetManager();
			if (!method_exists($manager, 'Offline_Build')){ continue; }

			$takeLink = $this->config->takeLinkOverride[$name];
			if (empty($takeLink)){
				$takeLink = $module->takelink;
			}
			
			$modDir = new OfflineDir($rootDir, $takeLink);

			$manager->Offline_Build($modDir);
			
			$link = $takeLink."/index.html";
			
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
		
		@copy(CWD."/images/empty.gif", $rootDir->rootPath."/img/empty.gif");
		
		return true;
	}
	
	
}

?>