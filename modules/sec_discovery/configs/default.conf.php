<?php
    global $arrConf;
    global $arrConfModule;

    $arrConfModule['module_name']       = 'sec_discovery';
    $arrConfModule['templates_dir']     = 'themes';
    $arrConfModule['dsn_conn_database'] = "sqlite3:///$arrConf[issabel_dbdir]/iptables.db";
?>
