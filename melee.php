<?php
function leachemails() {
#main MELEE everything in 1 script. 
#handles PGP in some cases. Good enough for casual use. Be wary of webmail PGP implementations. 
#Needs a properly setup mail server and GPG for the list. 
#version 0.42b - Working well enough in my cases..  --meuon--
#
    global $db, $lang, $thousands, $decimals;
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
        $help = "If in the subject line, the following words do magic:\n\nsubscribe - subscribe to the list\nunsubscribe - get me off of this list. It deletes your record and you can resubsribe\npasswordreset - get a password to the web interface.\ndigest - if only word on subject line, toggles digest mode\nhelp - if only word on subject line, you get this email with helpful commands.\nfortune - check the list function and my stats and list stats and give you a fortune\n\"list working\" - same as fortune, cuts down on people asking if the list is working and it sending upmteen thousand emails about it.\n";
        if ($sender['uniq'] > 0) {
            #=========================CHANGE RECV REQUIREMENT ASAP!!!
            print "MEMBER $sender[uniq] $sender[name] SENT US EMAIL\nSubject; $subject              " . intnff($size) . " bytes !!!\n\n";
            $send = true;
            if ($sender['digest'] > 0) {
                $digtext = 'Daily';
            } else {
                $digtext = 'No';
            };
            $sender['sent'] = intnff($sender['sent']);
            $sender['recv'] = intnff($sender['recv']);
            $info = "\nName: $sender[name]\nEmail: $sender[email]\nMember since: $sender[created]\nKarma Level: $sender[level] Sent: $sender[sent] Received: $sender[recv] Bounced: $sender[bounced]  Digest: $digtext\n";
            list($mems, $recv, $sent) = gafm("select count(uniq),sum(recv),sum(sent) from members");
            $mems = intnff($mems);
            $recv = intnff($recv);
            $sent = intnff($sent);
            #globals:
            $stats = "\n\n$mems active members have sent $sent emails to the list that generated $recv emails, not counting administrative emails.";
            #size sanity check and reply
            if ($size > $maxsize and $sender['level'] < 500) {
                print "$size > $maxsize - refused\n";
                $wcontent = "\n\nDear $cleanfrom,\n\nYour message was refused because it was too large.\n\nSubject; $subject\n\nYour message was $size bytes and the limit is $maxsize bytes\n\n\n\n\n$stats";
                $wcontent = signme($wcontent);
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
                $wcontent = signme($wcontent);
                sendemail("$emailfrom", "$cleanfrom", "[$listname] un-subscribe $cleanfrom from $listname", '', $optheaders, $wcontent);
                $send = false;
            };
            if (preg_match("/^passwordreset$/", strtolower(dt($subject)), $m)) {
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
                $wcontent = "\n\nMagic word requested.\n\nYour new magic word is:   $passwd\n$info\nAll generic disclaimers apply; do not use this anywhere else. If possible, change this when you login.\n\n\n\nThis was sent via plain text email and may already be comprimised. Not much can be done with it, but if you notice your mailing lists settings are strange, agents from the planet Bogon may be messing with you. Only you can keep your mailing list safe from the Bogons. Any insult or injury to Bogons was strictly intentional. The $listname mailing list does not need wild Bogons creating havoc. That's usually internally self-generated and best left to members of the mailing list. All of this nonsense at the bottom of this message is just noise to help get this past the heuristic gatekeeps that guard us from the worst of ourselves. No actual semantic meaning of value should be infered. Have a sparkly day.\n\n -Respectfully submited, the $listname mailing list.\n$help\n\n$fortune\n";
                $wcontent = signme($wcontent);
                sendemail("$emailfrom", "$cleanfrom", "[$listname] reset $cleanfrom ", '', $optheaders, $wcontent);
                $send = false;
            };
            if (preg_match("/^digest$/", strtolower(dt($subject)), $m)) {
                print "Digest Toggle!!!\n\n";
                #auto subcribe for now..
                $fortune = fortune();
                if ($sender['digest'] == '0') {
                    $q = "update members set digest = '1' where uniq = '$sender[uniq]'";
                    $wcontent = "Digest Mode Enabled. Currently Daily Only\n\n$stats\n\n$fortune";
                    $wcontent = signme($wcontent);
                    runsql("$q");
                    sendemail("$emailfrom", "$cleanfrom", "[$listname] digest mode toggled ON ", '', $optheaders, $wcontent);
                };
                if ($sender['digest'] == '1') {
                    $q = "update members set digest = '0' where uniq = '$sender[uniq]'";
                    $wcontent = "Digest Mode Disabled.\n\n$stats\n\n$fortune";
                    $wcontent = signme($wcontent);
                    runsql("$q");
                    sendemail("$emailfrom", "$cleanfrom", "[$listname] digest toggled OFF ", '', $optheaders, $wcontent);
                };
                $send = false;
            };
            if (preg_match("/fortune/", strtolower(dt($subject)), $m) or preg_match("/list working/", strtolower(dt($subject)), $m)) {
                # a way to test the list is working
                print "Fortune!!!\n\n";
                #auto subcribe for now..
                $fortune = fortune();
                $wcontent = "This list is working.\n$info\n$fortune\n\n$stats";
                $wcontent = signme($wcontent);
                sendemail("$emailfrom", "$cleanfrom", "[$listname]  Fortune - List Check", '', $optheaders, $wcontent);
                $send = false;
            };
            if (preg_match("/^bounceme$/", strtolower(dt($subject)), $m)) {
                # a way to debug message formatting, headers, etc.. without affecting the whole list - this includes the contenttype header
                print "BOUNCEME!!!\n";
                #-------
                $origcontent = $content;
                #determine if GPG/PGP Signed or Encrypted and if so, decrypt.
                if (preg_match("/PGP SIGNED MESSAGE/", $origcontent, $m) or preg_match("/PGP MESSAGE/", $origcontent, $m)) { #looks like a PGP message?
                    include ("settings.inc");
                    putenv("GNUPGHOME=$gnupghome");
                    $res = gnupg_init();
                    $plaintext = '';
                    $msginfos = gnupg_verify($res, $origcontent, false, $plaintext);
                    $senderfingerprint = makefingerprint("$cleanfrom");
                    $matchsender = false;
                    foreach ($msginfos as $msginfo) {
                        if ($msginfo['fingerprint'] == $senderfingerprint) {
                            $matchsender = true;
                            print $msginfo['fingerprint'] . " matched!\n\n";
                            gnupg_adddecryptkey($res, "$senderfingerprint", "");
                            gnupg_adddecryptkey($res, "$fingerprint", "");
                        };
                    };
                    if ($matchsender) {
                        $content = $plaintext;
                        $content.= "\n=====\n$listname VERIFIES $cleanfrom as the original sender\nFingerprint: $senderfingerprint\n=====\n";
                    } else {
                        $content.= "\n=====\n$listname COULD NOT VERIFY $cleanfrom as the sender of this content\n=====\n";
                    };
                };
                #--------
                print $content;
                $contenttype = '';
                $content = signme($content);
                $contenttype = '';
                sendemail("$emailfrom", "$cleanfrom", "[$listname]  Bounce Test", $contenttype, $optheaders, $content);
                $send = false;
            };
            if (preg_match("/^help$/", strtolower(dt($subject)), $m)) {
                # a way to test the list is working
                print "HELP!!!\n\n";
                $fortune = fortune();
                $wcontent = "Helpful Commands: $help\nThis list is working.\n$info\n$fortune\n\n$stats";
                $wcontent = signme($wcontent);
                sendemail("$emailfrom", "$cleanfrom", "[$listname]  Fortune - List Check", '', $optheaders, $wcontent);
                $send = false;
            };
            if (preg_match("/^subscribe$/", strtolower(dt($subject)), $m)) {
                print "Redundant Subscripton\n\n";
                $fortune = fortune();
                $wcontent = "You are already subscribed to $listname. If this was a mistake, make a more complex subject line.\n\nHelpful Commands: $help\nThis list is working.\n$info\n$fortune\n\n$stats";
                $wcontent = signme($wcontent);
                sendemail("$emailfrom", "$cleanfrom", "[$listname]  Fortune - List Check", '', $optheaders, $wcontent);
                $send = false;
            };
            #add to subject line if listname not there.
            if (preg_match("/\[$listname\]/", $subject, $m)) {
            } else {
                $subject = "[$listname] " . $subject . "\n";
            }
            if ($send) {
                $cleancontent = gleanemail($mbox, $mid); #only doing this 1 time. Same signature for all signed.
                $origcontent = $content;
                #determine if GPG/PGP Signed or Encrypted and if so, decrypt.
                if (preg_match("/PGP SIGNED MESSAGE/", $origcontent, $m) or preg_match("/PGP MESSAGE/", $origcontent, $m)) { #looks like a PGP message?
                    include ("settings.inc");
                    putenv("GNUPGHOME=$gnupghome");
                    $res = gnupg_init();
                    $plaintext = '';
                    $msginfos = gnupg_verify($res, $origcontent, false, $plaintext);
                    $senderfingerprint = makefingerprint("$cleanfrom");
                    $matchsender = false;
                    foreach ($msginfos as $msginfo) {
                        if ($msginfo['fingerprint'] == $senderfingerprint) {
                            $matchsender = true;
                            print $msginfo['fingerprint'] . " matched!\n\n";
                            gnupg_adddecryptkey($res, "$senderfingerprint", "");
                            gnupg_adddecryptkey($res, "$fingerprint", "");
                        };
                    };
                    if ($matchsender) {
                        $cleancontent = $plaintext ;
                        $content = $plaintext ; 
                        $cleancontent.= "\n=====\n| $listname VERIFIES $cleanfrom as sender and valid content\n| Fingerprint: $senderfingerprint\n=====\n";
                        $content.= "\n=====\n| $listname VERIFIES $cleanfrom as sender and valid content\n| Fingerprint: $senderfingerprint\n=====\n";
                    } else {
                        $content.= "\n=====\n| $listname COULD NOT VERIFY $cleanfrom as the sender nor content validity\n=====\n";
                    };
                };
                #--------
                $signedcontent = signme($cleancontent);
               # if ($cleanfrom == 'mike.geeklabs@gmail.com') { #useful for testing things.
               #     $members = gaaafm("select * from members where status = 'active' and level > 5 and digest < 1 and bounced < 3 and uniq != '$sender[uniq]' order by email,uniq");
               # } else {
                    $members = gaaafm("select * from members where status = 'active' and level > 0 and digest < 1 and bounced < 3 and uniq != '$sender[uniq]' order by email,uniq");
               # };
                $C = count($members);
                print "Sleeping for 5...before sending to $C members.      ctrl-c to abort\n";
                sleep(5);
                runsql("update members set sent = sent + 1 where uniq = '$sender[uniq]'"); #increment the sent mail counter
                #================
                foreach ($members as $member) {
                    #print "Sending to: $member[email] $member[name]\n" ;
                    runsql("update members set recv = recv + 1 where uniq = '$member[uniq]'"); #increment the recv mail counter
                    if ($member['sign'] == '1' and $member['encrypt'] == '0') {
                        sendemail("$from", "$member[email]", "$subject", '', $optheaders, $signedcontent);
                    } elseif ($member['encrypt'] == '1') {
                        #add keys and encrypt goes here.
                        print "Encrypting\n";
                        $encryptcontent = encryptme($cleancontent, $member['publickey']);
                        sendemail("$from", "$member[email]", "$subject", '', $optheaders, $encryptcontent);
                    } else {
                        sendemail("$from", "$member[email]", "$subject", $contenttype, $optheaders, $content);
                    };
                };
            };
            imap_delete($mbox, $mid);
        } elseif (preg_match("/mailer-deamon/", strtolower($cleanfrom), $m) or preg_match("/undelivered/", strtolower($subject), $m)) {
            print "cleanfrom: $cleanfrom  or $subject indicates a bounce?\n\n";
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
                $wcontent = "\n\nWelcome $cleanfrom,\n\nYou were auto-subscribed to the $emailfrom mailing list.\n\nYou may have to be on the list a while before you can post or reply. Depends on the whims of the admins.\n\nBe gracious, trim your replies, drunk/high posting may be encouraged or discouraged. Read the list rules and read a few posts for a clue.\n\n$help\n$info\n$fortune";
                $wcontent = signme($wcontent);
                sendemail("$emailfrom", "$cleanfrom", "[$listname] Welcome", '', $optheaders, $wcontent);
            };
            imap_delete($mbox, $mid);
        };
    };
    #cleanup - items deleted as parsed.
    sleep(1);
    imap_expunge($mbox); #Do It!
    sleep(1);
    imap_close($mbox);
    sleep(1);
};
function signme($content) {
    #https://www.php.net/manual/en/ref.gnupg.php
    include ("settings.inc");
    if (!empty($gnupghome) and !empty($fingerprint)) {
        putenv("GNUPGHOME=$gnupghome");
        $res = gnupg_init();
        gnupg_addsignkey($res, "$fingerprint", ""); #list gpg fingerpint
        $signed = gnupg_sign($res, "$content");
        $content = $signed;
    };
    return $content;
};
function encryptme($content, $memberpublickey) {
    include ("settings.inc");
    putenv("GNUPGHOME=$gnupghome");
    $res = gnupg_init();
    print "List fingerprint: $fingerprint \n";
    gnupg_addencryptkey($res, $fingerprint); #list fingerpint - should already be in gpg keyring
    gnupg_addsignkey($res, $fingerprint, ""); #list fingerpint
    $keydata = gnupg_import($res, $memberpublickey); #import members publickey
    #    print_r($keydata); #useful when debugging
    if (empty($keydata['fingerprint'])) {
        $signed = gnupg_sign($res, $content);
        $signed.= "\n\nSIGNED ONLY: You requested encrypted, but there seems to be an issue with your public key\n Make sure it has all of the ---'s at top and bottom.\n\n";
        return $signed;
    } else {
        gnupg_addencryptkey($res, $keydata['fingerprint']); #data from importing key.
        $encrypted = gnupg_encryptsign($res, $content);
        return $encrypted;
    };
};
function makefingerprint($cleanemail) {
    global $db;
    print "Making fingerpint to compare for $cleanemail\n";
    list($uniq, $publickey, $fingerprint) = gafm("select uniq,publickey,fingerprint from members where email = '$cleanemail' limit 1");
    if (!empty($uniq)) {
        include ("settings.inc");
        putenv("GNUPGHOME=$gnupghome");
        $res = gnupg_init();
        $info = gnupg_import($res, $publickey);
        #print_r($info);
        if (!empty($info['fingerprint']) and $info['fingerprint'] != $fingerprint) {
            print "Update Fingerprint for $uniq $cleanemail $info[fingerprint]\n";
            runsql("update members set fingerprint = '$info[fingerprint]' where uniq = '$uniq'");
        };
        return $info['fingerprint'];
    } else {
        return '';
    };
};
function gleanemail($mbox, $mid) {
    #there is some extra stuff here, used elsewhere as well. cut and paste code that mostly works
    #strip MIME email to plain text if possible. Removes attachements.
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
    return $content;
};
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
#borrowed code: not my style but works.
function parse_rfc822_headers(string $header_string):
    array {
        preg_match_all('/([^:\s]+): (.*?(?:\r\n\s(?:.+?))*)\r\n/m', $header_string, $matches);
        $headers = array_combine_groupkeys($matches[1], $matches[2]);
        return $headers;
    };
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
        };






#=================================
        #Makes sure this only runs via CLI.
        #example crontab entry:
        #*/5 *	* * *	root	cd /home/domains/chugalug.org/website/melee ; /usr/bin/php /home/domains/chugalug.org/website/melee/melee.php >/tmp/melee.log
        include ("settings.inc");
        (PHP_SAPI !== 'cli' || isset($_SERVER['HTTP_USER_AGENT'])) && die('not for you');
        #Tries to make sure that only 1 of these
        $lockfile = sys_get_temp_dir() . "/melee.$database.lock";
        $pid = @file_get_contents($lockfile);
        if (posix_getsid($pid) === false or empty($pid)) {
            print "Life Locking Melee!\n";
            file_put_contents($lockfile, getmypid()); // create lockfile
        } else {
            print "Melee can only run once. If you run multiple lists, change the lockfile per list\n";
            exit;
        };
        error_reporting(E_ALL & ~E_NOTICE & ~E_USER_NOTICE);
        include ("glass-core.php");
        include ("sendemail.php");
        leachemails();
        
?>
