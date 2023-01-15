<?php

    if (!isset($home_path)) $home_path="..";
    require_once $home_path."/include/basics.php";
    require_once $home_path."/include/bitmap_fonts.php";

//include/bitmap_line.php?fnt=sw_12_purp_med&txt=syndicate

    $txt = $_GET["txt"];
    $fnt = $_GET["fnt"];
    if (!is_string($txt)) $txt="";
    if (!is_string($fnt)) $fnt="";
    $fnt = trim($fnt);
    if (preg_match("/:/i", $fnt)||preg_match("/\//i", $fnt)||preg_match("/\.\./i", $fnt)) $fnt="";
    if ((strlen($fnt)<5)||(strlen($fnt)>32))
      $fnt="";

    $txt = stripslashes($txt);
    $txt = trim($txt);
    if (strlen($txt)>128)
      $txt=substr($txt,0,128);

   if  ( (strlen($fnt)<1) || (strlen($txt)<1) )
   {
     echo "Wrong generator input data.\n";
     return;
   }

   header("Content-type: image/png");

   $font="font_".$fnt;

   $text_arr = str_split( $txt );

//   $text_arr = preg_split('//', $txt, -1, PREG_SPLIT_NO_EMPTY);

   $file_list = array ();

    foreach($text_arr as $chr)
    {
      array_push ($file_list,getBFontCharFile($font,$chr));
    }

   printMergedImages($file_list);


?>
