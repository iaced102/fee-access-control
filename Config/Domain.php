<?php

use Library\Environment;
$envDomain = Environment::getPrefixEnvironment();
define('ACCESS_CONTROL_SERVICE',"https://".$envDomain."accesscontrol.symper.vn");
define('SYQL_SERVICE',"https://".$envDomain."syql.symper.vn");
define('ACCOUNT_SERVICE',"https://".$envDomain."account.symper.vn");
define('MESSAGE_BUS_API',"https://message-bus.symper.vn/");
define('LUFFY_SERVICE',"https://luffy.symper.vn");
define('KAFKA_NODE',"k1.symper.vn");
