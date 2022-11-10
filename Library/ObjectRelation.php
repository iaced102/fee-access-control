<?php
namespace Library;

use Library\Request;

class ObjectRelation {
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
        $token = "Bearer ".Auth::getBearerToken();
        $res = Request::request(
            "https://".OBJECT_RELATION.'/sub-graph', 
            $data,
            'POST',
            $token,
            'application/json',
            false,
            300
        );
    }


    /**
     * Xóa nodes và links từ host được truyền vào
     */
    public static function deleteNodesAndLinks($host)
    {
        $token = "Bearer ".Auth::getBearerToken();
        $res = Request::request(
            "https://".OBJECT_RELATION."/object-host/$host", 
            false,
            'DELETE',
            $token,
            'application/x-www-form-urlencoded',
            false
        );
        return $res;
    }
}