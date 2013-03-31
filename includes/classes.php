<?php 
/**
 * @package Abricos
 * @subpackage Offline
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * @author Alexander Kuzmin <roosit@abricos.org>
 */

require_once 'dbquery.php';



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