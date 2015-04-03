<?php

if ( !class_exists('modifiers') ) {
class modifiers{

public function parse($output,$modifiers){
	global $modx;
 	if (preg_match_all('~:([^:=]+)(?:=`(.*?)`(?=:[^:=]+|$))?~s',$modifiers, $matches)) {
		$modifier_cmd = $matches[1]; // modifier command
		$modifier_value = $matches[2]; // modifier value
		$count = count($modifier_cmd);
		$condition = array();
		for($i=0; $i<$count; $i++) {
			$output = trim($output);
			switch ($modifier_cmd[$i]) {
				
				
				
				case "lcase": 
				case "strtolower": 
					$output = strtolower($output); 
				break;	


				case "ucase": 
				case "strtoupper": 
					$output = mb_strtoupper($output, $modx->config['modx_charset']); 
				break;	

				case "htmlent": 
				case "htmlentities": 
					$output = htmlentities($output,ENT_QUOTES,$modx->config['modx_charset']); 
				break;	

				case "html_entity_decode": 
					$output = html_entity_decode($output,ENT_QUOTES,$modx->config['modx_charset']); 
				break;

				case "esc":
					$output = preg_replace("/&amp;(#[0-9]+|[a-z]+);/i", "&$1;", htmlspecialchars($output));
					$output = str_replace(array("[","]","`"),array("&#91;","&#93;","&#96;"),$output);
				break;

				case "strip": 
					$output = preg_replace("~([\n\r\t\s]+)~"," ",$output); 
				break;

				case "notags": 
					case "strip_tags": $output = strip_tags($output); 
				break;

				case "length": 
				case "len": 
				case "strlen": 
					$output = mb_strlen($output,$modx->config['modx_charset']); 
				break;


				case "reverse": 
				case "strrev": 
					$output = iconv("UTF-16LE", $modx->config['modx_charset'], strrev(iconv($modx->config['modx_charset'], "UTF-16BE", $output))); 
				break;

				case "wordwrap": 
					$wrapat = intval($modifier_value[$i]) ? intval($modifier_value[$i]) : 70;
					$output = preg_replace("~(\b\w+\b)~e","wordwrap('\\1',\$wrapat,' ',1)",$output);
				break;

				case "limit": 
					$limit = intval($modifier_value[$i]) ? intval($modifier_value[$i]) : 100;
					$output = substr($output,0,$limit);
				break;


				case "str_word_count": 
				case "word_count":	
				case "wordcount": 
					$output = str_word_count($output); 
				break; 	


				case "ucfirst":
				case "lcfirst":
				case "ucwords":
				case "addslashes":
				case "ltrim":
				case "rtrim":
				case "trim":
				case "nl2br":					
				case "md5": 
					$output = $modifier_cmd[$i]($output); 
				break;


				case "math":
					$filter = preg_replace("~([a-zA-Z\n\r\t\s])~","",$modifier_value[$i]);
					$filter = str_replace("?",$output,$filter);
					$output = eval("return ".$filter.";");
				break;	

				case "date": 
					$output = strftime($modifier_value[$i],0+$output); 
				break;


				default:

				$snippetName = 'modifier:'.$modifier_cmd[$i];
				if( isset($this->snippetCache[$snippetName]) ) {
					$snippet = $this->snippetCache[$snippetName];
				} else {
					$prfx = $this->db->config['table_prefix'];
					$sql= "SELECT snippet FROM {$prfx}site_snippets  WHERE {$prfx}site_snippets.name='" . $this->db->escape($snippetName) . "';";
					$result= $this->db->query($sql);
					if ($this->db->getRecordCount($result) == 1) {
						$row= $this->db->fetchRow($result);
						$snippet= $this->snippetCache[$row['name']]= $row['snippet'];
					} else if ($this->db->getRecordCount($result) == 0){ // If snippet not found, look in the modifiers folder
						$filename = $this->config['rb_base_dir'] . 'modifiers/'.$modifier_cmd[$i].'.modifier.php';
						if (@file_exists($filename)) {
							$file_contents = @file_get_contents($filename);
							$file_contents = str_replace('<'.'?php', '', $file_contents);
							$file_contents = str_replace('?'.'>', '', $file_contents);
							$file_contents = str_replace('<?', '', $file_contents);
							$snippet = $this->snippetCache[$snippetName] = $file_contents;
							$this->snippetCache[$snippetName.'Props'] = '';
						}
					}
				}
				$cm = $snippet;
				// end //
				
				ob_start();
				$options = $modifier_value[$i];
				$custom = eval($cm);
				$msg = ob_get_contents();
				$output = $msg.$custom;
				ob_end_clean();	
				break;
			} 
		}
	}	
	
	return $output;
}

}
}

$this->modifiers = new modifiers;
