<?php
#as minimal and functional of a web interface for MELEE users as I could create.
function main() {
    global $db, $login, $mode, $submode, $subsubmode, $action, $subaction, $uniq, $thousands, $decimals ;
    #  ini_set('display_errors',1);
    #  ini_set('display_startup_errors',1);
    #  error_reporting(-1);
    if ($_SERVER['HTTPS'] != 'on') {
        header("Location: https://$_SERVER[HTTP_HOST]/$_SERVER[REQUEST_URI]");
        print "<a href=\"https://$_SERVER[HTTP_HOST]/$_SERVER[REQUEST_URI]\">SSL Required - click here</a>  - No SSL? See LetsEncryt.org for a FREE and GOOD SSL Certificate\n\n";
        return;
    };
    include_once ("glass-core.php");
    include ("settings.inc");
    $debug = false;
    #all header/cookie stuff first
    session_start(); #may be used in other places. Sets the PHPSESSID cookie if not set.
    $path = dt($_SERVER['PATH_INFO']);
    list($mode, $submode, $subsubmode, $subsubsubmode, $subsubsubsubmode) = preg_split('/\//', $path, -1, PREG_SPLIT_NO_EMPTY);
    $mode = dtpci($mode);
    $submode = dtpci($submode);
    $subsubmode = dtpci($subsubmode);
    $action = dtpci($_REQUEST['action']);
    $subaction = dtpci($_REQUEST['subaction']);
    $level = 0;
    if ($mode == 'login' or $submode == 'login') {
        list($uniq, $login, $name, $level, $perms) = glauth();
    }
    #start interface
    #I'm using cutestrap from http://cutestrap.com - not included, but free and easy to install. 
    print <<<EOF
<!DOCTYPE html>
<html>
 <head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
   <meta name="apple-mobile-web-app-capable" content="yes">
   <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, maximum-scale=1.0">
  <meta name="apple-touch-fullscreen" content="yes">
  <title>melee $listname control</title>
  <meta name="description" content="$listname MELEE Mail List Engine Web Interface">
  <link rel="stylesheet" href="$webroot/css/dist/css/cutestrap.min.css">
</head>
<body style="background-color:#ffffff;color:#000000;">
EOF;
#$debug = true ; 
    if ($debug) {
        print "mode: $mode submode: $submode subsubmode: $subsubmode action: $action subaction: $subaction level: $level<br>\n";
    };
    if (empty($mode)) {
        #welcome screen.
        include ("welcome.html");
    };
    if ($mode == 'learnmore.html') {
        include ("learnmore.html");
    };
    if ($mode == 'melee.html') {
        include ("melee.html");
    };
    if (($mode == 'login' or $submode == 'login') and $level > 1) {
        $member = gaafm("select uniq as Member,email as Email, name as Name,created as `Member Since`,status as Status,digest,recv,sent,bounced,asshole,hush,publickey,sign,encrypt from members where uniq = '$uniq' limit 1");
#        print "<h3>Member</h3><p>This is very very bare... for now. No actual editing yet.</p>";
#        print glisttable($member);
if($subsubmode == 'update' or $submode == 'update') { 
  $name = dt($_REQUEST['name']) ; #not real efficient, but sure is easy to debug/extend ;)
  runsql("update members set name = '$name' where uniq = '$member[Member]'") ; 
  $name = dt($_REQUEST['name']) ; #not real efficient, but sure is easy to debug/extend ;)
  runsql("update members set name = '$name' where uniq = '$member[Member]'") ; 
  $publickey = dtless($_REQUEST['publickey']) ;
  runsql("update members set publickey = '$publickey' where uniq = '$member[Member]'") ; 
  if($_REQUEST['gpgmode'] == 'sign') { 
    runsql("update members set sign = '1', encrypt='0' where uniq = '$member[Member]'") ; 
  } elseif ($_REQUEST['gpgmode'] == 'encrypt'){ 
    runsql("update members set sign = '0', encrypt='1' where uniq = '$member[Member]'") ; 
  } else { 
    runsql("update members set sign = '0', encrypt='0' where uniq = '$member[Member]'") ; 
  } ; 
  $digest = dt($_REQUEST['digest']) ; 
  if($digest == 'on') { 
    runsql("update members set digest = '1' where uniq = '$member[Member]'") ; 
  } else { 
    runsql("update members set digest = '0' where uniq = '$member[Member]'") ; 
  } ;   
  $member = gaafm("select uniq as Member,email as Email, name as Name, created,level,status as Status,digest,recv,sent,bounced,asshole,hush,publickey,sign,encrypt from members where uniq = '$uniq' limit 1");
} ; 

#css used is cutestrap. 


$target = "$webroot/index.php/login/update" ; 
print "<form ACTION='$target' METHOD='post' class='wrapper' style='max-width: 50rem'>\n" ; 
print "<H4>Member# $member[Member]<br>$member[Email]</H4>" ;
print "Status: <b>$member[Status]</b></br>Since: <b>$member[created]</b> Karma: <b>$member[level]</b><br>Sent: <b>$member[sent]</b> Received: <b>$member[recv]</b> Bounced: <b>$member[bounced]</b><br><hr>" ; 
#print "<label class=\"field\"><input type=\"text\" name=email id=email value=\"$member[Email]\" READONLY/><span class=\"label\">E-Mail</span></label>" ; 
#print "<label class=\"field\"><input type=\"text\" name=status id=status value=\"$member[Status]\" READONLY/><span class=\"label\">Status</span></label>" ; 
print "<label class=\"field\"><input type=\"text\" name=name id=name value=\"$member[Name]\"/><span class=\"label\">Name</span></label>" ; 
if($member['digest'] > 0 ) { $CHECKED = 'CHECKED' ; } else { $CHECKED = '' ; } ;  
print "<label class=\"field\"><input name=digest id=digest type=\"checkbox\" $CHECKED/><span class=\"label\">Digest Mode</span></label>" ; 
if($member['encrypt'] < 1 and $member['sign'] < 1) { $CHECKED = 'CHECKED' ; } else { $CHECKED = '' ; } ;  
print "<label class=\"field\"><input name=gpgmode id=gpgmode type=\"radio\" value=none $CHECKED/><span class=\"label\">Neither Sign or Encrypt</span></label>" ; 
if($member['sign'] > 0 ) { $CHECKED = 'CHECKED' ; } else { $CHECKED = '' ; } ;  
print "<label class=\"field\"><input name=gpgmode id=gpgmode type=\"radio\" value=sign $CHECKED/><span class=\"label\">GPG sign my email</span></label>" ; 
if($member['encrypt'] > 0 ) { $CHECKED = 'CHECKED' ; } else { $CHECKED = '' ; } ;  
print "<label class=\"field\"><input name=gpgmode id=gpgmode type=\"radio\" value=encrypt $CHECKED/><span class=\"label\">GPG encrypt my email</span></label>" ; 
print "<label class=\"field\"><textarea name=publickey>$member[publickey]</textarea><span class=\"label\">Public Key</span></label>" ; 
print "<input type=submit value='Update' class=button>" ; 


        print "<hr><h3>$listname</h3>";
        list($members, $recv, $sent) = gafm("select count(uniq),sum(recv),sum(sent) from members");
        $sent = intnff($sent) ; $recv = intnff($recv) ; $members = intnff($members) ; 
        print "<b>$members</b> active members that have sent <b>$sent</b> emails to the list and generated <b>$recv</b> emails, not counting administrative emails.";
        print "</form>" ; 
        $listpublickey = @file_get_contents('publickey.gpg') ; 
        print "<h5  class='wrapper' style='max-width: 50rem'>List Public GPG Key</h5><center><pre style='max-width: 50rem; font-size:small;text-align:left'>" ; 
        print "$listpublickey" ; 
        print "</pre></center>" ; 
        
    };
}
main();
?>