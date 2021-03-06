<?php

/**
 * Controller for interactive service
 */
class ServiceController extends \BaseController
{

    public function init()
    {
        parent::init();
        $this->initHotelList($this->userInfo);
    }

    /**
     * Get shopping order list and control panel
     */
    public function robotShoppingAction()
    {
        $shoppingModel = new ShoppingModel();
        $shoppingList = $shoppingModel->getShoppingList(array(
            'hotelid' => $this->getHotelId(),
            'status' => 1,
            'nopage' => true, //no pagination as we need to list all the shopping in the filter field
        ), 0);
        $filterList = $shoppingModel->getShoppingOrderFilterList(array('hotelid' => $this->getHotelId()), 3500);

        $robotModel = new RobotModel();

        $roomPositionList = $robotModel->getPositionList(
            array(
                'hotelid' => $this->getHotelId(),
                'type' => RobotModel::POSITION_TYPE_ROOM
            )
        );
        $publicPositionList = $robotModel->getPositionList(array(
            'hotelid' => $this->getHotelId(),
            'type' => RobotModel::POSITION_TYPE_OTHER
        ));

        $this->_view->assign('publicPositionList', $publicPositionList['data']['list']);
        $this->_view->assign('roomPositionList', $roomPositionList['data']['list']);
        $this->_view->assign('shoppingList', $shoppingList['data']['list']);
        $this->_view->assign('userList', $filterList['data']['userlist']);
        $this->_view->assign('statusList', $filterList['data']['statuslist']);
        $this->_view->display('service/order.phtml');
    }

    public function gsmOrderAction()
    {
        $userInfo = $this->userInfo;
        $this->_view->assign('serviceUrl', $userInfo['serviceUrl']);
        $this->_view->display('service/gsm_order.phtml');
    }

    public function robotGuideAction()
    {
        $robotModel = new RobotModel();
        $roomPositionList = $robotModel->getPositionList(
            array(
                'hotelid' => $this->getHotelId(),
                'type' => RobotModel::POSITION_TYPE_ROOM
            )
        );
        $floorPositionList = $robotModel->getPositionList(
            array(
                'hotelid' => $this->getHotelId(),
                'type' => RobotModel::POSITION_TYPE_FLOOR
            )
        );
        $otherPositionList = $robotModel->getPositionList(
            array(
                'hotelid' => $this->getHotelId(),
                'type' => RobotModel::POSITION_TYPE_OTHER
            )
        );
        $this->_view->assign('roomPositionList', $roomPositionList['data']['list']);
        $this->_view->assign('floorPositionList', $floorPositionList['data']['list']);
        $this->_view->assign('otherPositionList', $otherPositionList['data']['list']);
        $this->_view->display('service/robot_guide.phtml');
    }

    public function robotCallAction()
    {
        $robotModel = new RobotModel();
        $otherPositionList = $robotModel->getPositionList(
            array(
                'hotelid' => $this->getHotelId(),
                'type' => RobotModel::POSITION_TYPE_OTHER
            )
        );
        $robotList = $robotModel->getRobotList($this->getHotelId());
        $this->_view->assign('publicPositionList', $otherPositionList['data']['list']);
        $this->_view->assign('robotList', $robotList['data']);
        $this->_view->display('service/robot_call.phtml');
    }


    public function historyMessageAction()
    {
        $this->_view->display('service/history_message.phtml');
    }


    /**
     * Call the robot to the specified destination
     */
    public function callRobotAction()
    {

        $dest = intval($this->getPost('dest'));
        $type = $this->getPost('type');
        $hotelId = $this->getHotelId();

        try {
            $paramList = array(
                'dest' => $dest,
                'type' => $type,
                'hotelid' => $hotelId,
                'userid' => $this->userInfo['id']
            );
            $shoppingModel = new ShoppingModel();
            $result = $shoppingModel->callRobot($paramList);
            if ($result['code'] == 0) {
                $result['data']['msg'] = Enum_Lang::getPageText('shopping', 'robotontheway');
            }
        } catch (Exception $e) {
            $result = array(
                'code' => $e->getCode(),
                'msg' => $e->getMessage(),
                'data' => array()
            );
        }

        $this->echoJson($result);
    }

    /**
     * Deliver the items to the guest's room from the start position
     */
    public function deliverRobotAction()
    {

        $itemList = $this->getPost('itemList');
        $start = intval($this->getPost('start'));
        $dest = intval($this->getPost('dest'));
        $hotelId = $this->getHotelId();

        try {
            $paramList = array(
                'start' => $start,
                'dest' => $dest,
                'hotelid' => $hotelId,
                'itemlist' => $itemList,
                'userid' => $this->userInfo['id']
            );
            $shoppingModel = new ShoppingModel();
            $result = $shoppingModel->deliverRobot($paramList);

            if ($result['code'] == 0) {
                $result['data']['msg'] = Enum_Lang::getPageText('shopping', 'robotontheway');
            }
        } catch (Exception $e) {
            $result = array(
                'code' => $e->getCode(),
                'msg' => $e->getMessage(),
                'data' => array()
            );
        }

        $this->echoJson($result);
    }

    public function robotGetAction()
    {
        $robotModel = new RobotModel();
        $roomPositionList = $robotModel->getPositionList(
            array(
                'hotelid' => $this->getHotelId(),
                'type' => RobotModel::POSITION_TYPE_ROOM
            )
        );
        $publicPositionList = $robotModel->getPositionList(array(
            'hotelid' => $this->getHotelId(),
            'type' => RobotModel::POSITION_TYPE_OTHER
        ));


        $statusList = Enum_Robot::getStatusList();

        $this->_view->assign('publicPositionList', $publicPositionList['data']['list']);
        $this->_view->assign('roomPositionList', $roomPositionList['data']['list']);
        $this->_view->assign('statusList', $statusList);
        $this->_view->display('service/robot_get.phtml');
    }

    public function pinAction()
    {
        $userModel = new UserModel();
        $response = $userModel->getList(array(
            'hotelid' => $this->getHotelId(),
            'limit' => 0
        ));
        $data = $response['data']['list'];
        $roomList = array_unique(array_column($data, 'room_no'));
        $nameList = array_unique(array_column($data, 'fullname'));
        sort($roomList);
        sort($nameList);

        $this->_view->assign('token', $this->getToken());
        $this->_view->assign('roomList', $roomList);
        $this->_view->assign('nameList', $nameList);
        $this->_view->display('service/pin.phtml');
    }


}
