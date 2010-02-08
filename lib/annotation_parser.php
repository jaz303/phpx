<?php
namespace phpx;

class AnnotationParser
{
    public function parse($comment) {
        
        if (strlen($comment) == 0 || strpos($comment, ':') === false) {
			return array();
		}
		
		$annotations = array();
		preg_match_all('/\*\s+:(\w+)(\[\])?\s*(=\s*(.*))?$/m', $comment, $matches, PREG_SET_ORDER);
		foreach ($matches as $m) {
			if (!isset($m[4])) {
			    $decode = true;
			} else {
			    $json = trim($m[4]);
    			if ($json[0] == '[' || $json[0] == '{') {
    				$decode = json_decode($json, true);
    			} else {
    				$decode = json_decode('[' . $json . ']', true);
    				if (is_array($decode)) {
    					$decode = $decode[0];
    				}
    			}
			}
			if ($decode === null) {
				throw new \Exception("Invalid JSON fragment: $json");
			}
			if ($m[2] == '[]') {
			    $annotations[$m[1]][] = $decode;
			} else {
			    $annotations[$m[1]] = $decode;
			}
			
		}
		
		return $annotations;
        
    }
}
?>