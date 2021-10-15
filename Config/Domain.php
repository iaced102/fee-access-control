<?php

use Library\Environment;
$envDomain = Environment::getPrefixEnvironment();
define('API_ACCESS_CONTROL',"https://".$envDomain."accesscontrol.symper.vn");
define('MESSAGE_BUS_API',"https://message-bus.symper.vn/");
