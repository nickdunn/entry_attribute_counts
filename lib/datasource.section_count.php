<?

	$fieldPool = array();
	$where = NULL;
	$joins = NULL;
	$group = false;

	$key = 'ds-' . $this->dsParamROOTELEMENT;

	include_once(TOOLKIT . '/class.entrymanager.php');
	$entryManager = new EntryManager($this->_Parent);

	if(!$section = $entryManager->sectionManager->fetch($this->getSource())){
		$about = $this->about();
		trigger_error(__('The section associated with the data source <code>%s</code> could not be found.', array($about['name'])), E_USER_ERROR);
	}

	$sectioninfo = new XMLElement('section', $section->get('name'), array('id' => $section->get('id'), 'handle' => $section->get('handle')));

	if($this->_force_empty_result == true){
		$this->_force_empty_result = false; //this is so the section info element doesn't dissapear.
		$result = $this->emptyXMLSet();
		$result->prependChild($sectioninfo);
		return;
	}

	$include_pagination_element = @in_array('system:pagination', $this->dsParamINCLUDEDELEMENTS);

	if(is_array($this->dsParamFILTERS) && !empty($this->dsParamFILTERS)){
		foreach($this->dsParamFILTERS as $field_id => $filter){

			if((is_array($filter) && empty($filter)) || trim($filter) == '') continue;

			if(!is_array($filter)){
				$filter_type = $this->__determineFilterType($filter);

				$value = preg_split('/'.($filter_type == DS_FILTER_AND ? '\+' : '(?<!\\\\),').'\s*/', $filter, -1, PREG_SPLIT_NO_EMPTY);			
				$value = array_map('trim', $value);

				$value = array_map(array('Datasource', 'removeEscapedCommas'), $value);
			}

			else $value = $filter;

			if(!isset($fieldPool[$field_id]) || !is_object($fieldPool[$field_id]))
				$fieldPool[$field_id] =& $entryManager->fieldManager->fetch($field_id);

			if($field_id != 'id' && !($fieldPool[$field_id] instanceof Field)){
				throw new Exception(
					__(
						'Error creating field object with id %1$d, for filtering in data source "%2$s". Check this field exists.', 
						array($field_id, $this->dsParamROOTELEMENT)
					)
				);
			}

			if($field_id == 'id') $where = " AND `e`.id IN ('".@implode("', '", $value)."') ";
			else{ 
				if(!$fieldPool[$field_id]->buildDSRetrivalSQL($value, $joins, $where, ($filter_type == DS_FILTER_AND ? true : false))){ $this->_force_empty_result = true; return; }
				if(!$group) $group = $fieldPool[$field_id]->requiresSQLGrouping();
			}

		}
	}

	if($this->dsParamSORT == 'system:id') $entryManager->setFetchSorting('id', $this->dsParamORDER);
	elseif($this->dsParamSORT == 'system:date') $entryManager->setFetchSorting('date', $this->dsParamORDER);
	else $entryManager->setFetchSorting($entryManager->fieldManager->fetchFieldIDFromElementName($this->dsParamSORT, $this->getSource()), $this->dsParamORDER);

	$count = $entryManager->fetchCount($this->getSource(), $where, $joins);
	$result->setValue($count);