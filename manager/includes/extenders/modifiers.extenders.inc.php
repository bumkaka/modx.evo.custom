<?php

if (class_exists('modifiers') ) return;

class modifiers{

public function parse($output, $key, $modifiers){
    global $modx;
    if ( !preg_match('/^\.*-*_*\w*\d*(:)/mi',$modifiers) ) return $output;
     if (preg_match_all('~:([^:=]+)(?:=`(.*?)`(?=:[^:=]+|$))?~s',$modifiers, $matches)) {
        $count = count($matches[1]);
        $condition = array();
        for($i=0; $i<$count; $i++) {
            $output = trim($output);
            $modifier_cmd = $matches[1][$i]; // modifier command
            $modifier_value = $matches[2][$i]; // modifier value
            $value = $modifier_value;
            switch ($modifier_cmd) {
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
                    $wrapat = intval($modifier_value) ? intval($modifier_value) : 70;
                    $output = preg_replace("~(\b\w+\b)~e","wordwrap('\\1',\$wrapat,' ',1)",$output);
                break;

                case "limit":
                    $limit = intval($modifier_value) ? intval($modifier_value) : 100;
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
                    $output = $modifier_cmd($output);
                break;

                case "math":
                    $filter = preg_replace("~([a-zA-Z\n\r\t\s])~","",$modifier_value);
                    $filter = str_replace("?",$output,$filter);
                    $output = eval("return ".$filter.";");
                break;

                case "date":
                    $output = strftime($modifier_value,0+$output);
                break;

                default:

                $snippetName = 'modifier:'.$modifier_cmd;
                if( isset($modx->snippetCache[$snippetName]) ) {
                    $snippet = $modx->snippetCache[$snippetName];
                } else {
                    $prfx = $modx->db->config['table_prefix'];
                    $esc_name = $modx->db->escape($snippetName);
                    $result= $modx->db->select('snippet',"{$prfx}site_snippets","name='{$esc_name}'");
                    if ($modx->db->getRecordCount($result) == 1) {
                        $row= $modx->db->getRow($result);
                        $snippet= $modx->snippetCache[$row['name']]= $row['snippet'];
                    } else if ($modx->db->getRecordCount($result) == 0){ // If snippet not found, look in the modifiers folder
                        $filename = $modx->config['rb_base_dir'] . 'modifiers/'.$modifier_cmd.'.modifier.php';
                        if (@is_file($filename)) {
                            $file_contents = @file_get_contents($filename);
                            $file_contents = str_replace('<'.'?php', '', $file_contents);
                            $file_contents = str_replace('?'.'>', '', $file_contents);
                            $file_contents = str_replace('<?', '', $file_contents);
                            $snippet = $modx->snippetCache[$snippetName] = $file_contents;
                            $modx->snippetCache[$snippetName.'Props'] = '';
                        }
                    }
                }
                $cm = $snippet;
                // end //
                
                ob_start();
                $options = $modifier_value;
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

$this->modifiers = new modifiers;
