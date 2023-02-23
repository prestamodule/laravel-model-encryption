<?php
namespace Magros\Encryptable;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Query\Builder;

class EncryptableQueryBuilder extends Builder {

    private $model;

    /**
     * EncryptableQueryBuilder constructor.
     * @param ConnectionInterface $connection
     * @param Encryptable $model
     */
    public function __construct(ConnectionInterface $connection, $model)
    {
        parent::__construct($connection, $connection->getQueryGrammar(), $connection->getPostProcessor());
        $this->model = $model;
    }

    /**
     * @param array|\Closure|string $column
     * @param null $operator
     * @param null $value
     * @param string $boolean
     * @return Builder
     * @throws \Exception
     */
    public function where($column, $operator = null, $value = null, $boolean = 'and')
    {
        if (method_exists($this->model, 'encryptable')) {
            // Handle associative array, such as when we use updateOrCreate, in which case column contains the first arg
            if (!is_array($column)) {
                if ($this->model->encryptable($column)) {
                    list($value, $operator) = $this->prepareValueAndOperator($value, $operator, func_num_args() === 2);
                    $value = $this->model->encryptAttribute($value);
                }
            } else {
                // Build a list of columns we have to encrypt for the where to work properly
                $columnsToEncrypt = array_intersect(array_keys($column), $this->model->encryptable);
                // Once we have that list, we can apply the same behavior than for the "classic" case above
                foreach ($columnsToEncrypt as $columnName) {
                    list($newValue, $operator) = $this->prepareValueAndOperator($column[$columnName], $operator, func_num_args() === 2);
                    $newValue = $this->model->encryptAttribute($newValue);
                    // Replace the original column value with the encrypted one
                    $column[$columnName] = $newValue;
                }
            }
        }

        return parent::where($column, $operator, $value, $boolean);

    }
}
