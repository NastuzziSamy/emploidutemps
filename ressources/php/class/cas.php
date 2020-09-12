<?php

function xmlstr_to_array($xmlstr) {
  $doc = new DOMDocument();
  $doc->loadXML($xmlstr);
  $root = $doc->documentElement;
  $output = domnode_to_array($root);
  $output['@root'] = $root->tagName;

  return $output;
}

function domnode_to_array($node) {
  $output = array();
  switch ($node->nodeType) {
    case XML_CDATA_SECTION_NODE:
    case XML_TEXT_NODE:
      $output = trim($node->textContent);
    break;
    case XML_ELEMENT_NODE:
      for ($i=0, $m=$node->childNodes->length; $i<$m; $i++) {
        $child = $node->childNodes->item($i);
        $v = domnode_to_array($child);
        if(isset($child->tagName)) {
          $t = $child->tagName;
          if(!isset($output[$t])) {
            $output[$t] = array();
          }
          $output[$t][] = $v;
        }
        elseif($v || $v === '0') {
          $output = (string) $v;
        }
      }
      if($node->attributes->length && !is_array($output)) { //Has attributes but isn't an array
        $output = array('@content'=>$output); //Change output into an array.
      }
      if(is_array($output)) {
        if($node->attributes->length) {
          $a = array();
          foreach($node->attributes as $attrName => $attrNode) {
            $a[$attrName] = (string) $attrNode->value;
          }
          $output['@attributes'] = $a;
        }
        foreach ($output as $t => $v) {
          if(is_array($v) && count($v)==1 && $t!='@attributes') {
            $output[$t] = $v[0];
          }
        }
      }
    break;
  }

  return $output;
}

class CAS {
	const URL = 'https://cas.utc.fr/cas/';


	public static function authenticate($parse = TRUE)	{
		if (!isset($_GET['ticket']) || empty($_GET['ticket']))
			return -1;

		$data = file_get_contents(self::URL.'serviceValidate?service=http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'].'&ticket='.$_GET['ticket']);

		if (empty($data))
			return -1;

    $array = xmlstr_to_array($data);

    if (!isset($array['cas:authenticationSuccess']))
      return -1;

    return $parse ? $array['cas:authenticationSuccess'] : $data;
	}


	public static function login() {
		header('Location: '.self::URL.'login?service=http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']);
	}


	public static function logout()	{
		header('Location: '.self::URL.'logout?service=http://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['PHP_SELF']));//ou SCRIPT_NAME?
		// On n'utilise pas REQUEST_URI sinon cela déconnecterait à l'infini.
	}
}
