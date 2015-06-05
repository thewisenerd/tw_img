<?php

  /* author: Vineeth Raj <contact.twn@openmailbox.org>
   * twitter (image) fetcher for forum signatures;
   * options:
   *   ?id=<twitter widget id>
   *   ?text=<fallback text>
   *   ?font=<font size 1 - 5>
   *   ?align=<left|center|right>
   *   ?type=jpeg|gif|png|webp(experimental)
   *   ?color=<html color hex; argb>
   *   ?bg_color=<html color hex; argb>|transparent
   *
   * todo: add emoji support
   *       add twitter image support
   */

include ('simple_html_dom.php');

// default _str is date
$_str = date('m/d/Y h:i:s a', time());

// default _font size/type is 3
$_font = (int) 3;

// image type
$_type = 'jpeg';

// _align
$_align = 'center';

$_type_s = array('jpeg', 'gif', 'png', 'webp');
//webp is experimental? don't use!
if ($_GET["type"]) {
  if (in_array(($_GET["type"]), $_type_s)) {
    $_type = $_GET["type"];
  }
}

if ($_GET["size"]) {
  if (((int) $_GET["size"] < 6) && ((int) $_GET["size"] > 0)) {
    $_font = (int) $_GET["size"];
  }
}

$_align_s = array("left", "center", "right");
if ($_GET["align"]) {
  if (in_array($_GET["align"], $_align_s)) {
    $_align = $_GET["align"];
  }
}

if ($_GET["text"]) {
  $str = $_GET["text"];
}

if ($_GET["id"]) {

$callbackfn = 'cb0';

$lyr1 = 'https://syndication.twitter.com/widgets/timelines/preview/?callback=' . $callbackfn . '&do_not_track=true&expand_media=true&height=350&hide_at_replies=true&lang=en&suppress_response_codes=true&theme=light';
$lyr2 = '&timeline_id=' . $_GET["id"];
$lyr3 = '&timeline_type=user';


  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, ($lyr1 . $lyr2 . $lyr3));
  curl_setopt($ch, CURLOPT_HEADER, 0);
  curl_setopt($ch, CURLOPT_REFERER, '');
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);
  $return = curl_exec($ch);

  if ($return == true) {
    if (/*(curl_errno($ch) == 0) && */(strlen($return))) {
      // if $return == true; curl_errno($ch) automatically becomes zero /* ? */
      
      //$arr = json_decode($return);
      //$_html = $arr->body;

      $_retval =         substr(
          $return, 
          5 + strlen($callbackfn),
          (strlen($return) - (2 + (5 + strlen($callbackfn))) )
        );

      //echo $_retval;

      $arr = json_decode($_retval);
      //print_r($arr);
      //exit;

      //$str_array = explode("\r\n", $return);
      //$_str = $str_array[array_rand($str_array)];
    }
  } else {
    //echo curl_errno($ch) /* ??? */;
    //exit();
    ;
  }

  curl_close($ch);

}

function fix_tweet($var) {
  $ret = '';
  $ret .= htmlspecialchars_decode($var);
  
  //newline 
  $ret = str_replace("&#10;", "\n", $ret);
  //quote
  $ret = str_replace("&#39;", "'", $ret);
  //nbsp
  $ret = str_replace("&nbsp;", " ", $ret);


  return $ret;
}

$_html = $arr->body;

$is_set = 0;
$_tweet_to_work_with = '';

$var = str_get_html($_html);
foreach($var->find('div.e-entry-content') as $tweet) {
  //echo $tweet . "\n";
  if ($is_set)
    break;

  $_tweet_to_work_with = fix_tweet($tweet->find('p.e-entry-title')[0]->plaintext) . "\n";
  $is_set = 1;

  if ($tweet->find('div.inline-media', 0)) {
    $is_set = 0;
    // echo "has media" . "\n";
    //todo: handle media
    // j = $tweet->find('img.autosized-media')[0];
    // j.explode(' ')
    // j.search(data-srcset)
    // j.parse (image etc etc)
    // yada yada
  }
  //error_log($tweet);
  //echo '--------------------------------------------------------------------------------' . "\n";
  // ;
}
//echo $var;

$j = $_tweet_to_work_with;

//echo $j;
$_str = str_replace("\n", "\\n", $j);

//exit;

$_str_has_auth = (bool) false;

if (strpos($_str, "\\a")) {
  $_str_has_auth = (bool) true;
}

$exploded_string = explode("-|-", str_replace(array('\n', '\a'), "-|-", $_str));

$max_len = strlen($exploded_string[0]);

for ($i = 0; $i < count($exploded_string); $i++) {
  if (strlen($exploded_string[$i]) > $max_len) {
    $max_len = strlen($exploded_string[$i]);
  }
}

// now that we have max length, set width of image (margin-left|right: 1 font size)
$_width = ($max_len * imagefontwidth($_font)) + (2 * imagefontwidth($_font));
$_height = count($exploded_string) * imagefontheight($_font);

function align($str, $align) {
  if (strcasecmp($align, "right") == 0) {
    return (int) ($GLOBALS['_width'] - (imagefontwidth($GLOBALS['_font']) * (strlen($str) + 1)));
  } else if (strcasecmp($align, "center") == 0) {
    return (int) (($GLOBALS['_width'] - (imagefontwidth($GLOBALS['_font']) * strlen($str))) / 2);
  }

  //align = left; by def.
  return imagefontwidth($GLOBALS['_font']);
}

function opp_align($align) {
  // oneliners ftw.
  // offset array by 2 variables; get the 'opposite' align.
  return $GLOBALS['_align_s'][abs(array_search($align, $GLOBALS['_align_s']) - 2)];
}

//Create the image resource
$image = ImageCreate($_width, $_height);

//We are making three colors, white, black and gray
$white = ImageColorAllocate($image, 255, 255, 255);
$black = ImageColorAllocate($image, 0, 0, 0);

// default text color: black;
$_color = $black;

$_color_val = array();

if ($_GET["color"]) {
  if ((strlen($_GET["color"]) == 6) || (strlen($_GET["color"]) == 8)) {
    //argb -> bgra
    for ($i = strlen($_GET["color"]); $i > 0; $i = $i - 2) {
      $alpha8 = hexdec((substr($_GET["color"], $i - 2, 2)));
      array_push($_color_val, $alpha8);
    }

    if (count($_color_val) == 3) {
      $_color = imagecolorallocate($image, $_color_val[2], $_color_val[1], $_color_val[0]);
    } else { // count($_color_val) == 4
      // 7-bit, not 8-bit; for php
      $alpha7 = ((~((int)$_color_val[3])) & 0xff) >> 1;
      $_color = imagecolorallocatealpha($image, $_color_val[2], $_color_val[1], $_color_val[0], $alpha7);
    }
  }
}

// default bg color: white;
$_bg_color = $white;

$_bg_color_val = array();

if ($_GET["bg_color"]) {
  if ((strlen($_GET["bg_color"]) == 6) || (strlen($_GET["bg_color"]) == 8)) {
    //argb -> bgra
    for ($i = strlen($_GET["bg_color"]); $i > 0; $i = $i - 2) {
      $alpha8 = hexdec((substr($_GET["bg_color"], $i - 2, 2)));
      array_push($_bg_color_val, $alpha8);
    }

    if (count($_bg_color_val) == 3) {
      $_bg_color = imagecolorallocate($image, $_bg_color_val[2], $_bg_color_val[1], $_bg_color_val[0]);
    } else { // count($_color_val) == 4
      // 7-bit, not 8-bit; for php
      $alpha7 = ((~((int)$_bg_color_val[3])) & 0xff) >> 1;
      $_bg_color = imagecolorallocatealpha($image, $_bg_color_val[2], $_bg_color_val[1], $_bg_color_val[0], $alpha7);
    }
  }

  if ($_GET["bg_color"] == "transparent") {
    $_bg_color = imagecolorallocatealpha($image, 255, 255, 255, 127);
  }
}

//Make the background black
ImageFill($image, 0, 0, $_bg_color);

$loop_count = ($_str_has_auth == true) ? count($exploded_string) - 1 : count($exploded_string);

for ($i = 0; $i < $loop_count; $i++) {
  ImageString($image,
    $_font,
    align($exploded_string[$i], $_align),
    ($i * imagefontheight($_font)),
    $exploded_string[$i],
    $_color);
}

if ($_str_has_auth) {
  ImageString($image,
    $_font,
    align("-- ". $exploded_string[$loop_count], opp_align($_align)),
    (($loop_count) * imagefontheight($_font)),
    "-- ". $exploded_string[$loop_count],
    $_color);
}

//Tell the browser what kind of file is come in
header("Content-Type: image/" . $_type);

//$_type_s = array("jpeg", "gif", "png", "webp");
switch ($_type) {
  case "gif":
    imagegif($image);
    break;

  case "png":
    imagepng($image);
    break;

  case "webp":
    $img_create_ret = imagewebp($image);
    if ($img_create_ret == false) {
      //fallback?
      imagepng($image);
    }
    break;

    default:
      //jpeg as default
      imagejpeg($image);
      break;
}

//Free up resources
ImageDestroy($image);

exit();

?>
