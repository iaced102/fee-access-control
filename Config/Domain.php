<?php

use Library\Environment;
$envDomain = Environment::getPrefixEnvironment();
$domain = Environment::getDomain();
if($domain == ""){
    $host = $_SERVER['HTTP_HOST'];
    $domain = preg_replace('/^[a-z0-9-]+./', '', $host);
}
define('ACCESS_CONTROL_SERVICE',"https://".$envDomain."accesscontrol.".$domain);
define('ACCOUNT_SERVICE',"https://".$envDomain."account.".$domain);
define('APP_MANAGEMENT_SERVICE',"https://".$envDomain."apps-management.".$domain);
define('BI_SERVICE',"https://".$envDomain."bi-service.".$domain);
define('COMMENT_SERVICE',"https://".$envDomain."comment-service.".$domain);
define('DATA_IO_SERVICE',"https://".$envDomain."data-io.".$domain);
define('FILE_SERVICE',"https://".$envDomain."file-service.".$domain);
define('IO_SERVICE',"https://".$envDomain."io.".$domain);
define('KANBAN_SERVICE',"https://".$envDomain."kanban-service.".$domain);
define('LOG_SERVICE',"https://".$envDomain."log.".$domain);
define('NOTIFI_SERVICE',"https://".$envDomain."notifi.".$domain);
define('ORGCHART_SERVICE',"https://".$envDomain."orgchart.".$domain);
define('SDOCUMENT_MANAGEMENT_SERVICE',"https://".$envDomain."sdocument-management.".$domain);
define('SDOCUMENT_SERVICE',"https://".$envDomain."sdocument.".$domain);
define('SEARCH_SERVICE',"https://".$envDomain."search.".$domain);
define('SYQL_SERVICE',"https://".$envDomain."syql.".$domain);
define('TRASH_SERVICE',"https://".$envDomain."trash.".$domain);
define('UI_SERVICE',"https://".$envDomain."ui.".$domain);
define('WORKFLOW_EXTEND_SERVICE',"https://".$envDomain."workflow-extend.".$domain);
define('WORKFLOW_MODELER_SERVICE',"https://".$envDomain."workflow-modeler.".$domain);
define('WORKFLOW_SERVICE',"https://".$envDomain."workflow.".$domain);
define('DATA_CONNECTOR_SERVICE',"https://".$envDomain."data-connector.".$domain);
define('MESSAGE_BUS_SERVICE',"https://".$envDomain."message-bus.".$domain);
define('SYNC_DATA_SERVICE',"https://".$envDomain."sync-data.".$domain);
define('LUFFY_SERVICE',"https://luffy.".$domain);
define('KAFKA_NODE',"symperkafka.".$domain);
