<?php
	
	class extension_entry_attribute_counts extends Extension {
		
		private $param_pool = array();
		
		public function about() {
			return array(
				'name'			=> 'Entry Attribute Counts',
				'version'		=> '0.1',
				'release-date'	=> '2010-09-05',
				'author'		=> array(
					'name'			=> 'Nick Dunn',
					'website'		=> 'http://nick-dunn.co.uk'
				),
				'description' => 'Add custom attributes to <entry> elements in Data Sources'
			);
		}
		
		public function install() {
			$string = '<?xml version="1.0" encoding="UTF-8" ?>' . "\n";
			$string .= '<datasource-pairs>';
			$string .= '<!--pair add-attribute-to="posts" get-count-from="posts_comments"/-->';
			$string .= '</datasource-pairs>';
			return file_put_contents(MANIFEST . '/entry-attribute-counts.xml', $string);
		}
		
		public function uninstall() {
			if(file_exists(MANIFEST . '/entry-attribute-counts.xml')) unlink(MANIFEST . '/entry-attribute-counts.xml');
		}
		
		public function getSubscribedDelegates() {
			return array(
				array(
					'page'		=> '/frontend/',
					'delegate'	=> 'FrontendParamsPostResolve',
					'callback'	=> 'frontendParamsPostResolve'
				),
				array(
					'page'		=> '/frontend/',
					'delegate'	=> 'FrontendOutputPreGenerate',
					'callback'	=> 'frontendOutputPreGenerate'
				),
			);
			
			
		}
		
		public function frontendParamsPostResolve($context) {
			// cache the param pool, we'll need that later!
			$this->param_pool = $context['params'];
		}
		
		public function frontendOutputPreGenerate(&$context) {
			
			$pairs_config = @simplexml_load_file(MANIFEST . '/entry-attribute-counts.xml');
			if (!$pairs_config) return;
			
			$pairs = $pairs_config->xpath('pair');
			
			$xml = simplexml_load_string($context['xml']);
			
			foreach($pairs as $pair) {
				
				foreach($xml as $name => $node) {
					if ($name != $pair->attributes()->{'add-attribute-to'}) continue;
					foreach($node as $name => $entry) {
						if ($name != 'entry') continue;

						$dsm = new DataSourceManager(Frontend::instance());
						$ds_copy = $dsm->create($pair->attributes()->{'get-count-from'});

						require_once(EXTENSIONS . '/entry_attribute_counts/lib/class.datasource_count.php');
						$ds = new CountDataSource(Frontend::instance(), NULL, FALSE);

						$ds->dsSource = $ds_copy->getSource();
						$ds->dsParamROOTELEMENT = $ds_copy->dsParamROOTELEMENT;
						$ds->dsParamORDER = $ds_copy->dsParamORDER;
						$ds->dsParamLIMIT = 999999;
						$ds->dsParamREDIRECTONEMPTY = 'no';
						$ds->dsParamREQUIREDPARAM = '';
						$ds->dsParamSORT = $ds_copy->dsParamSORT;
						$ds->dsParamSTARTPAGE = 1;
						$ds->dsParamASSOCIATEDENTRYCOUNTS = 'no';
						$ds->dsParamFILTERS = $ds_copy->dsParamFILTERS;
						$ds->dsParamINCLUDEDELEMENTS = $ds_copy->dsParamINCLUDEDELEMENTS;

						$this->param_pool['ds-' . $pair->attributes()->{'add-attribute-to'}] = $entry->attributes()->id;

						$ds->processParameters(array('env' => array(), 'param' => $this->param_pool));

						$grab_xml = $ds->grab($this->param_pool);

						$entry->addAttribute(Lang::createHandle($pair->attributes()->{'get-count-from'}), $grab_xml->getValue());
					}
				}
				
			}
			
			$context['xml'] = $xml->asXML();
			
		}
			
	}