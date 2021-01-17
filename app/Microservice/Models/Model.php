<?php


namespace App\Microservice\Models;


use Illuminate\Database\Eloquent\Model as EloquentModel;
use PDO;

class Model extends EloquentModel
{
    protected $primaryKey = 'id';
    public $incrementing = true;
    protected $keyType = 'int';

    public $timestamps = false;
    public $customTimestamps = true;
    const CREATED_AT = 'createdAt';
    const UPDATED_AT = false;
    protected $dateFormat = 'U';

    protected $guarded = [];

    protected $hidden = [];

    public static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if($model->customTimestamps){
                $model->setCreatedAt($model->freshTimestamp()->format($model->getDateFormat()));
            }
        });
    }

    /**
     * Get model table info
     *
     * @return false|mixed
     */
    public function getTableInfo(){
        $info = $this->getConnection()
            ->getPdo()
            ->query('show table status like "'.$this->getTable().'"')
            ->fetchAll(PDO::FETCH_ASSOC);

        if(empty($info)) return false;

        return array_shift($info);
    }


    /**
     * Get AutoIncrement for model table
     *
     * @return false|mixed
     */
    public function getAutoIncrement(){
        $info = $this->getTableInfo();
        if(isset($info['Auto_increment'])){
            return $info['Auto_increment'];
        }

        return false;
    }
}
