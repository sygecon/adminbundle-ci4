<?php namespace Sygecon\AdminBundle\Models\Navigation;

use App\Models\Boot\BaseModel as Model;

class CustomPanelModel extends Model
{
    protected $table = 'navigation_bars';
    protected $columnsFindAll = 'id, name, title';
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
}