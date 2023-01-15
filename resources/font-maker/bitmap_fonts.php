<?php

function printFileAsText($outhnd,$filename,$max_lines=16777216,$name_attr='')
{
    $fd = fopen ($filename, "r");
    if ($fd==FALSE)
    {
      fprintf($outhnd,"File not available, sorry.\n");
      return;
    }
    while (!feof ($fd))
    {
       $buffer = fgets($fd, 4096);
       $lines[] = $buffer;
    }
    fclose ($fd);

    $num_lines = count ($lines);
    if ($num_lines>$max_lines)
        $num_lines=$max_lines;

    fprintf($outhnd,"<pre%s>",(empty($name_attr) ? "" : " id=\"$name_attr$num_str\""));
    $i=0;
    while($i < $num_lines)
    {
           fprintf($outhnd,"%s",htmlspecialchars($lines[$i]));
           $i++;
    }
    fprintf($outhnd,"</pre>\n");

}

function printFileContent($lnseparator,$filename,$max_lines=16777216)
{
    $fd = fopen ($filename, "r");
    if ($fd==FALSE)
    {
      echo "File not available, sorry.\n";
      return;
    }
    while (!feof ($fd))
    {
       $buffer = fgets($fd, 4096);
       $lines[] = $buffer;
    }
    fclose ($fd);

    $num_lines = count ($lines);
    if ($num_lines>$max_lines)
        $num_lines=$max_lines;

    $i=0;
    while($i < $num_lines)
    {
           echo htmlspecialchars($lines[$i]).$lnseparator;
           $i++;
    }

}

function printFileContentPar($outhnd,$lnseparator,$filename,$max_lines=16777216)
{
    $fd = fopen ($filename, "r");
    if ($fd==FALSE)
    {
      fprintf($outhnd,"File not available, sorry.\n");
      return;
    }
    while (!feof ($fd))
    {
       $buffer = fgets($fd, 4096);
       $lines[] = $buffer;
    }
    fclose ($fd);

    $num_lines = count ($lines);
    if ($num_lines>$max_lines)
        $num_lines=$max_lines;

    $i=0;
    if($i < $num_lines)
    {
           fprintf($outhnd,"%s",htmlspecialchars($lines[$i]));
           $i++;
    }
    while($i < $num_lines)
    {
           if ($lines[$i][0]!=' ') fprintf($outhnd,"%s",$lnseparator);
           fprintf($outhnd,"%s",htmlspecialchars($lines[$i]));
           $i++;
    }
}

function printFileBFont($font,$filename,$max_lines=16777216,$name_attr='')
{
    $fd = fopen ($filename, "r");
    if ($fd==FALSE)
    {
      echo "File not available, sorry.\n";
      return;
    }
    while (!feof ($fd))
    {
       $buffer = fgets($fd, 4096);
       $lines[] = $buffer;
    }
    fclose ($fd);

    $num_lines = count ($lines);
    if ($num_lines>$max_lines)
        $num_lines=$max_lines;

    $i=0;
    while($i < $num_lines)
    {
           printBFont($font,strtoupper($lines[$i]),$name_attr);
           echo "<br/>";
           $i++;
    }

}

function printFileBFontBlock($font,$filename,$max_lines=16777216,$name_attr='')
{
    $fd = fopen ($filename, "r");
    if ($fd==FALSE)
    {
      echo "File not available, sorry.\n";
      return;
    }
    while (!feof ($fd))
    {
       $buffer = fgets($fd, 4096);
       $lines[] = $buffer;
    }
    fclose ($fd);

    $num_lines = count ($lines);
    if ($num_lines>$max_lines)
        $num_lines=$max_lines;

    $i=0;
    while($i < $num_lines)
    {
           $act_line=trim($lines[$i]);
           if (strlen($act_line)>0)
               echo getBFontBlock($font,strtoupper($act_line),$name_attr);
           echo "<br/>";
           $i++;
    }

}

function getBRollParentTags($font,$text,$name_attr='')
{
    global $home_path;
    $img_url=$home_path."/include/bitmap_line.php?fnt=".$font."_med&amp;txt=".urlencode($text);
    $img_act=$home_path."/include/bitmap_line.php?fnt=".$font."_light&amp;txt=".urlencode($text);
    return " onmouseover=\"document.".$name_attr.".src='$img_act'\" onmouseout=\"document.".$name_attr.".src='$img_url'\"";
}

function getBRollFontBlock($font,$text,$name_attr='')
{
    global $home_path;

    $img_url=$home_path."/include/bitmap_line.php?fnt=".$font."_med&amp;txt=".urlencode($text);
    $img_act=$home_path."/include/bitmap_line.php?fnt=".$font."_light&amp;txt=".urlencode($text);
    $attributes="style=\"border: 0; margin: 0; position: relative; top: -3px;\" alt=\"".htmlspecialchars($text)."\" " . (empty($name_attr) ? '' : "name=\"$name_attr\" ");
    return "<img src=\"".$img_url."\" " . $attributes ." onload=\"F_loadRollover(this,'".$img_act."',0)\"/>";

/*
    $img_url=$home_path."/include/bitmap_btntext.php?fnt=".$font."&txt=".urlencode($text);
    $attributes="style=\"border: 0; margin: 0;\" alt=\"".htmlspecialchars($text)."\" " . (empty($name_attr) ? '' : "id=\"$name_attr$num_str\" ");
    return "<img src=\"".$img_url."\" " . $attributes . "/>";
*/
}


function printBFont($font,$text,$name_attr='')
{
    $text_array = preg_split('//', $text, -1, PREG_SPLIT_NO_EMPTY);
    $char_num=0;
    echo "<span>";
    foreach ($text_array as $single_chr)
    {
        echo printBFontChar($font,$single_chr,$char_num,$name_attr);
        $char_num++;
    }
    echo "</span>";
    echo "\n";
}

function printBFontChar($font,$chr,$char_num,$name_attr='')
{
    global $home_path;
    if ( ord($chr) < 32 )
        return "";
    $chrcode=ord($chr)-32;
    $fontfile = sprintf('pic%04d.png',$chrcode);
    $num_str = sprintf('%03d',$char_num);
    $attributes="style=\"border: 0; margin: 0;\" alt=\"$chr\" " . (empty($name_attr) ? '' : "id=\"$name_attr$num_str\" ");
    return "<img src=\"".getBFontCharFile($font,$chr)."\"  " . $attributes . "/>";
}

function getBFontBlock($font,$text,$name_attr='')
{
    global $home_path;
    $img_url=$home_path."/include/bitmap_line.php?fnt=".urlencode($font)."&amp;txt=".urlencode($text);
    $attributes="style=\"border: 0px; margin: 0px; position: relative; top: -3px;\" alt=\"".htmlspecialchars($text)."\" " . (empty($name_attr) ? '' : "id=\"$name_attr\" ");
    return "<img src=\"".$img_url."\" " . $attributes . "/>";
}

function loadImagesToArray($src)
{
    $imgBuf = array ();
    foreach ($src as $link)
    {
       switch(substr ($link,strrpos ($link,".")+1))
       {
           case 'png':
               $iTmp = @imagecreatefrompng($link);
               break;
           case 'gif':
               $iTmp = @imagecreatefromgif($link);
               break;               
           case 'jpeg':           
           case 'jpg':
               $iTmp = @imagecreatefromjpeg($link);
               break;               
       }
       if ($iTmp==FALSE) continue;


       // This line makes proper transparency in image
       imagefill( $iTmp, 0, 0, $white );
       array_push ($imgBuf,$iTmp);
    }
    return $imgBuf;
}

function mergeImages($imgBuf,$under=0)
{
    $maxW=0; $maxH=0;
    foreach ($imgBuf as $iTmp)
    {
       if ($under)
       {
           $maxW=(imagesx($iTmp)>$maxW)?imagesx($iTmp):$maxW;
           $maxH+=imagesy($iTmp);
       } else
       {
           $maxW+=imagesx($iTmp);
           $maxH=(imagesy($iTmp)>$maxH)?imagesy($iTmp):$maxH;
       }
    }
    if ( ($maxW<1) || ($maxH<1) ) return NULL;
    $iOut = imagecreate ($maxW,$maxH) ;
    $transpar=imagecolorallocate($iOut,0,0,0);
    imagecolortransparent($iOut,$transpar);
    $pos=0;
    foreach ($imgBuf as $img)
    {
       if ($under)
       {
           imagecopy ($iOut,$img,0,$pos,0,0,imagesx($img),imagesy($img));
           imagefill( $iOut, 0, $pos, $transpar );
       } else
       {
           imagecopy ($iOut,$img,$pos,0,0,0,imagesx($img),imagesy($img));
           imagefill( $iOut, $pos, 0, $transpar );
       }
       $pos+= $under ? imagesy($img) : imagesx($img);
       imagedestroy ($img);
    }
    return $iOut;
}

function printMergedImages($src,$under=0)
{
    $imgBuf=loadImagesToArray($src);
    $iOut=mergeImages($imgBuf,$under);
    imagegif($iOut);
}

function printMergedImages4ln($src1,$src2,$src3,$src4)
{
    $imgRows = array ();
    $imgBuf=loadImagesToArray($src1);
    $iTmp=mergeImages($imgBuf,0);
    array_push ($imgRows,$iTmp);
    $imgBuf=loadImagesToArray($src2);
    $iTmp=mergeImages($imgBuf,0);
    array_push ($imgRows,$iTmp);
    $imgBuf=loadImagesToArray($src3);
    $iTmp=mergeImages($imgBuf,0);
    array_push ($imgRows,$iTmp);
    $imgBuf=loadImagesToArray($src4);
    $iTmp=mergeImages($imgBuf,0);
    array_push ($imgRows,$iTmp);
    $iOut=mergeImages($imgRows,1);
    $type=gettype($iOut);
    if ($type=="resource")
    {
      imagegif($iOut);
    }
    else
    {
      echo "Error: returned type ".$type;
      echo $iOut;
    }
}

function getBFontCharFile($font,$chr)
{
    global $home_path;
    if ( ord($chr) < 32 )
        $chr=32;
    $chrcode=ord($chr)-32;
    $fontfile = sprintf('pic%04d.png',$chrcode);
    return $home_path."/".$font."/$fontfile";
}

function img($url, $alt, $wid, $hei, $id = '')
{
    if ($wid != 0) $wid .= 'px'; if ($hei != 0) $hei .= 'px';
    return "<img src=\"images/$url\" alt=\"$alt\" style=\"width:$wid;height:$hei;\" " . (empty($id) ? '' : "id=\"$id\" ") . "/>";
}

function printBlock($text, $block_type = 'p')
{
    echo "<$block_type>" . htmlentities($text) . "</$block_type>";
}

function printSWBriefing($platform,$campaign,$mail_idx)
{
  $camp_ident="";
  if (strlen($campaign)>0)
  {
    if ($campaign{0}=='E')
      $camp_ident="euro";
    else if ($campaign{0}=='C')
      $camp_ident="church";
    else if ($campaign{0}=='D')
      $camp_ident="demo";
  }
  if ((strlen($camp_ident)>0)&&($mail_idx>=0))
    printSWBriefingFile(sprintf("../walkthrough/swars_brief_%s_%s%02d.txt",$platform,$camp_ident,$mail_idx));
  else
    print("<p>No briefing available for this level.</p>\n");
}

function printSWBriefingFile($filename,$max_lines=512)
{
    $fd = fopen ($filename, "r");
    if ($fd==FALSE)
    {
      print("<p>Briefing file not available, sorry.</p>\n");
      return;
    }
    while (!feof ($fd))
    {
       $buffer = fgets($fd, 4096);
       $lines[] = $buffer;
    }
    fclose ($fd);

    $num_lines = count ($lines);
    if ($num_lines>$max_lines)
        $num_lines=$max_lines;
    echo "<p><span>";
    $i=0;
    while($i < $num_lines)
    {
           $line=htmlspecialchars($lines[$i]);
           $line=str_replace("\\n","<br/>",$line);
           $line=str_replace("\\h","</span><span class=\"brief_h\">",$line);
           $line=str_replace("\\c1","</span><span class=\"brief_c1\">",$line);
           $line=str_replace("\\c2","</span><span class=\"brief_c2\">",$line);
           $line=str_replace("\\c3","</span><span class=\"brief_c3\">",$line);
           $line=str_replace("\\c4","</span><span class=\"brief_c4\">",$line);
           $line=str_replace("\\c5","</span><span class=\"brief_c5\">",$line);
           $line=str_replace(", \\l","",$line);
           $line=str_replace("\\l, ","",$line);
           $line=str_replace(" \\l","",$line);
           echo $line;
           $i++;
    }
    echo "</span></p>";

}

?>
