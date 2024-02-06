<?php namespace Sygecon\AdminBundle\Models\Layout; 

use App\Models\Boot\BuildModel as Model; 

class PageModel extends Model 
{ 
    protected $columnsFindAll=''; 
    protected $allowedFields=[]; 
    protected $order=''; 
    protected $orderAsc=false; 

    protected $escape = [];
}
