<?php
/**
 * Created by PhpStorm.
 * User: eValor
 * Date: 2018/11/10
 * Time: 上午1:52
 */

namespace App\AutomaticGeneration;

use App\AutomaticGeneration\Config\ModelConfig;
use EasySwoole\Utility\File;
use EasySwoole\Utility\Str;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\PhpNamespace;

/**
 * easyswoole model快速构建器
 * Class BeanBuilder
 * @package AutomaticGeneration
 */
class ModelBuilder
{
    /**
     * @var $config ModelConfig
     */
    protected $config;
    protected $className;

    /**
     * BeanBuilder constructor.
     * @param  $config
     * @throws \Exception
     */
    public function __construct(ModelConfig $config)
    {
        $this->config = $config;
        $this->createBaseDirectory($config->getBaseDirectory());
        $realTableName = $this->setRealTableName() . 'Model';
        $this->className = $this->config->getBaseNamespace() . '\\' . $realTableName;
    }

    /**
     * createBaseDirectory
     * @param $baseDirectory
     * @throws \Exception
     * @author Tioncico
     * Time: 19:49
     */
    protected function createBaseDirectory($baseDirectory)
    {
        File::createDirectory($baseDirectory);
    }

    /**
     * generateBean
     * @return bool|int
     * @author Tioncico
     * Time: 19:49
     */
    public function generateModel()
    {
        $phpNamespace = new PhpNamespace($this->config->getBaseNamespace());
        $realTableName = $this->setRealTableName() . 'Model';
        $phpClass = $this->addClassBaseContent($this->config->getTableName(), $realTableName, $phpNamespace, $this->config->getTableComment(), $this->config->getTableColumns());

        $this->addListMethod($phpClass);
        $this->addFetchMethod($phpClass);
        $this->addGetMethod($phpClass);
        $this->addAddMethod($phpClass);
        $this->addDeleteMethod($phpClass);
        $this->addUpdateMethod($phpClass);


        return $this->createPHPDocument($this->config->getBaseDirectory() . '/' . $realTableName, $phpNamespace, $this->config->getTableColumns());
    }


    /**
     * 处理表真实名称
     * setRealTableName
     * @return bool|mixed|string
     * @author tioncico
     * Time: 下午11:55
     */
    function setRealTableName()
    {
        if ($this->config->getRealTableName()) {
            return $this->config->getRealTableName();
        }
        //先去除前缀
        //$tableName = substr($this->config->getTableName(), strlen($this->config->getTablePre()));
        $tableName = substr($this->config->getTableName(), strpos($this->config->getTableName(), '_') + 1);
        //去除后缀
        $tableName = str_replace($this->config->getIgnoreString(), '', $tableName);
        //下划线转驼峰,并且首字母大写
        $tableName = ucfirst(Str::camel($tableName));
        $this->config->setRealTableName($tableName);
        return $tableName;
    }

    /**
     * 新增基础类内容
     * addClassBaseContent
     * @param $tableName
     * @param $realTableName
     * @param $phpNamespace
     * @param $tableComment
     * @return ClassType
     * @author Tioncico
     * Time: 21:38
     */
    protected function addClassBaseContent($tableName, $realTableName, $phpNamespace, $tableComment, $tableColumns): ClassType
    {
        $phpClass = $phpNamespace->addClass($realTableName);
        //配置类基本信息
        if ($this->config->getExtendClass()) {
            $phpNamespace->addUse($this->config->getExtendClass());
            $phpClass->addExtend($this->config->getExtendClass());
        }
        $phpNamespace->addUse($this->config->getBeanClass());
        $phpNamespace->addUse(\EasySwoole\Mysqli\Exceptions\ConnectFail::class);
        $phpNamespace->addUse(\EasySwoole\Mysqli\Exceptions\Option::class);
        $phpNamespace->addUse(\EasySwoole\Mysqli\Exceptions\OrderByFail::class);
        $phpNamespace->addUse(\EasySwoole\Mysqli\Exceptions\PrepareQueryFail::class);
        $phpNamespace->addUse(\EasySwoole\MysqliPool\Connection::class);
        $phpNamespace->addUse(\EasySwoole\Utility\Str::class);
        $phpNamespace->addUse(\Throwable::class);

        $phpClass->addComment("{$tableComment}");
        $phpClass->addComment("Class {$realTableName}");
        $phpClass->addComment('Create With Automatic Generator');
        //配置表名属性
        $phpClass->addProperty('table', $tableName)
            ->setStatic(true)
            ->setVisibility('public');
        foreach ($tableColumns as $column) {
            if ($column['Key'] == 'PRI') {
                $this->config->setPrimaryKey($column['Field']);
                $phpClass->addProperty('pk', $column['Field'])
                    ->setVisibility('protected');
                break;
            }
        }
        return $phpClass;
    }

    protected function addUpdateMethod(ClassType $phpClass)
    {
        $method = $phpClass->addMethod('update');
        $method->setStatic(true);
        $beanName = $this->setRealTableName() . 'Bean';;
        $namespaceBeanName = $this->config->getBaseNamespace() . '\\' . $beanName;
        //配置基础注释
        $method->addComment("根据主键或组合条件更新记录");
        $method->addComment("@update");
        $method->addComment("@param  Connection \$". 'db');
        $method->addComment("@param  \$". 'idOrArray');//主键或组合条件
        $method->addComment("@param  {$beanName} \$newBean");

        //配置返回类型
        $method->setReturnType('bool');

        $method->addComment("@throws  ConnectFail");
        $method->addComment("@throws  PrepareQueryFail");
        $method->addComment("@throws  Throwable");

        //配置参数
        $method->addParameter('db')->setTypeHint('EasySwoole\MysqliPool\Connection');
        $method->addParameter('idOrArray');
        $method->addParameter('newBean')->setTypeHint($namespaceBeanName);

        $primaryKey = $this->config->getPrimaryKey();


        $methodBody = <<<Body

if(empty(\$idOrArray)) return false;

if(is_array(\$idOrArray)){
    foreach(\$idOrArray as \$key => \$value){
        \$db->where(\$key, \$value);
    }
}
else{
    \$db->where('$primaryKey', \$idOrArray);
}

return \$db->update(self::\$table, \$newBean->toArray([], \$newBean::FILTER_NOT_NULL));
Body;
        $method->setBody($methodBody);
        $method->addComment("@return bool");
    }

    protected function addDeleteMethod(ClassType $phpClass)
    {
        $method = $phpClass->addMethod('delete');
        $method->setStatic(true);

        //配置基础注释
        $method->addComment("根据主键或组合条件删除记录");
        $method->addComment("@delete");
        $method->addComment("@param  Connection \$". 'db');
        $method->addComment("@param  \$". 'idOrArray');//主键或组合条件

        //配置返回类型
        $method->setReturnType('bool');

        $method->addComment("@throws  ConnectFail");
        $method->addComment("@throws  PrepareQueryFail");
        $method->addComment("@throws  Throwable");

        $method->addParameter('db')->setTypeHint('EasySwoole\MysqliPool\Connection');
        $method->addParameter('idOrArray');

        $primaryKey = $this->config->getPrimaryKey();
        $primaryKey_camel = Str::camel($this->config->getPrimaryKey());

        $methodBody = <<<Body

if(empty(\$idOrArray)) return false;

if(is_array(\$idOrArray)){
    foreach(\$idOrArray as \$key => \$value){
        \$db->where(\$key, \$value);
    }
}
else{
    \$db->where('$primaryKey', \$idOrArray);
}

return \$db->delete(self::\$table);
Body;
        $method->setBody($methodBody);
        $method->addComment("@return bool");
    }

    protected function addAddMethod(ClassType $phpClass)
    {
        $method = $phpClass->addMethod('add');
        $method->setStatic(true);
        $beanName = $this->setRealTableName() . 'Bean';;
        $namespaceBeanName = $this->config->getBaseNamespace() . '\\' . $beanName;

        $getPrimaryKeyMethodName = "get" . Str::studly($this->config->getPrimaryKey());

        //配置基础注释
        $method->addComment("默认根据bean数据进行插入数据");
        $method->addComment("@add");
        $method->addComment("@param  Connection \$". 'db');
        $method->addComment("@param  {$beanName} \$bean");//默认为使用Bean注释

        //配置参数为bean
        $method->addParameter('db')->setTypeHint('EasySwoole\MysqliPool\Connection');
        $method->addParameter('bean')->setTypeHint($namespaceBeanName);
        //配置返回类型
        $method->setReturnType('bool');

        $method->addComment("@throws  ConnectFail");
        $method->addComment("@throws  PrepareQueryFail");
        $method->addComment("@throws  Throwable");

        $methodBody = <<<Body

return \$db->insert(self::\$table, \$bean->toArray(null, \$bean::FILTER_NOT_NULL));
Body;
        $method->setBody($methodBody);
        $method->addComment("@return bool");
    }



    protected function addGetMethod(ClassType $phpClass)
    {
        $method = $phpClass->addMethod('get');
        $method->setStatic(true);
        $beanName = $this->setRealTableName() . 'Bean';;
        $namespaceBeanName = $this->config->getBaseNamespace() . '\\' . $beanName;

        $primaryKey = $this->config->getPrimaryKey();

        //配置基础注释
        $method->addComment("根据主键或组合条件查询一条记录并转为bean");
        $method->addComment("@get");
        $method->addComment("@param  Connection \$". 'db');
        $method->addComment("@param  \$". 'idOrArray');//主键或组合条件
        $method->addComment("@param  string \$field");//字段

        //配置返回类型
        $method->setReturnType($namespaceBeanName)->setReturnNullable();

        $method->addComment("@throws  ConnectFail");
        $method->addComment("@throws  PrepareQueryFail");
        $method->addComment("@throws  Throwable");

        //配置参数为bean
        $method->addParameter('db')->setTypeHint('EasySwoole\MysqliPool\Connection');
        $method->addParameter('idOrArray');
        $method->addParameter('field', '')->setTypeHint('string');

        $methodBody = <<<Body

if(empty(\$field)) \$field = '*';

if(empty(\$idOrArray)) return null;

if(is_array(\$idOrArray)){
    foreach(\$idOrArray as \$key => \$value){
        \$db->where(\$key, \$value);
    }
}
else{
    \$db->where('$primaryKey', \$idOrArray);
}

\$info = \$db->getOne(self::\$table, \$field);
if (empty(\$info)) {
    return null;
}
return new $beanName(\$info);
Body;


        $method->setBody($methodBody);
        $method->addComment("@return $beanName");
    }


    protected function addFetchMethod(ClassType $phpClass)
    {
        $method = $phpClass->addMethod('fetch');
        $method->setStatic(true);


        $primaryKey = $this->config->getPrimaryKey();

        //配置基础注释
        $method->addComment("查询列表");
        $method->addComment("@get");
        $method->addComment("@param  Connection \$". 'db');
        $method->addComment("@param  array \$". 'conditionArr');//主键或组合条件
        $method->addComment("@param  string \$field");//字段

        //配置返回类型
        $method->setReturnType("array")->setReturnNullable();

        $method->addComment("@throws  ConnectFail");
        $method->addComment("@throws  PrepareQueryFail");
        $method->addComment("@throws  Throwable");

        //配置参数为bean
        $method->addParameter('db')->setTypeHint('EasySwoole\MysqliPool\Connection');
        $method->addParameter('conditionArr')->setTypeHint('array');
        $method->addParameter('field', '')->setTypeHint('string');

        $methodBody = <<<Body

if(empty(\$field)) \$field = '*';

foreach(\$conditionArr as \$key => \$value){
    \$db->where(\$key, \$value);
}

return \$db->get(self::\$table, null, \$field);
Body;


        $method->setBody($methodBody);
        $method->addComment("@return array");
    }


    protected function addListMethod(ClassType $phpClass, $keywordArr = [])
    {
        $method = $phpClass->addMethod('list');

        //配置基础注释
        $method->addComment("分页查询记录");

        //配置方法参数
        $method->addParameter('params', [])
            ->setTypeHint('array');

        $method->addParameter('sortColumn', $this->config->getPrimaryKey())
            ->setTypeHint('string');

        $method->addParameter('sortDirect', 'DESC')
            ->setTypeHint('string');

        $method->addParameter('pageSize', 10)
            ->setTypeHint('int');

        $method->addParameter('page', 1)
            ->setTypeHint('int');

        $method->addParameter('field', '')->setTypeHint('string');

        foreach ($method->getParameters() as $parameter) {
            $method->addComment("@param  " . $parameter->getTypeHint() . '  $' . $parameter->getName() . '  ' . (is_array($parameter->getDefaultValue()) ? '[]' : $parameter->getDefaultValue()));
        }

        //配置返回类型
        $method->setReturnType('array');

        $method->addComment("@throws  ConnectFail");
        $method->addComment("@throws  Option");
        $method->addComment("@throws  OrderByFail");
        $method->addComment("@throws  PrepareQueryFail");
        $method->addComment("@throws  Throwable");

        $methodBody = '';

        $methodBody .= <<<Body
\$db = \$this->getDb();

\$this->setParams(\$params);

\$columns = "
Body;

        $tableColumns = $this->config->getTableColumns();

        $columnBody = '';
        foreach ($tableColumns as $column) {
            $snakeKey = Str::snake($column['Field']);
            $camelKey = Str::camel($column['Field']);

            if ($camelKey == $snakeKey) {
                $columnBody .= ','.PHP_EOL. $snakeKey;
            } else {
                $columnBody .= ','.PHP_EOL. $snakeKey . ' as ' . $camelKey;
            }
        }
        $columnBody = substr($columnBody, 1);
        $methodBody .= $columnBody. PHP_EOL;
        $methodBody .= <<<Body
";

if(\$sortColumn == "") \$sortColumn = '{$this->config->getPrimaryKey()}' ;
if(\$sortDirect == "") \$sortDirect = 'DESC';
if(\$pageSize == "") \$pageSize = 10;
if(\$page == "") \$page = 1;

if(\$field == "") \$field = \$columns;
        
 if (!empty(\$this->getParam("keyword"))) {
    \$db->where('columnNameToSet', '%' . \$this->getParam("keyword") . '%', 'like');
}
Body;


        $methodBody .= <<<Body

\$sortColumn = Str::snake(\$sortColumn);
if(strtoupper(\$sortDirect) != 'DESC') \$sortDirect = 'ASC';       
\$list = \$db->withTotalCount()
    ->orderBy(\$sortColumn, \$sortDirect)
    ->get(self::\$table, [\$pageSize * (\$page  - 1), \$pageSize], \$field);
    
\$total = \$db->getTotalCount();
return [
        'total' => \$total, 
        'page' => \$page,
        'pageSize' => \$pageSize,
        'pageCount' => \$this->totalPages(\$pageSize),
        'list' => \$list
       ];
        
Body;
        //配置方法内容
        $method->setBody($methodBody);
        $method->addComment('@return array[total, page, pageSize, pageCount, list]');
    }

    /**
     * convertDbTypeToDocType
     * @param $fieldType
     * @return string
     * @author Tioncico
     * Time: 19:49
     */
    protected function convertDbTypeToDocType($fieldType)
    {
        $newFieldType = strtolower(strstr($fieldType, '(', true));
        if ($newFieldType == '') $newFieldType = strtolower($fieldType);
        if (in_array($newFieldType, ['tinyint', 'smallint', 'mediumint', 'int', 'bigint'])) {
            $newFieldType = 'int';
        } elseif (in_array($newFieldType, ['float', 'double', 'real', 'decimal', 'numeric'])) {
            $newFieldType = 'float';
        } elseif (in_array($newFieldType, ['char', 'varchar', 'text'])) {
            $newFieldType = 'string';
        } else {
            $newFieldType = 'mixed';
        }
        return $newFieldType;
    }

    /**
     * createPHPDocument
     * @param $fileName
     * @param $fileContent
     * @param $tableColumns
     * @return bool|int
     * @author Tioncico
     * Time: 19:49
     */
    protected function createPHPDocument($fileName, $fileContent, $tableColumns)
    {
        if (file_exists($fileName . '.php')) {
            return "已存在：". $fileName . '.php';
        }

//        var_dump($fileName.'.php');
        if ($this->config->isConfirmWrite()) {
            if (file_exists($fileName . '.php')) {
                echo "(Model)当前路径已经存在文件,是否覆盖?(y/n)\n";
                if (trim(fgets(STDIN)) == 'n') {
                    echo "已结束运行\n";
                    return false;
                }
            }
        }
        $content = "<?php\n\n{$fileContent}\n";
//        var_dump($content);
        $result = file_put_contents($fileName . '.php', $content);
        return $result == false ? $result : $fileName . '.php';
    }

    /**
     * @return mixed
     */
    public function getClassName()
    {
        return $this->className;
    }

}