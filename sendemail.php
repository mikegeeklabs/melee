<?php
#Basic local mail server version  of 'sendemail-basic.php'
function emailheader($filler) { 
    $S = "" ; 
    return $S ; 
} ; 

function emailfooter() { 
    $S = '' ; 
    $S .= "<div style=\"font-family:sans-serif;font-size:small;color:#888888;\">
    Cybrmall.com Mail Test. If you got this and should not have we apologize, we are playing with code. 
    <a href=\"https://cybrmall.com\">cybrmall.com</A> | 
    <a href=\"https://geeklabs.com\">geeklabs.com</A> | 
    </div>\n";
    return $S ; 
} ; 
function sendemail($fromemail,$email, $subject, $contenttype, $optheaders,$message) {
    global $portal, $account, $db, $login, $customer;
    #this is very basic, just resends raw $message for now. 
    include ("settings.inc"); 
    $validemail = true;
    if ($validemail) {
    } else {
        print "<div class='alert alert-error'>invalid email address</div>\n";
        return;
    };
    #-----------------------------------------------------------------------------
#    $boundary = '' . md5(uniqid("boundary")); # should create a unique boundary for these messages
    $headers = '';
    $headers.= "From: $fromemail\r\n"; #in settings.inc
    $headers.= "Reply-To: $emailfrom\r\n"; #in settings.inc -f below
    $headers.= "Return-Path: $emailfrom\r\n"; 
    $headers.= "Precedence: list\n" ; 
    $headers.= "User-Agent: geeklabs MELEE\n";
#    $headers.= "X-BeenThere: <$emailfrom>\n" ; 
#    $headers.= "X-Melee Version: 0.42\n" ; 
    $headers.= "X-Mailer: geeklabs MELEE\n" ; 
    $headers.= "X-Priority: 3\n";
    $headers.= "Importance: 3 (normal)\n";
    $headers.= "X-MSMail-Priority: Normal\n";
    
#    print_r($optheaders) ; 
    if(!empty($optheaders[0])) { 
        $headers .= "References: $optheaders[0]\n" ; 
        print "References: $optheaders[0]\n" ; 
    } ; 

    $headers.= "List-ID: $listname <$emailfrom>\n"; 
    $headers.= "Mailing-list: list $emailfrom; contact admin-$emailfrom\n" ; 
    $headers.= "List-Unsubscribe: <mailto:$emailfrom?subject=unsubscribe>\n"; 
    $headers.= "List-Post: <mailto:$emailfrom>\n" ; 
    $headers.= "List-Help: <mailto:$emailfrom?subject=HELP>\n" ; 
    $headers.= "List-Subscribe: <mailto:$emailfrom?subject=subscribe>\n" ; 
#    $headers.= "Errors-To: <mailto:errors@cybrmall.com>\n" ; 
    if(!empty($contenttype)) { 
        $headers.= "MIME-Version: 1.0\n";
        $headers.= "Content-Type: $contenttype\n";
    } ; 
    $headers.= "\r\n\r\n"; #break between headers and non-mime body
    
    if (mail($email, $subject, $message . "\n\r", $headers, "-f $emailfrom" )) {
        print "Sent to $email\n" ; 
    } else {
        print "Could not send to $email\n" ; 
    } ; 

};

?>
