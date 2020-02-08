----- MELEE Mail List Engine -----
---A geeks tool for mailing list management. ---

As defined: Melee - a confused mass of people.
"the melee of people that was always thronging the streets"

This was created by a sysadmin/programmer that runs the CHUGALUG.org mailing
list that had been running mailman 1 through mailman 2.1 for 20+ years
and was frustrated by mailman 3. It's not idiot proof, but if you have basic
server admin skills, this should be simple enough. Suitable for ??? (Less
than 1k members, 5k members?) 

It requires: 
  *  1 db table (Maria/MySQL) for each mailing list
  *  1 code base in a directory for each mailing list, 
  *  1 email account (IMAP or POP) for each list. 
  *  2+ entries in /etc/crontab or some other scheduler.

You should have a Working mail server (SMTP and IMAP) - I suggest you have SPF DNS records and
DKIM setup. In theory this could work on a remote mail server, I'm using 'localhost' for
everything, it's a dedicated server for my list. 

You need either MariaDB or MySQL server installed. Your choice. Some skills
with the sql prompt will help until a proper web admin function is added,
but not required. 

Requirements: (PHP 7.3 isn't required, just what I'm using)
You may need to (or similar for other distributions):
apt-get install php7.3-mysql php-net-imap php-imap 

In examples below, $DB is your table name: ***** is your password. create
a database, use a decent password. the melee.starter.sql file is included,
but has no list members. Your first email to the list should be a
'subscribe' email. 

   mysqladmin -u root -p create $DB
   mysql <
   GRANT ALL PRIVILEGES ON $DB.* TO '$DB'@'localhost' IDENTIFIED BY '******';
   FLUSH PRIVILEGES
   mysql $DB <melee.starter.sql

If your melee installation is in a directory structure as part of a website, make sure
you do not allow .inc and .sql file to be read via the web. as a minimum,
the following should be part of your config. 

   <Files ~ "\.inc"> 
      Order allow,deny
      Deny from all   
   </Files>
   <Files ~ "\.sql">
       Order allow,deny
       Deny from all   
   </Files>
   <Files ~ "\.sh">
       Order allow,deny
       Deny from all   
   </Files>
   <Files ~ "\.pl">
       Order allow,deny
       Deny from all   
   </Files>

Now your need to edit settings.inc. copy settings.inc.dist to settings.inc and edit it. 
It's a PHP file. don't remove the 1st and last lines. 

   <?php
   $dbserver = 'localhost' ;    # Your MariaDB/MYSQL Server. 
   $dblogin = 'chugalug' ;      # the login to your database server.
   $dbpasswd = 'dbpassword' ; # the password to your database server
   $database = 'chugalug' ;     # the database
   $listname = 'chugalug' ;     # the name of the list. In subject line as[$listname]
   $webroot = '/melee' ;        # if on a webserver using the web interface, the relative path from the website
   $shellroot = '/home/domains/chugalug.org/website/melee/' ; #actual path to this directory. 
   $timezonetext = 'America/New_York' ; # the TZ text of the mailing list. use tzselect to determin
   $timezonenumber = '-5:00' ;      # a numeric offset for timezone 
   $emailserver = 'localhost' ;     # IMAP Server
   $emaillogin = 'chugalug' ;       # IMAP Login, probably the list name.. 
   $emailpasswd = 'yourimappassword' ;  # IMAP password
   $emailfrom = 'chugalug@chugalug.org' ;   #List email address. email will be from here. 
   $maxsize = 10042 ;           # Max email size, in bytes.  
   #internationalization settings - not used much yet. 
   $thousands = ',' ; 
   $decimals = '.' ; 
   $defaultlang = 'en' ; 
   ?>

You can run 'melee.php' manually for testing, simply by running:  php ./melee.php
Once tested, you will want to run this via /etc/crontab or some other
scheduler. An example of a crontab entry running ./melee.php every 5
minutes. There are a lot of different ways to do this. 

   */5 *	* * *	www-data	cd /home/domains/chugalug.org/list ; /usr/bin/php /home/domains/chugalug.org/list/melee.php >/tmp/melee.log


If you are going to use the web interface, which allows members to see their
info and will soon allow some editing of their info, you may want to edit 
2 HTML files:   

   welcome.html    - The initial web page.
   learnmore.html  - more list info. 

At some point, members in the members table with a security level of '99'
will be able to manage members of the list. To set yourself to that level,
once you have successfully subscribed to the list:

   mysql -u foo -p
   > update members set level = '99' where email = 'your@email.here' ;
   > quit


Things for you to do:

Send emails to the list with a subject line of 'fortune' and verify
your DKIM and SPF records in the headers received. Good examples:

  Received-SPF: pass (google.com: domain of chugalug@chugalug.org designates 2600:3c02::f03c:92ff:fe2c:3321 as permitted sender)
  DKIM-Signature: v=1; a=rsa-sha256; c=simple/simple; d=chugalug.org; s=chugalug2020; t=1581174172; bh=SBYVAbd6Gci/1Gk76pk/dokVULvBFk3muLSvhGpnUD8=; h=To:Subject:From:Reply-To:List-ID:List-Unsubscribe:List-Post:
	 List-Help:List-Subscribe:Date:From; b=sIbOboUpUBJ3mvaQlqyik3h17RioaOfapP1ImoZvdB6em2dC1D8JukNPQE9dvlDie
	 ePAXV3RprP9RJ7uXERXgf50LGYzyXrYLw88Rei3txTRPUZpzeX07X4CYYQjZxc55J1
	 AtNiLlt+2qrLkjeAoF7vCONDZDFcL5Q0ytwqIZxa8iHGGT6ZGFEW6vXbSJn92j84r4
	 s4/DeBOM/VZ8ZJ6xsEqSN6v2L/Zk4kp1U4i/Qr/cC3wi3EUkPOKYQetbLHIyvOEmlk
	 EmqpNl4A2bdRgbS3py84MiuC+Ebqwzu58X940NfqlUGVKaN91/79AAs8eRbH40hsSR
	 PkURQKTiTdCWw==

Worth knowing; 

MELEE ignores emails from non-members that don't ask to subscribe. It just
deletes them. No "moderator" junk email crapola piling up the mailing list
server. 

Set a persons level to '0' to stop them from getting emails. 

If they get 2 bounces, it won't send them more emails. (this may become a
config item). 



