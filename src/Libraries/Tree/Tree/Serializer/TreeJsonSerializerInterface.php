<?php

namespace Sygecon\AdminBundle\Libraries\Tree\Tree\Serializer;

use Sygecon\AdminBundle\Libraries\Tree\Tree;

/**
 * Interface for classes which offer tree serialization.
 *
 * @author Carsten Bluem <carsten@bluem.net>
 * @license http://www.opensource.org/licenses/mit-license.html MIT License
 */
interface TreeJsonSerializerInterface
{
    /**
     * Returns a representation of the tree which is natively encodable in JSON using json_encode().
     *
     * @return mixed
     */
    public function serialize(Tree $tree);
}
