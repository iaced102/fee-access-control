<?php
namespace Controller;

use Model\RoleAction;

class RoleActionService extends Controller
{
    function __construct()
    {
        parent::__construct();
        $this->defaultAction = '';
        $this->requireLogin = true;
    }

    public function makeNewViewForTenant()
    {
        if ($this->checkParameter(['tenantId'])) {
            RoleAction::makeNewViewForTenant($this->parameters['tenantId']);
            $this->output = [
                'message' => 'OK',
                'status'    => STATUS_OK
            ];
        }else{
            $this->output = [
                'message' => 'tenantId not found in param',
                'status'    => STATUS_BAD_REQUEST
            ];
        }      
    }
    
}

