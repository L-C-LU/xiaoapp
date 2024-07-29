<?php


namespace App\Model;


use App\Base\BaseModel;
use EasySwoole\Utility\Str;

Class PrinterModel  extends BaseModel
{
    protected $tableName = "shop_printer";
    protected $autoTimeStamp = true;

    /**
     * 我的关注
     * @param array $params
     * @param string $sortColumn
     * @param string $sortDirect
     * @param int $pageSize
     * @param int $page
     * @param string $fields
     * @return array
     * @throws
     */
    public function list(
        array $params = [],
        string $sortColumn,
        string $sortDirect,
        int $pageSize = 10,
        int $page = 1,
        string $fields = ''
    ): array
    {
        $this->setParams($params);
        $model = $this->alias("prt");

        $fromTime = strtotime('-1 month', time());

        $columns = "
		prt.printer_id,
		prt.name,
		prt.type,
		prt.sid,
		prt.secret,
		prt.status,
		prt.is_auto_print,
		prt.shop_id,
		prt.print_times,
		prt.for_here_print_times";

        if ($fields == "") $fields = $columns;

        $this->buildWhereNotNull($model, 'prt', 'status');
        $this->buildWhere($model, 'prt', 'shop_id');

        $this->buildOrder($model, 'prt', $sortColumn, $sortDirect, 'status', 'DESC');

        $this->buildOrder($model, 'prt', $sortColumn, $sortDirect, 'create_time', 'DESC');

        return $this->getPageList($model, 'prt', $fields, $pageSize, $page);
    }


}