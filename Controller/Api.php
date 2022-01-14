<?php
namespace Controller;

use Library\Auth;
use Library\Message;
use Model\Users;

class Api extends Controller
{
    //
    function __construct()
    {
        parent::__construct();
        $this->defaultAction = 'test';
        $this->requireLogin = false;
    }
    function login(){
        if(isset($this->parameters['email'])){
            $email = trim(addslashes(strip_tags($this->parameters['email'])));
            $listUser = Users::getByTop(1,"email='$email'");
            if(count($listUser)>0){
                $profile = $listUser[0]->getProfile();
                $this->output['profile'] = $profile;
                $this->output['access_token'] = Auth::getJwtToken($profile);
                
                $this->output['status'] = STATUS_OK;
            }
            else{
                $this->output['status'] = STATUS_NOT_FOUND;
                $this->output['message'] = Message::getStatusResponse(STATUS_NOT_FOUND);
            }
        }
        else{
            $this->output['status'] = STATUS_BAD_REQUEST;
            $this->output['message'] = Message::getStatusResponse(STATUS_BAD_REQUEST);
        }
    }   
    
    public function testGet(){
        // $this->output = Auth::getDataToken();
        $kk = new KafkaService();
        $data = '{"event":"update","data":{"id":"2961","name":"duyen_test_pq_control","title":"\u0110ang test ph\u00e2n quy\u1ec1n control","type":1,"note":"test","version":2,"parent_id":0,"is_editting":0,"create_at":"2022-01-12 20:17:07","update_at":"2022-01-13 10:25:56","ba_update_id":"31","fields":[{"name":"tb1","type":"table","fields":[{"name":"tb1_out_of_scope","type":"text"},{"name":"tb1_moi_truong_test","type":"text"},{"name":"tb1_ten_sub_module","type":"text"},{"name":"tb1_ten_module","type":"text"},{"name":"tb1_sub_module","type":"text"},{"name":"tb1_tieu_chi_test","type":"text"},{"name":"tb1_sub_test_plan_id","type":"text"},{"name":"tb1_nguoi_test","type":"text"},{"name":"tb1_nguoi_lap_test_case","type":"text"},{"name":"tb1_stt","type":"number"},{"name":"tb1_srs_id","type":"text"},{"name":"tb1_in_scope","type":"text"},{"name":"tb1_module","type":"text"},{"name":"tb1_note","type":"text"}]},{"name":"tp_id","type":"text"},{"name":"ngay_lap","type":"date"},{"name":"sprint","type":"text"},{"name":"tmg_status","type":"text"},{"name":"ten_nguoi_tao","type":"text"},{"name":"quan_ly","type":"text"},{"name":"nguoi_tao","type":"text"},{"name":"ten_manager","type":"text"}],"new":[{"id":"15160","name":"tp_id","type":"text","title":"Test plan ID"},{"id":"15162","name":"ngay_lap","type":"date","title":"Ng\u00e0y l\u1eadp"},{"id":"15163","name":"sprint","type":"text","title":"Sprint"},{"id":"15164","name":"tmg_status","type":"text","title":"Status"},{"id":"15168","name":"ten_nguoi_tao","type":"text","title":"T\u00ean ng\u01b0\u1eddi t\u1ea1o"},{"id":"15167","name":"nguoi_tao","type":"text","title":"Ng\u01b0\u1eddi t\u1ea1o"},{"id":"15165","name":"ten_manager","type":"text","title":"T\u00ean manager"},{"id":"15161","name":"quan_ly","type":"text","title":"Project manager"},{"id":"2962","name":"tb1","title":"B\u1ea3ng 1","type":"table","fields":[{"id":"15148","name":"tb1_out_of_scope","type":"text","title":"Out of Scope"},{"id":"15151","name":"tb1_moi_truong_test","type":"text","title":"Test enviroment"},{"id":"15154","name":"tb1_ten_module","type":"text","title":"Module name"},{"id":"15157","name":"tb1_sub_test_plan_id","type":"text","title":"Sub Test Plan ID"},{"id":"15150","name":"tb1_nguoi_test","type":"text","title":"Tester"},{"id":"15153","name":"tb1_nguoi_lap_test_case","type":"text","title":"Test suite creators"},{"id":"15158","name":"tb1_stt","type":"number","title":"STT"},{"id":"15147","name":"tb1_srs_id","type":"text","title":"SRS ID"},{"id":"15149","name":"tb1_in_scope","type":"text","title":"In Scope"},{"id":"15145","name":"tb1_note","type":"text","title":"Note"},{"id":"15152","name":"tb1_ten_sub_module","type":"text","title":"Sub module name"},{"id":"15155","name":"tb1_sub_module","type":"text","title":"Sub module"},{"id":"15156","name":"tb1_tieu_chi_test","type":"text","title":"Test criteria"},{"id":"15146","name":"tb1_module","type":"text","title":"Module"}]}],"old":[{"id":"15160","name":"tp_id","type":"text","title":"Test plan ID"},{"id":"15162","name":"ngay_lap","type":"date","title":"Ng\u00e0y l\u1eadp"},{"id":"15163","name":"sprint","type":"text","title":"Sprint"},{"id":"15164","name":"tmg_status","type":"text","title":"Status"},{"id":"15168","name":"ten_nguoi_tao","type":"text","title":"T\u00ean ng\u01b0\u1eddi t\u1ea1o"},{"id":"15161","name":"quan_ly","type":"text","title":"Project manager"},{"id":"15167","name":"nguoi_tao","type":"text","title":"Ng\u01b0\u1eddi t\u1ea1o"},{"id":"15165","name":"ten_manager","type":"text","title":"T\u00ean manager"},{"id":"2962","name":"tb1","title":"B\u1ea3ng 1","type":"table","fields":[{"id":"15148","name":"tb1_out_of_scope","type":"text","title":"Out of Scope"},{"id":"15151","name":"tb1_moi_truong_test","type":"text","title":"Test enviroment"},{"id":"15152","name":"tb1_ten_sub_module","type":"text","title":"Sub module name"},{"id":"15154","name":"tb1_ten_module","type":"text","title":"Module name"},{"id":"15155","name":"tb1_sub_module","type":"text","title":"Sub module"},{"id":"15156","name":"tb1_tieu_chi_test","type":"text","title":"Test criteria"},{"id":"15157","name":"tb1_sub_test_plan_id","type":"text","title":"Sub Test Plan ID"},{"id":"15150","name":"tb1_nguoi_test","type":"text","title":"Tester"},{"id":"15153","name":"tb1_nguoi_lap_test_case","type":"text","title":"Test suite creators"},{"id":"15158","name":"tb1_stt","type":"number","title":"STT"},{"id":"15147","name":"tb1_srs_id","type":"text","title":"SRS ID"},{"id":"15149","name":"tb1_in_scope","type":"text","title":"In Scope"},{"id":"15146","name":"tb1_module","type":"text","title":"Module"},{"id":"15145","name":"tb1_note","type":"text","title":"Note"}]}]},"time":1642044358.97263}';
        $data = json_decode($data, true);
        $kk->processObject('document_definition', $data);
    }
    public function testPut(){
        $this->output['message'] = 'put';
        $this->output['parameter'] = $this->parameters;
    }
    
    public function testDelete(){
        $this->output['message'] = 'delete';
        $this->output['parameter'] = $this->parameters;
    }
    
    public function testPatch(){
        $this->output['message'] = 'patch';
        $this->output['parameter'] = $this->parameters;
    }
    public function getDemoToken(){
        $this->output['status'] = STATUS_OK;
        $this->output['data'] = $this->parameters;
        $this->output['token'] = Auth::getJwtToken($this->parameters);
    }
    

}