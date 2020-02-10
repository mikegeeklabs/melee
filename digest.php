<?php
function createdigest() {
    global $db;
    include ('settings.inc');
    $db = glconnect();
    print "Connecting to $emailserver as $emaillogin\n";
    $mbox = imap_open("{" . $emailserver . ":143/novalidate-cert}INBOX", "$digestemaillogin", "$digestemailpasswd") or die(imap_last_error() . "<br>Connection Faliure!");
    $headers = @imap_headers($mbox) or die("Couldn't get emails or no new emails\n\n");
    $numEmails = sizeof($headers);
    echo "You have $numEmails in your INBOX\n";
    $now = date("Y-m-d");
    $DIGEST = "$now $listname Digest: $numEmails messages\n====================================\n\n";
    if($numEmails > 0) {
    for ($mid = 1;$mid < $numEmails + 1;$mid++) {
        $mailrawheader = imap_fetchbody($mbox, $mid, "0");
        $mailraw = imap_fetchbody($mbox, $mid, "");
        $someheaderinfo = imap_headerinfo($mbox, $mid);
        $size = $someheaderinfo->Size;
        list($hjunk, $mailrawbody) = preg_split("/\n\r/", $mailraw, 2);
        $headers = parse_rfc822_headers($mailrawheader); #cause I want ALL the headers.
        # $contenttype = $headers['Content-Type'];
        $optheaders = array($headers['References']);
        $from = $headers['From'];
        $to = $headers['To'];
        $subject = $headers['Subject'];
        #this will evolve:
        $content = $mailrawbody;
        if (base64_decode($mailrawbody, true)) {
            $content = base64_decode($content, true);
        };
        preg_match_all("/[\._a-zA-Z0-9-]+@[\._a-zA-Z0-9-]+/i", $from, $m); #borrowed, may not be great.
        $cleanfrom = strtolower(dtemail($m[0][0]));
        $DIGEST.= "\n--------------\nFrom: $from\nDate: $headers[Date]\nSubject;$subject\n\n";
        #now to decode the body, MIME and all.
        $struct = imap_fetchstructure($mbox, $mid);
        $parts = $struct->parts;
        $i = 0;
        if (!$parts) { /* Simple message, only 1 piece */
            $attachment = array(); /* No attachments */
            $content = imap_body($mbox, $mid);
            if (base64_decode($content, true)) {
                $content = base64_decode($content, true);
            };
        } else { /* Complicated message, multiple parts */
            $endwhile = false;
            $stack = array(); /* Stack while parsing message */
            $content = ""; /* Content of message */
            $attachment = array(); /* Attachments */
            while (!$endwhile) {
                if (!$parts[$i]) {
                    if (count($stack) > 0) {
                        $parts = $stack[count($stack) - 1]["p"];
                        $i = $stack[count($stack) - 1]["i"] + 1;
                        array_pop($stack);
                    } else {
                        $endwhile = true;
                    }
                }
                if (!$endwhile) {
                    /* Create message part first (example '1.2.3') */
                    $partstring = "";
                    foreach ($stack as $s) {
                        $partstring.= ($s["i"] + 1) . ".";
                    }
                    $partstring.= ($i + 1);
                    if (strtoupper($parts[$i]->disposition) == "ATTACHMENT") {
                        /*Attachment */
                        #                $attachment[] = array("filename" => $parts[$i]->parameters[0]->value,"filedata" => imap_fetchbody($mbox,$mid, $partstring));
                        $filename = $parts[$i]->parameters[0]->value;
                        $content.= "\n[attachment: $filename] \n"; #just so you know there was one...
                        
                    } elseif (strtoupper($parts[$i]->subtype) == "PLAIN") {
                        /*Message */
                        $content.= imap_fetchbody($mbox, $mid, $partstring);
                    }
                }
                if ($parts[$i]->parts) {
                    $stack[] = array("p" => $parts, "i" => $i);
                    $parts = $parts[$i]->parts;
                    $i = 0;
                } else {
                    $i++;
                }
            } /* while */
        } /* complicated message */
        if (base64_decode($content, true)) {
            $content = base64_decode($content, true);
        };
        $DIGEST.= $content;
        imap_delete($mbox, $mid);
    };
    $fortune = fortune();
    $DIGEST.= "\n\n\n\n===================================================================\n[$listname] $emailfrom Digested.\nThis is Alpha Code: It worked for the dev. Once, maybe twice.\nSend email to mike@geeklabs.com if you need to rant.\n\n$fortune";
    print $DIGEST;
    $members = gaaafm("select * from members where status = 'active' and level > 0 and digest > 0 and bounced < 3 order by email,uniq");
    $storedfrom = $from;
    foreach ($members as $member) {
        #print "Sending to: $member[email] $member[name]\n" ;
        #if($cleanfrom == '$member[email]') { $from = $emailfrom ; } else { $from = $storedfrom ; } ;
        runsql("update members set recv = recv + 1 where uniq = '$member[uniq]'"); #increment the recv mail counter
        sendemail("$emailfrom", "$member[email]", "[$listname] $now Digest ", '', array(''), $DIGEST);
    };
    #cleanup - items deleted as parsed.
    #    sleep(1) ;
    imap_expunge($mbox); #Do It!
    sleep(1);
    imap_close($mbox);
    #    sleep(1) ;
    } ; 
};
function parse_rfc822_headers(string $header_string):
    array {
        preg_match_all('/([^:\s]+): (.*?(?:\r\n\s(?:.+?))*)\r\n/m', $header_string, $matches);
        $headers = array_combine_groupkeys($matches[1], $matches[2]);
        return $headers;
    }
    function array_combine_groupkeys(array $keys, array $values):
        array {
            $result = [];
            foreach ($keys as $i => $k) {
                $result[$k][] = $values[$i];
            }
            array_walk($result, function (&$v) {
                $v = (count($v) === 1) ? array_pop($v) : $v;
            });
            return $result;
        }
        function findoriginal($content) {
            $lines = preg_split("/\n/", $content);
            foreach ($lines as $l) {
                if (preg_match("/^Original-Recipient/", $l, $m)) {
                    preg_match_all("/[\._a-zA-Z0-9-]+@[\._a-zA-Z0-9-]+/i", $l, $m); #hmmm
                    $cleanfrom = strtolower(dtemail($m[0][0]));
                };
            };
            if (!empty($cleanfrom) and strlen($cleanfrom) < 60) {
                return $cleanfrom;
            } else {
                return 'none@none.here';
            };
        };
        function fortune() {
            #if fortune exists, get a fortune to add to the email.
            $filename = '/usr/games/fortune';
            if (file_exists($filename)) {
                system("/usr/games/fortune >/tmp/fortune");
            };
            $fortune = file_get_contents("/tmp/fortune");
            if (empty($fortune)) {
                $fortune = "No fortune for you!";
            };
            $fortune = "\n\nYour Fortune:\n\n$fortune";
            return $fortune;
        };
        #Makes sure this only runs via CLI.
        (PHP_SAPI !== 'cli' || isset($_SERVER['HTTP_USER_AGENT'])) && die('not for you');
        //Tries to make sure that only 1 of these
        $lockfile = sys_get_temp_dir() . '/melee.digest.lock';
        $pid = @file_get_contents($lockfile);
        if (posix_getsid($pid) === false or empty($pid)) {
            print "Life Locking Melee!\n";
            file_put_contents($lockfile, getmypid()); // create lockfile
            
        } else {
            print "Melee can only run once. If you run multiple lists, change the lockfile per list\n";
            exit;
        }
        error_reporting(E_ALL & ~E_NOTICE & ~E_USER_NOTICE);
        include ("glass-core.php");
        include ("sendemail.php");
        createdigest();
?>
