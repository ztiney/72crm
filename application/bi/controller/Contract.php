<?php
// +----------------------------------------------------------------------
// | Description: 商业智能-员工业绩分析
// +----------------------------------------------------------------------
// | Author: Michael_xu | gengxiaoxu@5kcrm.com 
// +----------------------------------------------------------------------

namespace app\bi\controller;

use app\admin\controller\ApiCommon;
use think\Hook;
use think\Request;

class Contract extends ApiCommon
{
    /**
     * 用于判断权限
     * @permission 无限制
     * @allow 登录用户可访问
     * @other 其他根据系统设置
    **/    
    public function _initialize()
    {
        $action = [
            'permission'=>[''],
            'allow'=>['analysis','summary']            
        ];
        Hook::listen('check_auth',$action);
        $request = Request::instance();
        $a = strtolower($request->action());        
        if (!in_array($a, $action['permission'])) {
            parent::_initialize();
        }
        if (!checkPerByAction('bi', 'contract' , 'read')) {
            header('Content-Type:application/json; charset=utf-8');
            exit(json_encode(['code'=>102,'error'=>'无权操作']));
        }         
    }

    /**
     * 合同数量分析/金额分析/回款金额分析
     * @return 
     */
    public function analysis()
    {
        $userModel = new \app\admin\model\User();
        $receivablesModel = new \app\bi\model\Receivables();
        $biContractModel = new \app\bi\model\Contract();
        $adminModel = new \app\admin\model\Admin(); 
        $param = $this->param;
        $perUserIds = $userModel->getUserByPer('bi', 'contract', 'read'); //权限范围内userIds
        $whereArr = $adminModel->getWhere($param, '', $perUserIds); //统计条件
        $userIds = $whereArr['userIds'];

        if (empty($param['year'])) {
            $year = date('Y');
        } else {
            $year = $param['year'];
        }
        $datas = array();
        for ($i=1; $i <= 12; $i++) { 
            $whereArr = [];
            $whereArr['owner_user_id'] = array('in',$userIds);
            $item = array();
            $item['type'] = $i.'月';
            //时间段
            $start_time = $year.'-'.$i.'-01';
            $end_time = $year.'-'.($i+1).'-01';
            if ($i == 12) {
                $start_time = $year.'-'.$i.'-01';
                $end_time = ($year+1).'-01-01';
            }
            $create_time = [];
            if ($start_time && $end_time) {
                $create_time = array('between',array($start_time,$end_time));
            }
            if ($param['type'] == 'back') {
                $time = 'return_time';
            } else {
                $time = 'order_date';
            }
            $whereArr['check_status'] = array('eq',2);
            $whereArr[$time] = $create_time;
            //当月
            if ($param['type'] == 'count') {
                $item['month'] = $biContractModel->getDataCount($whereArr);
            } elseif ($param['type'] == 'money') {
                $item['month'] = $biContractModel->getDataMoney($whereArr);
            } elseif ($param['type'] == 'back') {
                $item['month'] = $receivablesModel->getDataMoney($whereArr);
            }
            //上月
            if ($i == 1) {
                $start_time = ($year-1).'-12-01';
                $end_time = $year.'-01-01';
            } else {
                $start_time = $year.'-'.($i-1).'-01';
                $end_time = $year.'-'.$i.'-01';
            }
            $create_time = [];
            if ($start_time && $end_time) {
                $create_time = array('between',array($start_time,$end_time));
            }
            $whereArr[$time] = $create_time;
            if ($param['type'] == 'count') {
                $item['lastMonth'] = $biContractModel->getDataCount($whereArr);
            } elseif ($param['type'] == 'money') {
                $item['lastMonth'] = $biContractModel->getDataMoney($whereArr);
            } elseif ($param['type'] == 'back') {
                $whereArr['return_time'] = $create_time;
                $item['lastMonth'] = $receivablesModel->getDataMoney($whereArr);
            }
            
            //去年当月
            $start_time = ($year-1).'-'.$i.'-01';
            $end_time = ($year-1).'-'.($i+1).'-01';
            if ($i == 12) {
                $start_time = ($year-1).'-'.$i.'-01';
                $end_time = ($year).'-01-01';
            }
            $create_time = [];
            if ($start_time && $end_time) {
                $create_time = array('between',array($start_time,$end_time));
            }
            $whereArr[$time] = $create_time;
            if ($param['type'] == 'count') {
                $item['lastYeatMonth'] = $biContractModel->getDataCount($whereArr);
            } elseif ($param['type'] == 'money') {
                $item['lastYeatMonth'] = $biContractModel->getDataMoney($whereArr);
            } elseif ($param['type'] == 'back') {
                $whereArr['return_time'] = $create_time;
                $item['lastYeatMonth'] = $receivablesModel->getDataMoney($whereArr);
            }
            
            // //去年上月
            if ($i == 1) {
                $start_time = ($year-2).'-12-01';
                $end_time = ($year-1).'-01-01';
            } else {
                $start_time = ($year-1).'-'.($i-1).'-01';
                $end_time = ($year-1).'-'.$i.'-01';
            }
            $create_time = [];
            if ($start_time && $end_time) {
                $create_time = array('between',array($start_time,$end_time));
            }
            $whereArr[$time] = $create_time;
            if ($param['type'] == 'count') {
                $item['lastYeatLastMonth'] = $biContractModel->getDataCount($whereArr);
            } elseif ($param['type'] == 'money') {
                $item['lastYeatLastMonth'] = $biContractModel->getDataMoney($whereArr);
            } elseif ($param['type'] == 'back') {
                $whereArr['return_time'] = $create_time;
                $item['lastYeatLastMonth'] = $receivablesModel->getDataMoney($whereArr);
            }
            
            //环比增长
            if ($item['month'] == 0 || $item['lastMonth'] == 0) {
                $item['chain_ratio'] = 0;
            } else {
                $item['chain_ratio'] = round(($item['month']/$item['lastMonth']),4)*100;
            }
            //同比增长
            if ($item['month'] == 0 || $item['lastYeatMonth'] == 0) {
                $item['year_on_year'] = 0;
            } else {
                $item['year_on_year'] = round(($item['month']/$item['lastYeatMonth']),4)*100;
            }
            $datas[] = $item;
        }
        return resultArray(['data' => $datas]);
    }
    
    /**
     * 合同汇总表
     * @return 
     */
    public function summary()
    {
        $userModel = new \app\admin\model\User();
        $receivablesModel = new \app\bi\model\Receivables();
        $biContractModel = new \app\bi\model\Contract();
        $biCustomerModel = new \app\bi\model\Customer();
        $adminModel = new \app\admin\model\Admin(); 
        $param = $this->param;
        $perUserIds = $userModel->getUserByPer('bi', 'contract', 'read'); //权限范围内userIds
        $whereArr = $adminModel->getWhere($param, '', $perUserIds); //统计条件
        $userIds = $whereArr['userIds'];
        $between_time = $whereArr['between_time'];
        
        if(empty($param['type']) && empty($param['start_time'])){
            $param['type'] = 'month';
        }
        $company = $biCustomerModel->getParamByCompany($param);
        $datas = array();
        for ($i=1; $i <= $company['j']; $i++) { 
            $whereArr = [];
            $whereArr['owner_user_id'] = array('in',$userIds);
            $whereArr['check_status'] = array('eq',2);
            $item = array();
            //时间段
            $timeArr = $biCustomerModel->getStartAndEnd($param,$company['year'],$i);
            $item['type'] = $timeArr['type'];
            $day = $timeArr['day']?$timeArr['day']:'1';
            $start_time = $timeArr['year'].'-'.$timeArr['month'].'-'.$day;
            $next_day = $timeArr['next_day']?$timeArr['next_day']:'1';
            $end_time = $timeArr['next_year'].'-'.$timeArr['next_month'].'-'.$next_day;
            $create_time = [];
            if ($start_time && $end_time) {
                $create_time = array('between',array($start_time,$end_time));
            }
            $where = array();
            $where = $whereArr;
            $where['order_date'] = $create_time;
            $item['count'] = $biContractModel->getDataCount($where);
            $item['money'] = $biContractModel->getDataMoney($where);
            $where_b = array();
            $where_b = $whereArr;
            $where_b['return_time'] = $create_time;
            $item['back'] = $receivablesModel->getDataMoney($where_b);
            $datas['items'][] = $item;
        }
        $whereArr['create_time'] = array('between',$between_time);
        $datas['count_zong'] = $biContractModel->getDataCount($whereArr);
        $datas['money_zong'] = $biContractModel->getDataMoney($whereArr);
        $datas['back_zong'] = $receivablesModel->getDataMoney($whereArr);
        $datas['w_back_zong'] = $datas['money_zong']-$datas['back_zong'];
        return resultArray(['data' => $datas]);
    }
}
