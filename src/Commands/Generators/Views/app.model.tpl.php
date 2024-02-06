<@php 
declare(strict_types=1);

namespace {class_namespace};

use App\Models\Boot\AppModel as Model;

final class {class} extends Model
{
    public $orderLimit   = 0; 
    public $orderOffset  = 0;

    protected $fields       = '{fields}';

    protected $order        = '{order}';
    protected $orderAsc     = {orderAsc};
    protected $orderByDate  = false;

    protected $relations    = null; // ['name'=>true] из Left, Right ['name'=>false]

    protected $table        = '{table}';

    /* Public functions */
    
}
