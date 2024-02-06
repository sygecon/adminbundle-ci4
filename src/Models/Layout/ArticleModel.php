<?php 
declare(strict_types=1);

namespace Sygecon\AdminBundle\Models\Layout; 

use App\Models\Boot\BuildModel as Model; 

final class ArticleModel extends Model 
{ 
    protected $table            = 'model_article'; 
    protected $columnsFindAll   =''; 
    protected $allowedFields    =[]; 
    protected $order            = ''; 
    protected $orderAsc         = false; 

    protected $escape           = [];
    
    /* Public functions */
    
}
