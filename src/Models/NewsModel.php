<?php
namespace App\Models\Layout;

use App\Models\Boot\AppModel as Model;

class NewsModel extends Model
{
    protected $table            = 'article';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $columnsFindAll   = '';
    protected $order            = '';
    protected $orderAsc         = false;
    protected $useSoftDeletes   = true;
    protected $createdField     = 'created_at';
    protected $updatedField     = 'updated_at';
}
