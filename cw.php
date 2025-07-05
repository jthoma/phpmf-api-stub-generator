<?php

function copydir($src, $tgt) {
    $dir = opendir($src);

    // Make the destination directory if it doesn't exist
    @mkdir($tgt);

    while (false !== ($file = readdir($dir))) {
        if (($file != '.') && ($file != '..')) {
            if (is_dir($src . '/' . $file)) {
                // Recursively call the function for subdirectories
                copydir($src . '/' . $file, $tgt . '/' . $file);
            } else {
                // Copy the file
                copy($src . '/' . $file, $tgt . '/' . $file);
            }
        }
    }
    closedir($dir);
}


if ( count($argv) < 3 ){
 ?>Usage: 
   php -q cw.php rest-api.json code-directory 

   note that here the concise and practical JSON 
    definition (OpenAPI-style) for the REST API
    is expected as the rest-api.json and the directory
    should contain the basic structure for phpmf from cw-base

    Note 
      if you are not aware of the OpenAPI-style defenition make use of AI tools like chat gpt or gemini.
     give a detailed query in english as though you will ask an expert to create an Open API style defenition of your requirements about the request and the process to do with what all properties to handle like that give a detail and ask it to create the Open-API defenition for the same.

  <?   
}


$api=json_decode(file_get_contents($argv[1]), true); 
$write_to = $argv[2];

echo "Copying base fies to $write_to !\n\n";
copydir('./cw-base/' , $write_to);

/* we dont need the README.md in the target dir */
@unlink(rtrim($write_to, "/") . '/README.md');

echo "Now trying to analyze the defenition ... \n\n";

$keys = array_keys($api);

$epl = $api[$keys[0]]['endpoints'];
$actions = array_keys($epl);


$epd = array();  //the end point defenitions will go in index.php

$hnd=array(); //the handlers will be written as files in plugins

function hasParams($mu, $dets){
   // any in path parameters need to be transformed to regex pattern
   // in the MFR route defenition
   $mur = $mu;
   if(isset($dets["parameters"]) ){
     foreach($dets["parameters"] as $pp){
       if($pp["in"] == "path"){
         $mur = str_replace('/{' . $pp["name"] . '}','/(.+)', $mu);
       }
     }
   }
   return $mur;
}

function codeGen($mu, $dets, $handler, $method){
  $mur = hasParams($mu, $dets);

  $code = 'public function ' . $method . '(){' . "\n";
  if($mur !== $mu){
    $code = 'public function ' . $method . '($p){' . "\n";
  }
   $code .= "\n/* add the business logic here */\n  ";


$gk = array_keys($dets["responses"]);
$smsg = $dets["responses"][$gk[0]]["description"];
$fmsg = $dets["responses"][$gk[1]]["description"];

if(isset($dets["requestBody"]) && 
   $dets["requestBody"]["required"] == true &&
   isset($dets["requestBody"]["content"]["application/json"])
   ){
   $code .= ' // capture parameters '."\n";
   $code .= '$payload = MF::object("bodyparser")->getjson();' . "\n  ";
   $code .= '/* validate input using format validator */' . "\n";
}
  $code .= '  // handle database actions '."\n";

 if($mur !== $mu){
  $code .= 'if(!isset($p[1]) || !is_numeric($p[1])){' . "\n";
  $code .= ' $out = ["success" => false, "message" => "'.$fmsg.'"];' . "\n"; 
  $code .= '  }else{' . "\n";
  $code .= '    $out = ["success" => true, "message" => "'.$smsg.'"];' . "\n";
  $code .= '  }' . "\n";
 }else{
   $code .= '/* validate action and adjust message accordingly */' . "\n";
  $code .= '    $out = ["success" => true, "message" => "'.$smsg.'"];' . "\n";
 }
  $code .= '  // ouput responses '."\n";
  $code .= '  echo json_encode($out);' . "\n";
  $code .= '  exit(); ' . "\n";
  $code .= '}'. "\n";
  return $code;
  //return '/* will do */';
}


function mkHandler($mu, $dets){
   $verbs = explode(' ', strtolower($dets["description"]));
   $handler = array_pop($verbs);
   $method = array_shift($verbs);
   return array(
    'handler' => $handler ,
    'method' => $method,
    'code' => codeGen($mu, $dets, $handler, $method)
  );
}

function mkEndPoint($mu, $dets){
  $mur = hasParams($mu, $dets);
  $h = mkHandler($mu, $dets);
  return array(
'mfr' => 'MF::MFR("'.$mur.'", "'.$h['handler'].'", "'.$h['method'].'");',
'hndl' => $h
);
}

$ei = 0;
foreach($actions as $mu){
   $endpoint = mkEndPoint($mu, $epl[$mu]);
   $epd[$ei] = $endpoint['mfr'];
   $hnd[$ei] = $endpoint['hndl'];
   $ei += 1;
}


// print_r($epd);
// print_r($hnd);


$indexTpl = file_get_contents("./index.php.tpl");
$tmptags = array('%cw_date_time%', '%Defined_Routes_and_Handlers%');
$tmpVals = array( date("r"), implode("\n", $epd));

$indexFile = str_replace( $tmptags, $tmpVals, $indexTpl);

file_put_contents( $write_to . '/index.php', $indexFile );

$plugins = array();

foreach($hnd as $hdd){
  if(!isset($plugins[$hdd['handler']])){
     $plugins[$hdd['handler']] = array();
  }
  $plugins[$hdd['handler']][$hdd['method']] = $hdd['code'];
}

$cxn = array_keys($plugins);

$tcode = '';

foreach($cxn as $handler){
  $fn = $write_to . 'plugins/' . $handler . '.php';

  $tcode .= '<' . '?' . 'php' . "\n";

$tcode .= '/'.'**'. "\n"
 .'* @package MariaFramework '. "\n"
 .'* @subpackage '.$handler.' plugin '. "\n"
 .'**'.'/'. "\n\n"
 .'class '.'user{ ' . "\n";

  foreach($plugins[$handler] as $method => $xcode ){
   $tcode .= '/' . '*' . "\n";
   $tcode .= '@method ' . $method . "\n";
   $tcode .= '*' .'/' . "\n";
   $tcode .= $xcode . "\n\n";
  }

  $tcode .= '}';
 
  file_put_contents($fn, $tcode);
  $tcode = '';
  echo "Wrote $fn \n";
}
