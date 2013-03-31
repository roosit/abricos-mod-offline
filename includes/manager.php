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
			case "sporteventlist": 
				return $this->SportEventListToAJAX();
			case "sporteventsave": 
				return $this->SportEventSave($d->savedata);
			case "sportsmanlist": 
				return $this->SportsmanListToAJAX($d->seventid);
			case "sportsman":
				return $this->SportsmanToAJAX($d->smanid);
			case "sportsmansave": 
				return $this->SportsmanSave($d->seventid, $d->savedata);
			case "sboard":
				return $this->SBoardToAJAX($d->seventid, $d->sboardid);
			case "sboardsave":
				return $this->SBoardSave($d->seventid, $d->sboardid, $d->savedata);
			case "sboardcommandsave":
				return $this->SBoardCommandSave($d->seventid, $d->sboardid, $d->savedata);
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
	
	public function SportEventSave($d){
		if (!$this->IsAdminRole()){ return null; }
		
		$error = 0;
		$seventid = intval($d->id);
		
		$utmf = Abricos::TextParser(true);
		$d->tl = $utmf->Parser($d->tl);
		
		if ($seventid == 0){
			$seventid = OfflineQuery::SportEventAppend($this->db, $d);
		}else{
			
		}
		
		$ret = $this->SportEventListToAJAX();
		$ret->error = $error;
		$ret->seventid = $seventid;
		
		return $ret;
	}
	
	/**
	 * @return OfflineSportEventList
	 */
	public function SportEventList(){
		if (!$this->IsViewRole()){ return null; }

		$list = new OfflineSportEventList();
		$rows = OfflineQuery::SportEventList($this->db);
		
		while (($d = $this->db->fetch_array($rows))){
			$list->Add(new OfflineSportEvent($d));
		}
		
		return $list;
	}
	
	public function SportEventListToAJAX(){
		$list = $this->SportEventList();
		if (empty($list)){ return null; }
		
		$ret = new stdClass();
		$ret->sportevents = $list->ToAJAX();
		
		return $ret;
	}
	
	/**
	 * @return OfflineSportsman
	 */
	public function Sportsman($smanid){
		if (!$this->IsViewRole()){ return null; }
		
		$d = OfflineQuery::Sportsman($this->db, $smanid);
		$item = new OfflineSportsman($d);
		
		return $item;
	}
	
	public function SportsmanToAJAX($smanid){
		$item = $this->Sportsman($smanid);
		if (empty($item)){ return null; }
	
		$ret = new stdClass();
		$ret->sportsman = $item->ToAJAX();
	
		return $ret;
	}
	
	
	/**
	 * @return OfflineSportsmanList
	 */
	public function SportsmanList($seventid){
		if (!$this->IsViewRole()){ return null; }
	
		$list = new OfflineSportsmanList();
		$rows = OfflineQuery::SportsmanList($this->db, $seventid);
	
		while (($d = $this->db->fetch_array($rows))){
			$list->Add(new OfflineSportsman($d));
		}
	
		return $list;
	}
	
	public function SportsmanListToAJAX($seventid){
		$list = $this->SportsmanList($seventid);
		if (empty($list)){ return null; }
	
		$ret = new stdClass();
		$ret->sportsmans = $list->ToAJAX();
	
		return $ret;
	}
	
	public function SportsmanSave($seventid, $d){
		if (!$this->IsAdminRole()){ return null; }
	
		$error = 0;
		$smanid = intval($d->id);
	
		$utmf = Abricos::TextParser(true);
		$d->fnm = $utmf->Parser($d->fnm);
		$d->lnm = $utmf->Parser($d->lnm);
		$d->pnm = $utmf->Parser($d->pnm);
		
		if ($smanid == 0){
			$smanid = OfflineQuery::SportsmanAppend($this->db, $seventid, $d);
		}else{
			OfflineQuery::SportsmanUpdate($this->db, $smanid, $d);
		}
	
		$ret = $this->SportsmanListToAJAX($seventid);
		$ret->error = $error;
		$ret->snamid = $smanid;
	
		return $ret;
	}
	
	/**
	 * @return OfflineBoardLogList
	 */
	public function SBoardLogList($seventid, $sboardid){ 
		if (!$this->IsViewRole()){ return null; }
	
		$rows = OfflineQuery::SBoardLogList($this->db, $seventid, $sboardid);
		$list = new OfflineBoardLogList();
		
		while (($d = $this->db->fetch_array($rows))){
			$list->Add(new OfflineBoardLog($d));
		}
	
		return $list;
	}
	
	public function SBoardLogListToAJAX($seventid, $sboardid){
		$list = $this->SBoardLogList($seventid, $sboardid);
		if (empty($list)){ return null; }
		
		$ret = new stdClass();
		$ret->sboardlogs = $list->ToAJAX();
		
		return $ret;
	}
	
	/**
	 * @return OfflineBoard
	 */
	public function SBoard($seventid, $sboardid){
		if (!$this->IsViewRole()){ return null; }
		
		$log = $this->SBoardLogList($seventid, $sboardid);
		
		$d = OfflineQuery::SBoard($this->db, $seventid, $sboardid);
		if (empty($d)){
			$d = array("id"=>$sboardid, "seid"=>$seventid);
		}
		
		$item = new OfflineBoard($d, $log);

		return $item;
	}
	
	public function SBoardToAJAX($seventid, $sboardid){
		$item = $this->SBoard($seventid, $sboardid);
		if (empty($item)){
			return null;
		}
	
		$ret = new stdClass();
		$ret->sboard = $item->ToAJAX();
	
		return $ret;
	}
	
	public function SBoardSave($seventid, $sboardid, $d){
		if (!$this->IsAdminRole()){ return null; }
		
		$d->ss = intval($d->ss);
		if (empty($d->ss)){
			$val->ss = 180;
		}
		OfflineQuery::SBoardUpdate($this->db, $seventid, $sboardid, $d);
		
		return $this->SBoardToAJAX($seventid, $sboardid);
	}
	
	public function SBoardCommandSave($seventid, $sboardid, $d){
		if (!$this->IsAdminRole()){ return null; }
		
		$cmd = intval($d->cmd);
		$col = $d->c=='l' || $d->c=='r' ? $d->c : '';
		
		$logs = $this->SBoardLogList($seventid, $sboardid);
		$step = $logs->Count();
		
		$cur = $logs->Current();
		
		switch($cmd){
			case OfflineBoard::START:
			case OfflineBoard::STOP:
			case OfflineBoard::PAUSE:
				$col = ''; $val = 0;
				break;
			case OfflineBoard::RESET:
				OfflineQuery::SBoardLogClear($this->db, $seventid, $sboardid);
				return true;
				
			case OfflineBoard::POINT1:
			case OfflineBoard::POINT2:
			case OfflineBoard::POINT3:
			case OfflineBoard::POINTM:
			case OfflineBoard::CAT1:
			case OfflineBoard::CAT1M:
			case OfflineBoard::CAT2:
			case OfflineBoard::CAT2M:
				$val = 0;
				break;
				
			default: 
				return null;
		}
		
		OfflineQuery::SBoardCommandAppend($this->db, $seventid, $sboardid, $step, $cmd, $col, $val);
		
		return true;
	}
	
	
}

?>