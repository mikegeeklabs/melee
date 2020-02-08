<?php
function glconnect() {
    // database connector
    global $db, $mode, $submode, $subsubmode, $subsubsubmode, $action, $lang, $logic, $csspath;
    //get credentials from settings.inc
    include ('settings.inc');
    $db = mysqli_connect("$dbserver", "$dblogin", "$dbpasswd", "$database");
    if ($db->connect_errno) {
        print "No SQL (" . $db->connect_errno . ") " . $db->connect_error;
    }
    #set timezone
    if (!empty($timezonetext)) {
        ini_set('date.timezone', "$timezonetext");
    };
    if (!empty($timezonenumber)) {
        runsql("SET time_zone = '$timezonenumber'");
    };
    #runsql("set sql_mode='ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION'") ;
    runsql("set sql_mode='ONLY_FULL_GROUP_BY,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION'");
    return $db;
    #set language
    return $db;
};
function bbf($input) {
    return $input;
};
function gafm($query) {
    global $db;
    if (!$db || empty($db)) {
        $db = glconnect();
    };
    $a = array(); //declare the array
    $result = $db->query($query) or die("gafm failed:<br>\n$query<br>\n" . $db->connect_errno . " : " . $db->connect_error . "<br>\n");
    $result->data_seek(0);
    $a = array_values($result->fetch_assoc());
    return $a;
};
function gsfm($query) {
    global $db;
    if (!$db || empty($db)) {
        $db = glconnect();
    };
    $a = array(); //declare the array
    $result = $db->query($query) or die("gafm failed:<br>\n$query<br>\n" . $db->connect_errno . " : " . $db->connect_error . "<br>\n");
    $result->data_seek(0);
    #  if( is_null($result->fetch_assoc() )) {
    #    return '' ;
    #  } else {
    $a = @array_values($result->fetch_assoc());
    return $a[0];
    #  } ;
    
};
function gaafm($query) {
    //Get Associative Array from MySQL
    global $db;
    if (!$db || empty($db)) {
        $db = glconnect();
    };
    $a = array(); //declare the array
    $result = $db->query($query) or die("gaafm failed:<br>\n$query<br>\n" . $db->connect_errno . " : " . $db->connect_error . "<br>\n");
    $result->data_seek(0);
    $a = $result->fetch_assoc();
    return $a;
};
function gaaafm($query) {
    //Get Indexed Associative Array from MySQL
    global $db;
    if (!$db || empty($db)) {
        $db = glconnect();
    };
    $a = array(); //declare the array
    $result = $db->query($query) or die("gaaafm failed:<br>\n$query<br>\n" . $db->connect_errno . " : " . $db->connect_error . "<br>\n");
    if (is_object($result)) {
        $result->data_seek(0);
        $i = 0;
        while ($row = $result->fetch_assoc()) {
            $a[$i] = $row;
            $i++;
        };
        return $a;
    } else {
        return array();
    };
};
function glafm($query) {
    //Get List  Array from MySQL
    global $db;
    if (!$db || empty($db)) {
        $db = glconnect();
    };
    $a = array(); //declare the array
    $result = $db->query($query) or die("gaaafm failed:<br>\n$query<br>\n" . $db->connect_errno . " : " . $db->connect_error . "<br>\n");
    if (is_object($result)) {
        $result->data_seek(0);
        $i = 0;
        while ($row = mysqli_fetch_array($result, MYSQLI_NUM)) {
            array_push($a, $row[0]);
        };
        return $a;
    } else {
        return array();
    };
};
function runsql($query) {
    global $db;
    if (!$db || empty($db)) {
        $db = glconnect();
    };
    $result = $db->query($query) or die("runsql failed:<br>\n$query<br>\n" . $db->connect_errno . " : " . $db->connect_error . "<br>\n");
    return $result;
};
function glist($a) {
    foreach ($a as $k => $v) {
        foreach ($v as $key => $val) {
            print "$key = $val \n";
        };
    };
};
function glisttable($a) {
    $stuff = "<table>\n";
    foreach ($a as $k => $v) {
        foreach ($v as $key => $val) {
            $stuff.= "<tr><td>$key</td><td>$val</td></tr>\n";
        };
    };
    $stuff.= "</table>\n";
    return $stuff;
};
function glprintr($thing) {
    print "<pre>" . print_r($thing, 1) . "</pre>";
};
function gltable($a, $toton) {
    $header = '<table>';
    $footer = '</table>';
    $th = '';
    $theader = '';
    $tfooter = '';
    $trow = '';
    $totals = array();
    foreach ($a as $k => $v) {
        $trow.= '<tr>';
        foreach ($v as $key => $val) {
            if (empty($theader)) {
                $th.= "<th>$key</th>";
            }; //builder a table header ;
            if ($key == 'number') {
                $val = phormat($val);
            };
            $trow.= '<td>' . $val . '</td>';
            if (@in_array($key, $toton)) {
                $totals["$key"]+= ($val * 1);
            };
        };
        $trow.= '</tr>' . "\n";
        if (empty($theader)) {
            $theader = "<tr>$th</tr>\n";
        }; //builder a table header ;
        
    };
    if (!empty($totals)) { //a total line
        foreach ($a as $k => $v) {
            $tfooter = '<tr>';
            foreach ($v as $key => $val) {
                if (@in_array($key, $toton)) {
                    $tfooter.= "<td>$totals[$key]</td>";
                } else {
                    $tfooter.= '<td></td>';
                }
            };
            $tfooter.= '</tr>';
        };
    };
    return "$header\n$theader\n$trow\n$tfooter\n$footer";
};
function dt($string) {
    global $lang;
    if (empty($string)) {
    } else {
        $string = trim($string);
        $strip = array('/^ /', '/\s+$/', '/\$/', '/\n/', '/\r/', '/\n/', '/\,/', '/\:/', '/\@/', '/\%/', '/0x/');
        $string = preg_replace($strip, '', $string);
        $strip = array('/\'/');
        $string = preg_replace($strip, '&apos;', $string);
        # $string = mysql_escape_string($string) ;
        return $string;
    };
};
function dtpci($string) {
    global $lang;
    if (empty($string)) {
    } else {
        $string = trim($string);
        $string = strip_tags($string); #new per PCI
        $strip = array('/^ /', '/\s+$/', '/\$/', '/\n/', '/\r/', '/\n/', '/\,/', '/\:/', '/\@/', '/\%/', '/0x/', '/\'/', '/\"/', '/\</', '/\>/');
        $string = preg_replace($strip, '', $string);
        return $string;
    };
};
function dtfilename($string) {
    global $lang;
    if (empty($string)) {
    } else {
        $string = trim($string);
        $strip = array('/^ /', '/\s+/', '/\$/', '/\n/', '/\r/', '/\n/', '/\,/', '/\//', '/\&/', '/\@/', '/\%/', '/\(/', '/\)/', '/\.\./', '/0x/');
        $string = preg_replace($strip, '', $string);
        # $string = mysql_escape_string($string) ;
        return $string;
    };
};
function dtemail($string) {
    global $lang;
    if (empty($string)) {
        return '';
    } else {
        $string = trim($string);
        $string = strip_tags($string); #new per PCI
        $strip = array('/^ /', '/\s+$/', '/\$/', '/\n/', '/\r/', '/\n/', '/\,/', '/\:/', '/\%/', '/0x/', '/\'/', '/\"/', '/\</', '/\>/');
        $string = preg_replace($strip, '', $string);
        $strip = array('/\'/');
        $string = preg_replace($strip, '', $string);
        # $string = mysql_escape_string($string) ;
        return $string;
    };
};
function dtless($string) {
    global $lang;
    if (empty($string)) {
        return '';
    } else {
        $string = trim($string);
        $strip = array('/\'/');
        $string = preg_replace($strip, '&apos;', $string);
        # $string = mysql_escape_string($string) ;
        return $string;
    };
};
function dtpasswd($string) {
    global $lang;
    if (empty($string)) {
        return '';
    } else {
        $string = trim($string);
        $strip = array('/\'/');
        $string = preg_replace($strip, '', $string);
        #        $string = mysql_escape_string($string) ;
        return $string;
    };
};
function dtamt($string) {
    global $lang;
    $string = trim($string);
    if ($lang == 'pt' or $lang == 'fr') {
        $string = preg_replace("/[.]/", "", $string);
        $string = preg_replace("/[,]/", ".", $string);
    }
    $string = preg_replace("/[^0-9.]/", "", $string);
    return $string;
};
function dtnum($string) {
    global $lang;
    $string = preg_replace('/\D/', '', $string);
    return $string;
};
function glauth() {
    global $db, $lang, $script;
    $script = $_SERVER['PHP_SELF'];
    //Enforces HTTP Auth, MUST be absolutely first thing output, BEFORE Header!
    $level = 0;
    $posslogin = dtemail($_SERVER['PHP_AUTH_USER']); #sets login here, or in fakeauth.
    $posspasswd = dt($_SERVER['PHP_AUTH_PW']);
    $level = 0;
    $name = '';
    $perms = array();
    if (!isset($_SERVER['PHP_AUTH_USER'])) {
        header("WWW-Authenticate: Basic realm=\"Credentials Please (1)\"");
        header('HTTP/1.0 401 Unauthorized');
        print "<META HTTP-EQUIV=\"Refresh\" CONTENT=\"0; URL=$script?\">";
        gllog('auth','0 no user, unauth popup triggered') ; 
        exit;
    } else {
        gllog('auth',"1 $posslogin $posspasswd step1")  ; 
        #        list($login,$name,$level,$passwd) = gafm("select login,name,level,passwd from users where login = '$posslogin' limit 1") ;
        list($muniq,$login, $name, $level, $passwd) = gafm("select uniq,email,name,level,passwd from members where email = '$posslogin' limit 1");
        gllog('auth',"2 $posslogin $posspasswd got $login $name $passwd from members") ; 
        if (glpasswdverify($posspasswd, $passwd)) {
            gllog('auth',"3 $posslogin $posspasswd = $passwd") ; 
            } else {
            gllog('auth',"3 $posslogin $posspasswd != $passwd level set to 0") ; 
            $muniq = '' ; 
            $login = '';
            $name = '';
            $level = 0;
            $passwd = '';
        };
        #        if ($level > 0) {
        #            if(empty($_COOKIE['a'])) {
        #                $fromip = $_SERVER['REMOTE_ADDR'];
        #                $key = glakey();
        #                setcookie("a", gldesencrypt($fromip, $key)); #could be argued these need a secure and http flag set.
        #                setcookie("b", gldesencrypt("$login", $key)); #but the cookies are really just noise and useful as flags.
        #                setcookie("c", gldesencrypt("opensaysme", $key));
        #                setcookie("d", str_rot13($key));
        #             } ;
        #        };
        if ($level < 1) {
            header("WWW-Authenticate: Basic realm=\"Credentials Please (2)\"");
            header('HTTP/1.0 401 Unauthorized');
            print bbf('Error 401') . ' b<hr>' . bbf('You must have a valid login and password to access this system');
            gllog('auth',"4 $posslogin $posspasswd != $passwd level is 0 - reasked to auth ") ; 
            exit;
        };
    #        if($level > 0) { #useful in systems where there are complex perms.
    #         $perms = glafm("select perm from userperms where login = '$login'") ;
    #        } ;
    }; 
    return array($muniq,$login, $name, $level, $perms);
    
}; //end function glsimpleauth
function glakey() {
    #Generate a key used elsewhere. It is a password safe string. No ambiguous characters.
    $string = "aeiouBCDFHJKMNPQRTZ23456789QPZQB";
    srand(date("s"));
    $len = 20;
    $str = '';
    while (strlen($str) <= $len) {
        $c = rand(1, 28);
        $str.= substr("$string", $c, 1);
    }
    return $str;
};
function glashortkey() {
    #Generate a key used elsewhere. It is a password safe string. No ambiguous characters.
    $string = "23456782345678bqpthkx2345678923456789";
    srand(date("s"));
    $len = 6;
    $str = '';
    while (strlen($str) <= $len) {
        $c = rand(1, 28);
        $str.= substr("$string", $c, 1);
    }
    return $str;
};
function nff($number) {
    global $lang, $thousands, $decimals;
    $places = 2;
    if ($thousands != ',' or $decimals != '.') {
        $number = number_format($number, $places, "$decimals", "$thousands");
    } else {
        $number = number_format($number, $places, '.', ',');
    };
    return $number;
};
function gllog($log, $what) {
    global $portal, $login, $fromip;
    $dir = @fopen("logs", "r");
    if (!($dir)) {
        mkdir("logs");
    };
    @fclose($dir);
    if ($log == '') {
        $log = 'log';
    };
    $now1 = date("Ymd");
    $now2 = date("Ymd H:i:s");
    $fileout = fopen("logs/$now1.$log.log", "a");
    fputs($fileout, "$now2    $login    $fromip    $what\n");
    fclose($fileout);
};
function glpasswdhash($input) {
    #wrapped like this just for old code compatibility, PHP 7.3+ has all of this built it.
    $options = ['cost' => 12, ];
    $passwdhash = password_hash($input, PASSWORD_BCRYPT, $options);
    return $passwdhash;
};
function glpasswdverify($input, $existinghash) {
    #wrapped like this just for old code compatibility, PHP 7.3+ has all of this built it.
    if (password_verify($input, $existinghash)) {
        return true;
    } else {
        return false;
    };
};
function in_array_r($needle, $haystack, $strict = false) {
    foreach ($haystack as $item) {
        if (($strict ? $item === $needle : $item == $needle) || (is_array($item) && in_array_r($needle, $item, $strict))) {
            return true;
        }
    }
    return false;
}
function jsencode($string) {
    $newstring = $string;
    $newstring = str_replace("'", "\\'", $newstring);
    $newstring = str_replace("\n", "<br>", $newstring);
    $newstring = str_replace("\r", " ", $newstring);
    $newstring = str_replace("\"", "&quot;", $newstring);
    $newstring = str_replace("\:", " ", $newstring);
    $newstring = str_replace("\;", " ", $newstring);
    return $newstring;
};
function fauxphormat($number) {
    #simpler version. big version in portal.php
    $number = dtnum($number);
    if (strlen($number) == 12) {
        $number = '(' . substr($number, 2, 3) . ') ' . substr($number, 5, 3) . '-' . substr($number, 8, 4);
    };
    if (strlen($number) == 11) {
        $number = '(' . substr($number, 1, 3) . ') ' . substr($number, 4, 3) . '-' . substr($number, 7, 4);
    };
    if (strlen($number) == 10) {
        $number = '(' . substr($number, 0, 3) . ') ' . substr($number, 3, 3) . '-' . substr($number, 6, 4);
    };
    return "$number";
};
function ordinal($int) {
    #adds the ordinal to a date.
    $two = substr($int, -2);
    $day = $two < 32 ? $two : '2' . substr($two, -1);
    $suffix = date('S', strtotime('May ' . $day));
    return $int . $suffix;
}
function filesizeme($size) {
    if ($size > 1000000000) {
        $size = round($size / 10000000, 1) . 'Gb';
        return $size;
    };
    if ($size > 1000000) {
        $size = round($size / 1000000, 0) . ' mB';
        return $size;
    };
    if ($size > 1000) {
        $size = round($size / 1024, 0) . ' kB';
        return $size;
    };
    return $size;
};
function cnamascii($text) {
    $normal_characters = "a-zA-Z0-9\s";
    $normal_text = preg_replace("/[^$normal_characters]/", '', $text);
    return $normal_text;
}
?>
