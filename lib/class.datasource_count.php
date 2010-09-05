<?php

Class CountDataSource extends Datasource{
	
	public $dsParamROOTELEMENT = 'count';
	public $dsSource = NULL;
	
	public $dsParamORDER = 'asc';
	public $dsParamLIMIT = '20';
	public $dsParamREDIRECTONEMPTY = 'no';
	public $dsParamSORT = 'system:id';
	public $dsParamSTARTPAGE = '1';
	
	public $dsParamINCLUDEDELEMENTS = array('system:pagination');
	public $dsParamASSOCIATEDENTRYCOUNTS = 'no';
	
	public $_dependencies = array();
	
	public function __construct(&$parent, $env=NULL, $process_params=TRUE){
		parent::__construct($parent, $env, $process_params);
	}
	
	public function getSource(){
		return $this->dsSource;
	}
	
	public function grab(&$param_pool){
		
		$result = new XMLElement($this->dsParamROOTELEMENT);
		
		try{
			include(EXTENSIONS . '/entry_attribute_counts/lib/datasource.section_count.php');
		}
		catch(Exception $e){
			$result->appendChild(new XMLElement('error', $e->getMessage()));
			return $result;
		}
		if($this->_force_empty_result) $result = $this->emptyXMLSet();

		return $result;

	}
	
}