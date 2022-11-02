<?php

require_once '..\src\config.php';

$backup_file = DB_DB. "-" .date("d"). ".sql";

$commands = array(
    "mysqldump --opt -h ".DB_HOST." -u ".DB_USR." -p".DB_PASS." -v ".DB_DB." > $backup_file",
    "ftp -n <<EOF
open mincho.zapto.org
user respaldos G3stcom.1213
put ".$backup_file." /users/respaldos/home/bygmysql/".$backup_file."
EOF",
    "unlink ".$backup_file,
    );


foreach ( $commands as $command ) {
	system($command,$output);
	//echo $output;
}
?>