<?php
function leachemails() {
    global $db;
    include ('settings.inc');
    $db = glconnect();
    print "Connecting to $emailserver as $emaillogin\n";
    $mbox = imap_open("{" . $emailserver . ":143/novalidate-cert}INBOX", "$emaillogin", "$emailpasswd") or die(imap_last_error() . "<br>Connection Faliure!");
    $headers = @imap_headers($mbox) or die("Couldn't get emails or no new emails\n\n");
    $numEmails = sizeof($headers);
    echo "You have $numEmails in your INBOX\n";
    for ($mid = 1;$mid < $numEmails + 1;$mid++) {
        $mailrawheader = imap_fetchbody($mbox, $mid, "0");
        $mailraw = imap_fetchbody($mbox, $mid, "");
        $someheaderinfo = imap_headerinfo($mbox, $mid);
        $size = $someheaderinfo->Size;
        list($hjunk, $mailrawbody) = preg_split("/\n\r/", $mailraw, 2);
        $headers = parse_rfc822_headers($mailrawheader); #cause I want ALL the headers.
        $contenttype = $headers['Content-Type'];
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
        print "From: $from\nClean From: $cleanfrom\nTo: $to\nSubject: $subject\nContent:\n\n";
        #step 1, see if they are in members.
        $sender = gaafm("select * from members where lower(email) = '$cleanfrom'  limit 1");
        $help = "If in the subject line, the following words do magic:\n\nsubscribe - subscribe to the list\nunsubscribe - get me off of this list. It deletes your record and you can resubsribe\npasswordreset - get a password to the web interface.\nhelp - if only word on subject line, you get this email with helpful commands.\nfortune - check the list function and my stats and list stats and give you a fortune\n\"list working\" - same as fortune, cuts down on people asking if the list is working and it sending upmteen thousand emails about it.\n";
        if ($sender['uniq'] > 0) {
            #=========================CHANGE RECV REQUIREMENT ASAP!!!
            print "MEMBER $sender[uniq] $sender[name] SENT US EMAIL     $size bytes !!!\n\n";
            $send = true;
            $info = "\nName: $sender[name]\nEmail: $sender[email]\nMember since: $sender[created]\nKarma Level:$sender[level] Sent: $sender[sent] Received: $sender[recv] Bounced: $sender[bounced]\n";
            list($mems, $recv, $sent) = gafm("select count(uniq),sum(recv),sum(sent) from members");
            #globals:
            $stats = "\n\n$mems active members have sent $sent emails to the list that generated $recv emails, not counting administrative emails.";
            #size sanity check and reply
            if ($size > $maxsize and $sender['level'] < 100) {
                print "$size > $maxsize - refused\n";
                $wcontent = "\n\nDear $cleanfrom,\n\nYour message was refuse because it was too large.\n\nYour message was $size bytes and the limit is $maxsize bytes\n\n$stats";
                sendemail("$emailfrom", "$cleanfrom", "[$listname] Message size exceeds limit.", '', $optheaders, $wcontent);
                $send = false;
            }
            if ($send) {
                #some sanity checks and niceties if the email size is not huge.
                if (empty($sender['name']) and $from != $cleanemail) {
                    $f = dtemail($from);
                    runsql("update members set name = '$f' where uniq = '$sender[uniq]'");
                };
            };
            #check for unsubscribe
            if (preg_match("/unsubscribe/", strtolower(dt($subject)), $m)) {
                #maybe this entity wants to unsubscribe?
                print "UNSUBSCRIBING!!!\n\n";
                #auto subcribe for now..
                $q = "delete from members where uniq = '$sender[uniq]'";
                runsql("$q");
                $wcontent = "\n\nGoodbye $cleanfrom,\n\nYou were un-subscribed from the $emailfrom mailing list.\n\nYou may resubscribe at any time. \n\n";
                sendemail("$emailfrom", "$cleanfrom", "[$listname] un-subscribe $cleanfrom from $listname", '', $optheaders, $wcontent);
                $send = false;
            };
            if (preg_match("/passwordreset/", strtolower(dt($subject)), $m)) {
                #maybe this entity wants a new password
                #this could ba a bad idea.
                print "Password Reset!!!\n\n";
                #auto subcribe for now..
                $fortune = fortune();
                $passwd = substr(glakey(), 10);
                $options = ['cost' => 12, ];
                $passwdhash = password_hash($passwd, PASSWORD_BCRYPT, $options);
                $q = "update members set passwd = '$passwdhash' where uniq = '$sender[uniq]'";
                runsql("$q");
                $wcontent = "\n\nMagic word requested.\n\nYour new magic word is:   $passwd\n$info\nAll generic disclaimers apply; do not use this anywhere else. If possible, change this when you login.\n\n\n\nThis was sent via plain text email and may already be comprimised. Not much can be done with it, but if you notice your mailing lists settings are strange, agents from the planet Bogon may be messing with you. Only you can keep your mailing list safe from the Bogons. Any insult or injury to Bogons was strictly intentional. The $listname mailing list does not need wild Bogons creating havoc. That's usually internally self-generated and best left to members of the mailing list. All of this nonsense at the bottom of this message is just noise to help get this past the heuristic gatekeeps that guard us from the worst of ourselves. No actual semantic meaning of value should be infered. Have a sparkly day.\n\n -Respectfully submited, the $listname mailing list.\n\n\n$fortune\n";
                sendemail("$emailfrom", "$cleanfrom", "[$listname] reset $cleanfrom ", '', $optheaders, $wcontent);
                $send = false;
            };
            if (preg_match("/fortune/", strtolower(dt($subject)), $m) or preg_match("/list working/", strtolower(dt($subject)), $m)) {
                # a way to test the list is working
                print "Fortune!!!\n\n";
                #auto subcribe for now..
                $fortune = fortune();
                $wcontent = "This list is working.\n$info\n$fortune\n\n$stats";
                sendemail("$emailfrom", "$cleanfrom", "[$listname]  Fortune - List Check", '', $optheaders, $wcontent);
                $send = false;
            };
            if (preg_match("/^help$/", strtolower(dt($subject)), $m)) {
                # a way to test the list is working
                print "HELP!!!\n\n";
                #auto subcribe for now..
                $fortune = fortune();
                $wcontent = "Helpful Commands: $help\nThis list is working.\n$info\n$fortune\n\n$stats";
                sendemail("$emailfrom", "$cleanfrom", "[$listname]  Fortune - List Check", '', $optheaders, $wcontent);
                $send = false;
            };
            if (preg_match("/^subscribe$/", strtolower(dt($subject)), $m)) {
                print "Redundant Subscripton\n\n";
                $fortune = fortune();
                $wcontent = "You are already subscribed to $listname. If this was a mistake, make a more complex subject line.\n\nHelpful Commands: $help\nThis list is working.\n$info\n$fortune\n\n$stats";
                sendemail("$emailfrom", "$cleanfrom", "[$listname]  Fortune - List Check", '', $optheaders, $wcontent);
                $send = false;
            };
            #add to subject line if listname not there.
            if (preg_match("/\[$listname\]/", $subject, $m)) {
            } else {
                $subject = "[$listname] " . $subject . "\n";
            }
            if ($send) {
                runsql("update members set sent = sent + 1 where uniq = '$sender[uniq]'"); #increment the sent mail counter
                $members = gaaafm("select * from members where status = 'active' and level > 0 and bounced < 3 and uniq != '$sender[uniq]' order by email,uniq");
                foreach ($members as $member) {
                    #print "Sending to: $member[email] $member[name]\n" ;
                    runsql("update members set recv = recv + 1 where uniq = '$member[uniq]'"); #increment the recv mail counter
                    sendemail("$from", "$member[email]", "$subject", $contenttype, $optheaders, $content);
                };
            };
            imap_delete($mbox, $mid);
        } elseif ($cleanfrom == 'mailer-daemon@cybrmall.com') {
            print "cleanfrom: $cleanfrom  indicates a bounce?\n\n";
            #can we find a bounce original email address in all cases?
            $maybe = findoriginal($content);
            print "Original may have been: $maybe    updating bounce counter if exists\n\n";
            runsql("update members set bounced = bounced + 1 where email = '$maybe'"); #increment the recv mail counter
            imap_delete($mbox, $mid);
        } else {
            #email from someone not on the list... what do we do... not much
            print "Not a member\n";
            if (preg_match("/subscribe/", strtolower(dt($subject)), $m)) { # if you start to get smarter spammers, change this word from subscribe
                #maybe this entity wants to subscribe?
                #this could ba a bad idea.
                $fortune = fortune();
                #auto subcribe for now..
                $q = "insert into members(email,name,level,created) values ('$cleanfrom','$from','4',now())";
                runsql("$q");
                $wcontent = "\n\nWelcome $cleanfrom,\n\nYou were auto-subscribed to the $emailfrom mailing list.\n\nYou may have to be on the list a while before you can post or reply. Depends on the whims of the admins.\n\nBe gracious, trim your replies, drunk/high posting may be encouraged or discouraged. Read the list rules and read a few posts for a clue.\n\n$help\n\n$fortune";
                sendemail("$emailfrom", "$cleanfrom", "[$listname] Welcome", '', $optheaders, $wcontent);
            };
            imap_delete($mbox, $mid);
        };
    };
    #cleanup - items deleted as parsed.
    imap_expunge($mbox); #Do It!
    imap_close($mbox);
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
        $lockfile = sys_get_temp_dir() . '/melee.lock';
        $pid = @file_get_contents($lockfile);
        if (posix_getsid($pid) === false or empty($pid)) {
            print "Life Locking Melee!\n";
            file_put_contents($lockfile, getmypid()); // create lockfile
            
        } else {
            print "Melee can only run once. If you run multiple lists, change the lockfile per list\n";
            exit;
        }
        #You may want to do something like this for debugging.. }
        print "backup to ../chugacopy\n" ;
        system("cat /var/spool/mail/chugalug >>/var/spool/mail/chugacopy") ;
        error_reporting(E_ALL & ~E_NOTICE & ~E_USER_NOTICE);
        include ("glass-core.php");
        include ("sendemail.php");
        leachemails();
?>
