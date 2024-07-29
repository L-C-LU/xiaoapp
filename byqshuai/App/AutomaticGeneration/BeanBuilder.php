<?php
/**
 * Created by PhpStorm.
 * User: eValor
 * Date: 2018/11/10
 * Time: 上午1:52
 */

namespace App\AutomaticGeneration;

use App\AutomaticGeneration\Config\BeanConfig;
use EasySwoole\Utility\File;
use EasySwoole\Utility\Str;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\PhpNamespace;

/**
 * easyswoole Bean快速构建器
 * Class BeanBuilder
 * @package AutomaticGeneration
 */
class BeanBuilder
{
    /**
     * @var $config BeanConfig;
     */
    protected $config;

    protected $className;

    /**
     * BeanBuilder constructor.
     * @param        $config
     * @throws \Exception
     */
    public function __construct(BeanConfig $config)
    {
        $this->config = $config;
        $this->createBaseDirectory($config->getBaseDirectory());
        $realTableName = $this->setRealTableName() . 'Bean';
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
    public function generateBean()
    {
        $realTableName = $this->setRealTableName() . 'Bean';

        $phpNamespace = new PhpNamespace($this->config->getBaseNamespace());
        $phpClass = $phpNamespace->addClass($realTableName);
        $phpNamespace->addUse(\EasySwoole\Spl\SplBean::class);
        $phpClass->addExtend(\EasySwoole\Spl\SplBean::class);
        $phpClass->addComment("{$this->config->getTableComment()}");
        $phpClass->addComment("Class {$realTableName}");
        $phpClass->addComment('Create With Automatic Generator');

        $this->addInitMethod($phpClass, $this->config->getTableColumns());
        foreach ($this->config->getTableColumns() as $column) {
            $name = $column['Field'];
            $comment = $column['Comment'];
            $columnType = $this->convertDbTypeToDocType($column['Type']);

            $property = $phpClass->addProperty($column['Field'])->setVisibility('protected');
            $property->addComment("@var {$columnType} {$name} | {$comment}");

            $this->addSetMethod($phpClass, $column['Field'], $columnType, $comment);
            $this->addGetMethod($phpClass, $column['Field'], $columnType, $comment);
        }
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

    function addInitMethod(ClassType $phpClass, $columns)
    {
        $method = $phpClass->addMethod("initialize");
        $method->setReturnType("void");
        $method->addComment("初始化方法");
        $methodBody = <<<Body
//add code here\n
Body;

        $methodBody .= <<<Body

parent::initialize();
Body;
        //配置方法内容
        $method->setBody($methodBody);
    }

    function addSetMethod(ClassType $phpClass, $column, $columnType, $comment)
    {
        $method = $phpClass->addMethod("set" . Str::studly($column));
        $method->setReturnType("void");
        $method->addComment("设置{$comment}");
        $method->addComment("@param {$columnType} {$column}");
        $method->addParameter($column);
        if($columnType == "json") {
            $methodBody = <<<Body
if(is_object(\$$column)|| is_array(\$$column)) {
    \$this->$column = json_encode(\$$column, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}
else{
    \$this->$column = \$$column;
}
        
Body;
        }
        else if(endWith($column, "_time") &&($columnType == "int")) {
            $methodBody = <<<Body
if(is_string(\$$column)){    
    \$this->$column = strtotime(\$$column);
} 
else{
    \$this->$column = \$$column;
}
Body;
        }
        else {
            $methodBody = <<<Body
\$this->$column = \$$column;
Body;
        }
        //配置方法内容
        $method->setBody($methodBody);
    }

    function addGetMethod(ClassType $phpClass, $column, $columnType, $comment)
    {
        $method = $phpClass->addMethod("get" . Str::studly($column));

        $method->setReturnType($columnType=="json"? '\stdclass': $columnType);
        $method->setReturnNullable(true);

        $method->addComment("获取{$comment}");
        $method->addComment("@return {$columnType}|null");

        if ($columnType == "json") {
            $methodBody = <<<Body
\$result = \$this->$column;
        if(empty(\$result)){
            return new \stdClass(); 
        }
        else if(is_string(\$result)) {
            \$result = json_decode(\$result);
        }
        return \$result;
Body;
        } else {
            $methodBody = <<<Body
return \$this->$column;
Body;
        }

        if(endWith($column, "_time") &&($columnType == "int")) {
            $this->addGetTimeMethod($phpClass, $column, $columnType, $comment);
        }

        //配置方法内容
        $method->setBody($methodBody);
    }

    function addGetTimeMethod(ClassType $phpClass, $column, $columnType, $comment)
    {
        $method = $phpClass->addMethod("get" . Str::studly($column). 'Str');
        $columnType = "string";

        $method->setReturnType('string');
        $method->setReturnNullable(true);

        $method->addComment("获取{$comment}字符串格式");
        $method->addComment("@return {$columnType}|null");

        $methodBody = <<<Body
return date('Y-m-d H:i:s', \$this->$column);
Body;

        //配置方法内容
        $method->setBody($methodBody);
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
        }  elseif (in_array($newFieldType, ['json'])) {
            $newFieldType = 'json';
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

        if ($this->config->isConfirmWrite()) {
            if (file_exists($fileName . '.php')) {
                echo "(Bean)当前路径已经存在文件,是否覆盖?(y/n)\n";
                if (trim(fgets(STDIN)) == 'n') {
                    echo "已结束运行\n";
                    return false;
                }
            }
        }
        $content = "<?php\n\n{$fileContent}\n";
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
