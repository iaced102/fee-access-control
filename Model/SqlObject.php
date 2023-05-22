<?php

use Model\Model;
use Library\Auth;
use Model\Connection;

class SqlObject extends Model
{
    public $listForeignKey;
    public static $mappingFromDatabase = [];
    public function __construct($data = [])
    {
        $this->listForeignKey = [];
        $keys = get_object_vars($this);
        foreach ($keys as $key => $_) {
            if ($key != 'listForeignKey') {
                $columnNameInDatabase = static::getColumnNameInDataBase($key);
                if (isset($data[$columnNameInDatabase])) {
                    $this->$key = isset($data[$columnNameInDatabase]) ? $data[$columnNameInDatabase] : '';
                } else if (isset($data[$key])) {
                    $this->$key = $data[$key];
                }
            }
        }
    }
    public function encode()
    {
        $arrayKey = get_object_vars($this);
        foreach ($arrayKey as $key => $value) {
            if (is_string($value)) {
                $this->$key = addslashes($value);
            }
        }
    }
    public function getForeignKey($SourceField)
    {
        if (isset($this->listForeignKey)) {
            foreach ($this->listForeignKey as $fk) {
                if ($fk->SourceField == $SourceField) return $fk;
            }
        }
        return null;
    }
    /*  Dev create: Dinh
    *   CreateTime: 31/03/2020
    *   description: lấy condition query để lọc theo TanentId. Đầu vào là field chứa tenantid id, nếu không tìm thấy column in db thì đó là table dùng chung
    *   Nếu có tanent id trong db, thì kiểm tra JWT data, nếu tenantid = '' nghĩa là supporter tối cao, có thể query, nếu có tenantid , thì chỉ chỉ được phép lọc theo tenantid id đó 
    */
    public static function getFilterTenantQueryByFieldName($fieldNameTanentId)
    {
        $tableName          = static::getTableName();
        $columnInDatabase   = static::getColumnNameInDataBase($fieldNameTanentId);
        if ($columnInDatabase != false) {
            $tenantId = Auth::getTenantId();
            if ($tenantId != '') {
                return "$tableName.$columnInDatabase = '$tenantId'";
            }
        }
        return '';
    }
    /*  Dev create: Dinh
    *   CreateTime: 24/06/2020
    *   description: lấy primary column name, nếu không tìm thấy thì mặc định trả về id
    */
    public static function getPrimaryKey()
    {
        foreach (static::$mappingFromDatabase as $fieldName => $ColumnData) {
            if (isset($ColumnData['primary']) && $ColumnData['primary'] == true && isset($ColumnData['name'])) {
                return $fieldName;
            }
        }
        return 'id';
    }
    public static function getColumnNameInDataBase($fieldName, $returnArray = false)
    {
        if (isset(static::$mappingFromDatabase[$fieldName]['name'])) {
            if ($returnArray) {
                return static::$mappingFromDatabase[$fieldName];
            } else {
                return static::$mappingFromDatabase[$fieldName]['name'];
            }
        }
        return false;
    }
    public static function createUUID()
    {
        return sprintf(
            '%08x-%04x-%04x-%04x-%04x%08x',
            time(),
            getmypid(),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            ip2long(\Library\Auth::getCurrentIP())
        );
    }

    /**
     * Chuyển các object từ tenant này sang tenant khác qua id của các object
     * 
     * @param int $sourceTenant id tenant xuất phát
     * @param int $targetTenant id tenant đích cần chuyeenr
     * @param array $ids chứa id của các object cần chuyển 
     * @param boolean $backup có backup dữ liệu cũ trước khi insert dữ liệu mới vào hay không, mặc định là true
     * @param boolean $returnClonedId có trả về id của các dòng đã được clone không, mặc định là true
     * 
     * @return array|false trả về mảng id của các object của model này đã được clone, trả về false nếu quá trình clone thất bại
     */
    public static function migrateObjectsByIds(int $sourceTenant, int $targetTenant, array $ids, $backup = true, $returnClonedId = true)
    {
        $parentStr = "'" . implode("','", $ids) . "'";
        $primaryCol = static::getColumnNameInDataBase(static::getPrimaryKey());
        return static::migrateObjectsByCondition($sourceTenant, $targetTenant, "$primaryCol IN ($parentStr)", $backup, $returnClonedId);
    }


    /**
     * Chuyển các object từ tenant này sang tenant khác qua id của các parent object
     * 
     * @param int $sourceTenant id tenant xuất phát
     * @param int $targetTenant id tenant đích cần chuyeenr
     * @param array $referenceColumn tên cột mà parent column refernce đến
     * @param array $parentId chứa parent id của các object cần chuyển 
     * @param string $extraCondition điều kiện thêm để lọc cùng với parentIds
     * @param boolean $backup có backup dữ liệu cũ trước khi insert dữ liệu mới vào hay không, mặc định là true
     * @param boolean $returnClonedId có trả về id của các dòng đã được clone không, mặc định là true
     * 
     * @return array|false trả về mảng id của các object của model này đã được clone, trả về false nếu quá trình clone thất bại
     */
    public static function migrateObjectsByParents($sourceTenant, $targetTenant, string $referenceColumn, array $parentIds, string $extraCondition = "", $backup = true, $returnClonedId = true)
    {
        $parentStr = "'" . implode("','", $parentIds) . "'";
        $cond = "$referenceColumn IN ($parentStr)";
        if ($extraCondition != "") {
            $cond .= " AND $extraCondition";
        }
        return static::migrateObjectsByCondition($sourceTenant, $targetTenant, $cond, $backup, $returnClonedId);
    }



    /**
     * Chuyển các object từ tenant này sang tenant khác qua điều kiện lọc
     * 
     * @param int $sourceTenant id tenant xuất phát
     * @param int $targetTenant id tenant đích cần chuyeenr
     * @param string $condition điều kiện cần để lọc ra các object cần clone, không cần điều kiện về tenant do đã được tự thêm
     * @param boolean $backup có backup dữ liệu cũ trước khi insert dữ liệu mới vào hay không, mặc định là true
     * @param boolean $returnClonedId có trả về id của các dòng đã được clone không, mặc định là true
     * 
     * @return array|false trả về mảng id của các object của model này đã được clone, trả về false nếu quá trình clone thất bại
     */
    public static function migrateObjectsByCondition($sourceTenant, $targetTenant, $condition, $backup = true, $returnClonedId = true)
    {
        $tableName = static::getTableName();
        $columns = [];

        // Lấy ra các cột trong bảng
        foreach (static::$mappingFromDatabase as $key => $col) {
            $tbColName = $col['name'];
            if ($tbColName != 'tenant_id_') {
                $columns[] = $tbColName;
            }
        }
        $columns = implode(",", $columns);
        $primaryCol = static::getColumnNameInDataBase(static::getPrimaryKey());

        $oppositeTenant = "-$targetTenant";

        // Trường hợp có backup dữ liệu
        $query = [
            "BEGIN",
            // Xoá các bản ghi backup cũ
            "DELETE FROM $tableName WHERE tenant_id_ = '$oppositeTenant' AND ($condition)",

            // Backup các bản ghi của tenant đích
            // "UPDATE $tableName SET tenant_id_ = '$oppositeTenant' WHERE tenant_id_ = '$targetTenant' AND ($condition)",
            "INSERT INTO $tableName($columns, tenant_id_) SELECT $columns, $oppositeTenant as tenant_id_ FROM $tableName WHERE ($condition) AND tenant_id_ = '$targetTenant'",

            // Xoá dữ liệu của tenant đích
            "DELETE FROM $tableName WHERE tenant_id_ = '$targetTenant' AND ($condition)",

            // Thêm các bản ghi từ tenant nguồn vào tenant đích
            "INSERT INTO $tableName($columns, tenant_id_)
                SELECT $columns, '$targetTenant' AS tenant_id_ 
                FROM $tableName 
                WHERE tenant_id_ = '$sourceTenant' AND ($condition)",

            "COMMIT",
        ];

        // Trường hợp không backup dữ liệu
        if (!$backup) {
            $query = [
                "BEGIN",
                // Xoá các bản ghi hiện tại của tenant đích
                "DELETE FROM $tableName WHERE tenant_id_ = '$targetTenant' AND ($condition)",

                // Thêm các bản ghi từ tenant nguồn vào tenant đích
                "INSERT INTO $tableName($columns, tenant_id_)
                    SELECT $columns, '$targetTenant' AS tenant_id_ 
                    FROM $tableName 
                    WHERE tenant_id_ = '$sourceTenant' AND ($condition)",

                "COMMIT",
            ];
        }

        if ($returnClonedId) {
            // Trả về id các bản ghi được clone
            $query[] = "SELECT DISTINCT $primaryCol AS id FROM $tableName WHERE tenant_id_ = '$sourceTenant' AND ($condition)";
        }

        $runQueryResult = Connection::exeQuery(implode(";", $query));
        if ($runQueryResult == false) {
            return false;
        }

        $result = [];
        if ($returnClonedId) {
            $rsl = pg_fetch_all($runQueryResult);
            if (is_array($rsl)) {
                foreach ($rsl as $row) {
                    $result[] = $row['id'];
                }
            }
        }
        return $result;
    }
}
