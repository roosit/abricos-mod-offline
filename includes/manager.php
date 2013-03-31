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
	
	public function BuildOffline(){
		if ($this->IsAdminRole()){ return null; }
		
		
		
	}
	
	
}

?>