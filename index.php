<?php
#as minimal and functional of a web interface for MELEE users as I could create.
function main() {
    global $db, $login, $mode, $submode, $subsubmode, $action, $subaction, $uniq;
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
    if ($mode == 'login') {
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
  <meta name="description" content="ring-u plug and play business phone system">
  <link rel="stylesheet" href="$webroot/css/dist/css/cutestrap.min.css">
</head>
<body style="background-color:#ffffff;color:#000000;">
EOF;
    if ($debug) {
        print "mode: $mode submode: $submode subsubmode: $subsubmode action: $action subaction: $subaction <br>\n";
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
    if ($mode == 'login' and $level > 1) {
        $member = gaaafm("select uniq as Member,email as Email, name as Name,created as `Member Since`,status as Status,digest,recv,sent,bounced,asshole,hush,publickey,sign,encrypt from members where uniq = '$uniq' limit 1");
        print "<h3>Member</h3><p>This is very very bare... for now. No actual editing yet.</p>";
        print glisttable($member);
        print "<h3>$listname</h3>";
        list($members, $recv, $sent) = gafm("select count(uniq),sum(recv),sum(sent) from members");
        print "<b>$members</b> active members that have sent: <b>$sent</b> emails to the list and generated <b>$recv</b> emails, not counting subscribe/unsubscribe and administrative emails.";
    };
}
main();
?>