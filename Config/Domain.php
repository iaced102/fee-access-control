<?php

use Library\Environment;

$envDomain = Environment::getPrefixEnvironment();
$domain = Environment::getDomain();
if ($domain == "") {
    $host = $_SERVER['HTTP_HOST'];
    $domain = preg_replace('/^[a-z0-9-]+./', '', $host);
}
define('ACCESS_CONTROL_SERVICE', $GLOBALS['domains']['ACCESS_CONTROL_SERVICE']);
define('ACCOUNT_SERVICE', $GLOBALS['domains']['ACCOUNT_SERVICE']);
define('APP_MANAGEMENT_SERVICE', $GLOBALS['domains']['APP_MANAGEMENT_SERVICE']);
define('BI_SERVICE', $GLOBALS['domains']['BI_SERVICE']);
define('COMMENT_SERVICE', $GLOBALS['domains']['COMMENT_SERVICE']);
define('DATA_IO_SERVICE', $GLOBALS['domains']['DATA_IO_SERVICE']);
define('FILE_SERVICE', $GLOBALS['domains']['FILE_SERVICE']);
define('IO_SERVICE', $GLOBALS['domains']['IO_SERVICE']);
define('KANBAN_SERVICE', $GLOBALS['domains']['KANBAN_SERVICE']);
define('NOTIFI_SERVICE', $GLOBALS['domains']['NOTIFI_SERVICE']);
define('ORGCHART_SERVICE', $GLOBALS['domains']['ORGCHART_SERVICE']);
define('SDOCUMENT_MANAGEMENT_SERVICE', $GLOBALS['domains']['SDOCUMENT_MANAGEMENT_SERVICE']);
define('SDOCUMENT_SERVICE', $GLOBALS['domains']['SDOCUMENT_SERVICE']);
define('SYQL_SERVICE', $GLOBALS['domains']['SYQL_SERVICE']);
define('UI_SERVICE', $GLOBALS['domains']['UI_SERVICE']);
define('WORKFLOW_EXTEND_SERVICE', $GLOBALS['domains']['WORKFLOW_EXTEND_SERVICE']);
define('WORKFLOW_MODELER_SERVICE', $GLOBALS['domains']['WORKFLOW_MODELER_SERVICE']);
define('MESSAGE_BUS_SERVICE', $GLOBALS['domains']['MESSAGE_BUS_SERVICE']);
define('DATA_CONNECTOR_SERVICE', $GLOBALS['domains']['DATA_CONNECTOR_SERVICE']);
define('OBJECT_RELATION', $GLOBALS['domains']['OBJECT_RELATION']);
define('LOG_SERVICE', "https://" . $envDomain . "log." . $domain);
define('SEARCH_SERVICE', "https://" . $envDomain . "search." . $domain);
define('WORKFLOW_SERVICE', "https://" . $envDomain . "workflow." . $domain);
define('LUFFY_SERVICE', "https://luffy." . $domain);
define('KAFKA_NODE', $GLOBALS['env']['kafka']);
