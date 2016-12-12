#!/usr/bin/expect -f
# wrapper to make passwd(1) be non-interactive
# username is passed as 1st arg, passwd as 2nd

set serverid [lindex $argv 0]
set username [lindex $argv 1]
set password [lindex $argv 2]
set newpassword [lindex $argv 3]

spawn ssh -o PreferredAuthentications=password -o PubkeyAuthentication=no $serverid passwd
expect "assword:"
send "$password\r"
expect "UNIX password:"
send "$password\r"
expect "password:"
send "$newpassword\r"
expect "password:"
send "$newpassword\r"
expect eof