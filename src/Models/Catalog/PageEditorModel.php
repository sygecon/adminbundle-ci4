<?php namespace Sygecon\AdminBundle\Models\Catalog;

use App\Models\Boot\BaseModel as Model;

class PageEditorModel extends Model
{
    protected $table = 'pages';
    protected $allowedFields = ['language_id', 'title', 'h1', 'meta_title', 'meta_keywords', 'meta_description, updated_at'];
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    protected $useTimestamps = true;
    protected $returnType    = 'object';

}