<?php

namespace Library;

use Library\Request;

class ObjectRelation
{
    /**
     * Lưu thông tin về các object và link giữa các object 
     * @param nodes có dạng 
     * [
     *       [
     *            'name'  => 'symper_wbs',
     *            'id'    => "document:2186",
     *            'title' => 'Tác vụ',
     *            'type'  => 'document',
     *            'host'  => "document:2186"
     *        ]
     * ]
     *  @param links có dạng 
     * [
     *       [
     *            'start'  => 'symper_wbs',
     *            'end'    => "document:2186",
     *            'type'  => 'document',
     *            'host'  => "document:2186" // có thể set hoặc không, nếu không set ở trong từng link thì phải set ở biến $host
     *        ]
     * ]
     * 
     * @param host đối tượng bao bọc tất cả các node và các link cần lưu, 
     * định dạng như cách định nghĩa objectIdentifier trong access control, vd: document:2186 hay dashboard:123
     */

    public static function save($nodes, $links, $host)
    {
        $data = [
            'relations' => $links,
            'nodes'     => $nodes,
            'host'      => $host
        ];
        MessageBus::publish("object-relation-data", "save", $data);
    }


    /**
     * Xóa nodes và links từ host được truyền vào
     * @param string $host chứa 1 hoặc nhiều host, các host cách nhau bằng dấu phẩy  
     */
    public static function deleteNodesAndLinks($host, $deleteByCohost = false)
    {
        $url = OBJECT_RELATION . "/object-host/$host";
        if ($deleteByCohost) {
            $url = OBJECT_RELATION . "/object-cohost/$host";
        }
        $token = "Bearer " . Auth::getBearerToken();
        $res = Request::request(
            $url,
            false,
            'DELETE',
            $token,
            'application/x-www-form-urlencoded',
            false
        );
        return $res;
    }
}
