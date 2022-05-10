<?php

use Library\Environment;
$envDomain = Environment::getPrefixEnvironment();
define('API_ACCESS_CONTROL',"https://".$envDomain."accesscontrol.vthmgroup.vn/");
define('ACCESS_CONTROL_SERVICE',"https://".$envDomain."accesscontrol.vthmgroup.vn/");
define('SYQL_SERVICE',"https://".$envDomain."syql.vthmgroup.vn/");
define('ACCOUNT_SERVICE',"https://".$envDomain."account.vthmgroup.vn/");
define('MESSAGE_BUS_API',"https://message-bus.vthmgroup.vn/");
define('LUFFY_SERVICE',"https://luffy.vthmgroup.vn/");
define('KAFKA_NODE',"symperkafla.vthmgroup.vn/");
