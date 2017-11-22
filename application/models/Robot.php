<?php

/**
 * Class RobotModel
 */
class RobotModel extends \BaseModel {

    const POSITION_TYPE_ROOM = 1;
    const POSITION_TYPE_FLOOR = 2;
    const POSITION_TYPE_OTHER = 3;

    private $_positionTypeList = array(
        self::POSITION_TYPE_ROOM => '房间',
        self::POSITION_TYPE_FLOOR => '楼层',
        self::POSITION_TYPE_OTHER => '其他公共区域'
    );

    public function getPositionTypeList()
    {
        return $this->_positionTypeList;
    }

    /**
     * Call robot to deliver the products to the guest's room
     *
     * @param $paramList
     * @return array
     */
    public function deliverRobot($paramList)
    {
        do {
            $result = array(
                'code' => 1,
                'msg' => '参数错误'
            );

            $params['hotelid'] = intval($paramList['hotelid']);
            if ($params['hotelid'] <= 0) {
                break;
            }

            if(!is_array($paramList['itemlist']) || count($paramList['itemlist']) <= 0) break;
            foreach ($paramList['itemlist'] as &$item) {
                $orderId = intval($item);
                if ($orderId <= 0) {
                    break 2;
                } else {
                    $item = $orderId;
                }
            }
            $params['itemlist'] = json_encode($paramList['itemlist']);

            $params['start'] = intval($paramList['start']);
            if ($params['start'] <= 0) {
                break;
            }

            $params['userid'] = intval($paramList['userid']);
            if ($params['userid'] <= 0) {
                break;
            }

            $result = $this->rpcClient->getResultRaw('GS011', $params);
        } while (false);

        return (array)$result;
    }

    /**
     * 新建和编辑tag信息数据
     */
    public function savePosition($paramList) {
        $params = $this->initParam($paramList);
        do {
            $result = array(
                'code' => 1,
                'msg' => '参数错误'
            );
            if (empty($params['position']) || empty($params['robot_position'])) {
                break;
            }
            $interfaceId = $params['id'] ? 'RT002' : 'RT001';
            $result = $this->rpcClient->getResultRaw($interfaceId, $params);
            if (!$result['code']) {
                $this->getPositionList(array('hotelid' => $params['hotelid']), -2);
            }
        } while (false);
        return $result;
    }




    /**
     * Get position list
     *
     * @param $paramList
     * @return array
     */
    public function getPositionList($paramList, $cacheTime = 0)
    {
        do {
            $paramList['id'] ? $params['id'] = $paramList['id'] : false;
            $paramList['type'] ? $params['type'] = $paramList['type'] : false;
            $paramList['hotelid'] ? $params['hotelid'] = $paramList['hotelid'] : false;
            $this->setPageParam($params, $paramList['page'], $paramList['limit'], 10);
            $isCache = $cacheTime != 0 ? true : false;
            $result = $this->rpcClient->getResultRaw('RT003', $params, $isCache, $cacheTime);
        } while (false);
        return (array)$result;
    }

    /**
     * Get position list
     *
     * @int $hotelid
     * @return array
     */
    public function getPublicPositionList(int $hotelid, $cacheTime = 0)
    {
        do {
            $params['hotelid'] = $hotelid;
            $isCache = $cacheTime != 0 ? true : false;
            $result = $this->rpcClient->getResultRaw('RT003', $params, $isCache, $cacheTime);
            if($result['code'] == 0 && is_array($result['data']['list'])){
                $publicList = array();
                $total = 0;
                foreach ($result['data']['list'] as $row){
                    if($row['type'] != self::POSITION_TYPE_ROOM){
                        $publicList[] = $row;
                        $total++;
                    }
                }
                $result['data']['list']  = $publicList;
                $result['data']['total'] = $total;
            }

        } while (false);
        return (array)$result;
    }


}
