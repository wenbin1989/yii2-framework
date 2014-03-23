<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\db\cubrid;

use yii\db\Expression;
use yii\db\TableSchema;
use yii\db\ColumnSchema;

/**
 * Schema is the class for retrieving metadata from a CUBRID database (version 9.1.x and higher).
 *
 * @author Carsten Brandt <mail@cebe.cc>
 * @since 2.0
 */
class Schema extends \yii\db\Schema
{
    /**
     * @var array mapping from physical column types (keys) to abstract column types (values)
     * Please refer to [CUBRID manual](http://www.cubrid.org/manual/91/en/sql/datatype.html) for
     * details on data types.
     */
    public $typeMap = [
        // Numeric data types
        'short' => self::TYPE_SMALLINT,
        'smallint' => self::TYPE_SMALLINT,
        'int' => self::TYPE_INTEGER,
        'integer' => self::TYPE_INTEGER,
        'bigint' => self::TYPE_BIGINT,
        'numeric' => self::TYPE_DECIMAL,
        'decimal' => self::TYPE_DECIMAL,
        'float' => self::TYPE_FLOAT,
        'real' => self::TYPE_FLOAT,
        'double' => self::TYPE_FLOAT,
        'double precision' => self::TYPE_FLOAT,
        'monetary' => self::TYPE_MONEY,
        // Date/Time data types
        'date' => self::TYPE_DATE,
        'time' => self::TYPE_TIME,
        'timestamp' => self::TYPE_TIMESTAMP,
        'datetime' => self::TYPE_DATETIME,
        // String data types
        'char' => self::TYPE_STRING,
        'varchar' => self::TYPE_STRING,
        'char varying' => self::TYPE_STRING,
        'nchar' => self::TYPE_STRING,
        'nchar varying' => self::TYPE_STRING,
        'string' => self::TYPE_STRING,
        // BLOB/CLOB data types
        'blob' => self::TYPE_BINARY,
        'clob' => self::TYPE_BINARY,
        // Bit string data types
        'bit' => self::TYPE_STRING,
        'bit varying' => self::TYPE_STRING,
        // Collection data types (considered strings for now)
        'set' => self::TYPE_STRING,
        'multiset' => self::TYPE_STRING,
        'list' => self::TYPE_STRING,
        'sequence' => self::TYPE_STRING,
        'enum' => self::TYPE_STRING,
    ];

    /**
     * @inheritdoc
     */
    public function releaseSavepoint($name)
    {
        // does nothing as cubrid does not support this
    }

    /**
     * Quotes a table name for use in a query.
     * A simple table name has no schema prefix.
     * @param string $name table name
     * @return string the properly quoted table name
     */
    public function quoteSimpleTableName($name)
    {
        return strpos($name, '"') !== false ? $name : '"' . $name . '"';
    }

    /**
     * Quotes a column name for use in a query.
     * A simple column name has no prefix.
     * @param string $name column name
     * @return string the properly quoted column name
     */
    public function quoteSimpleColumnName($name)
    {
        return strpos($name, '"') !== false || $name === '*' ? $name : '"' . $name . '"';
    }

    /**
     * Quotes a string value for use in a query.
     * Note that if the parameter is not a string, it will be returned without change.
     * @param string $str string to be quoted
     * @return string the properly quoted string
     * @see http://www.php.net/manual/en/function.PDO-quote.php
     */
    public function quoteValue($str)
    {
        if (!is_string($str)) {
            return $str;
        }

        $this->db->open();
        // workaround for broken PDO::quote() implementation in CUBRID 9.1.0 http://jira.cubrid.org/browse/APIS-658
        $version = $this->db->pdo->getAttribute(\PDO::ATTR_CLIENT_VERSION);
        if (version_compare($version, '8.4.4.0002', '<') || $version[0] == '9' && version_compare($version, '9.2.0.0002', '<=')) {
            return "'" . addcslashes(str_replace("'", "''", $str), "\000\n\r\\\032") . "'";
        } else {
            return $this->db->pdo->quote($str);
        }
    }

    /**
     * Creates a query builder for the CUBRID database.
     * @return QueryBuilder query builder instance
     */
    public function createQueryBuilder()
    {
        return new QueryBuilder($this->db);
    }

    /**
     * Loads the metadata for the specified table.
     * @param string $name table name
     * @return TableSchema driver dependent table metadata. Null if the table does not exist.
     */
    protected function loadTableSchema($name)
    {
        $this->db->open();
        $tableInfo = $this->db->pdo->cubrid_schema(\PDO::CUBRID_SCH_TABLE, $name);

        if (!isset($tableInfo[0]['NAME'])) {
            return null;
        }

        $table = new TableSchema();
        $table->fullName = $table->name = $tableInfo[0]['NAME'];

        $sql = 'SHOW FULL COLUMNS FROM ' . $this->quoteSimpleTableName($table->name);
        $columns = $this->db->createCommand($sql)->queryAll();

        foreach ($columns as $info) {
            $column = $this->loadColumnSchema($info);
            $table->columns[$column->name] = $column;
        }

        $primaryKeys = $this->db->pdo->cubrid_schema(\PDO::CUBRID_SCH_PRIMARY_KEY, $table->name);
        foreach ($primaryKeys as $key) {
            $column = $table->columns[$key['ATTR_NAME']];
            $column->isPrimaryKey = true;
            $table->primaryKey[] = $column->name;
            if ($column->autoIncrement) {
                $table->sequenceName = '';
            }
        }

        $foreignKeys = $this->db->pdo->cubrid_schema(\PDO::CUBRID_SCH_IMPORTED_KEYS, $table->name);
        foreach ($foreignKeys as $key) {
            if (isset($table->foreignKeys[$key['FK_NAME']])) {
                $table->foreignKeys[$key['FK_NAME']][$key['FKCOLUMN_NAME']] = $key['PKCOLUMN_NAME'];
            } else {
                $table->foreignKeys[$key['FK_NAME']] = [
                    $key['PKTABLE_NAME'],
                    $key['FKCOLUMN_NAME'] => $key['PKCOLUMN_NAME']
                ];
            }
        }
        $table->foreignKeys = array_values($table->foreignKeys);

        return $table;
    }

    /**
     * Loads the column information into a [[ColumnSchema]] object.
     * @param array $info column information
     * @return ColumnSchema the column schema object
     */
    protected function loadColumnSchema($info)
    {
        $column = new ColumnSchema();

        $column->name = $info['Field'];
        $column->allowNull = $info['Null'] === 'YES';
        $column->isPrimaryKey = false; // primary key will be set by loadTableSchema() later
        $column->autoIncrement = stripos($info['Extra'], 'auto_increment') !== false;

        $column->dbType = strtolower($info['Type']);
        $column->unsigned = strpos($column->dbType, 'unsigned') !== false;

        $column->type = self::TYPE_STRING;
        if (preg_match('/^([\w ]+)(?:\(([^\)]+)\))?/', $column->dbType, $matches)) {
            $type = $matches[1];
            if (isset($this->typeMap[$type])) {
                $column->type = $this->typeMap[$type];
            }
            if (!empty($matches[2])) {
                if ($type === 'enum') {
                    $values = explode(',', $matches[2]);
                    foreach ($values as $i => $value) {
                        $values[$i] = trim($value, "'");
                    }
                    $column->enumValues = $values;
                } else {
                    $values = explode(',', $matches[2]);
                    $column->size = $column->precision = (int) $values[0];
                    if (isset($values[1])) {
                        $column->scale = (int) $values[1];
                    }
                }
            }
        }

        $column->phpType = $this->getColumnPhpType($column);

        if ($column->type === 'timestamp' && $info['Default'] === 'CURRENT_TIMESTAMP' ||
            $column->type === 'datetime' && $info['Default'] === 'SYS_DATETIME' ||
            $column->type === 'date' && $info['Default'] === 'SYS_DATE' ||
            $column->type === 'time' && $info['Default'] === 'SYS_TIME'
        ) {
            $column->defaultValue = new Expression($info['Default']);
        } else {
            $column->defaultValue = $column->typecast($info['Default']);
        }

        return $column;
    }

    /**
     * Returns all table names in the database.
     * @param string $schema the schema of the tables. Defaults to empty string, meaning the current or default schema.
     * @return array all table names in the database. The names have NO schema name prefix.
     */
    protected function findTableNames($schema = '')
    {
        $this->db->open();
        $tables = $this->db->pdo->cubrid_schema(\PDO::CUBRID_SCH_TABLE);
        $tableNames = [];
        foreach ($tables as $table) {
            // do not list system tables
            if ($table['TYPE'] != 0) {
                $tableNames[] = $table['NAME'];
            }
        }

        return $tableNames;
    }

    /**
     * Determines the PDO type for the given PHP data value.
     * @param mixed $data the data whose PDO type is to be determined
     * @return integer the PDO type
     * @see http://www.php.net/manual/en/pdo.constants.php
     */
    public function getPdoType($data)
    {
        static $typeMap = [
            // php type => PDO type
            'boolean' => \PDO::PARAM_INT, // PARAM_BOOL is not supported by CUBRID PDO
            'integer' => \PDO::PARAM_INT,
            'string' => \PDO::PARAM_STR,
            'resource' => \PDO::PARAM_LOB,
            'NULL' => \PDO::PARAM_NULL,
        ];
        $type = gettype($data);

        return isset($typeMap[$type]) ? $typeMap[$type] : \PDO::PARAM_STR;
    }
}
