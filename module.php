<?php
/**
 * @package Abricos
 * @subpackage Offline
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * @author Alexander Kuzmin <roosit@abricos.org>
 */

class OfflineModule extends Ab_Module {
	
	/**
	 * @var OfflineModule
	 */
	public static $instance = null;
	
	private $_manager = null;
	
	public function OfflineModule(){
		// версия модуля
		$this->version = "0.1";

		// имя модуля 
		$this->name = "offline";

		$this->takelink = "offline";
		
		$this->permission = new OfflinePermission($this);
		
		OfflineModule::$instance = $this;
	}
	
	/**
	 * @return OfflineManager
	 */
	public function GetManager(){
		if (is_null($this->_manager)){
			require_once 'includes/manager.php';
			$this->_manager = new OfflineManager($this);
		}
		return $this->_manager;
	}
	
	public function GetContentName(){
		return "";
	}

}

class OfflineAction {
	const VIEW	= 10;
	const WRITE	= 30;
	const ADMIN	= 50;
}

class OfflinePermission extends Ab_UserPermission {

	public function OfflinePermission(OfflineModule $module){
		
		$defRoles = array(
			new Ab_UserRole(OfflineAction::VIEW, Ab_UserGroup::GUEST),
			new Ab_UserRole(OfflineAction::VIEW, Ab_UserGroup::REGISTERED),
			new Ab_UserRole(OfflineAction::VIEW, Ab_UserGroup::ADMIN),

			new Ab_UserRole(OfflineAction::WRITE, Ab_UserGroup::ADMIN),

			new Ab_UserRole(OfflineAction::ADMIN, Ab_UserGroup::ADMIN),
		);
		
		parent::__construct($module, $defRoles);
	}

	public function GetRoles(){
		return array(
			OfflineAction::VIEW => $this->CheckAction(OfflineAction::VIEW),
			OfflineAction::WRITE => $this->CheckAction(OfflineAction::WRITE),
			OfflineAction::ADMIN => $this->CheckAction(OfflineAction::ADMIN)
		);
	}
}
Abricos::ModuleRegister(new OfflineModule());


?>