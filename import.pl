#!/usr/bin/perl
#written in perl because this is drop dead easy in perl. 
print "reads a list of email addresses from std in and formats sql that coule be used to import. \n\n\nExample: perl ./import.pl <list >sql\n\nctrl-c to abort\n\n" ; 
use strict;
use warnings;
my $file = shift @ARGV;
my $IN;
my $is_stdin = 0;
if (defined $file){
  open $IN, "<", $file or die $!;
} else {
  $IN = *STDIN;
  $is_stdin++;
}
while (<$IN>){
   chop() ; 
   print "insert into members (email,created,level) values ('$_',now(),'3') ;\n" ; 
}
