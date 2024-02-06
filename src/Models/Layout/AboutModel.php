<?php 
declare(strict_types=1);

namespace Sygecon\AdminBundle\Models\Layout; 

use App\Models\Boot\BuildModel as Model; 

final class AboutModel extends Model 
{ 
    protected $table            = 'model_about'; 
    protected $columnsFindAll   =''; 
    protected $allowedFields    =[]; 
    protected $order            = ''; 
    protected $orderAsc         = false; 
    protected $relations        = ['service_specialists' => true];
    
    protected $escape           = [];
    
    /* Public functions */
    
}
