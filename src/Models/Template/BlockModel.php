<?php 
namespace Sygecon\AdminBundle\Models\Template;

use App\Models\Boot\BaseModel as Model;

final class BlockModel extends Model
{
    protected $table = 'blocks';
    protected $columnsFindAll = 'id, type, name, title';
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

}