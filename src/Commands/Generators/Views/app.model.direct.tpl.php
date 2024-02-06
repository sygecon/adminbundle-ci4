<@php 
declare(strict_types=1);

namespace {class_namespace}; 

use App\Models\Boot\BuildModel as Model; 

final class {class} extends Model 
{ 
    protected $columnsFindAll   = '{fields}'; 
    protected $allowedFields    = [{allowedFields}]; 
    protected $order            = '{order}'; 
    protected $orderAsc         = {orderAsc}; 

    protected $relations        = null; // ['name'=>true] из Left, Right ['name'=>false]

    protected $createdField     = 'created_at';
    protected $updatedField     = 'updated_at';
    protected $escape           = [];
    
    protected $table            = '{table}'; 
    protected $returnType       = 'array';

    /* Public functions */
    
}