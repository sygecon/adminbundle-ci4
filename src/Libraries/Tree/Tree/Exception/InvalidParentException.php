<?php

namespace Sygecon\AdminBundle\Libraries\Tree\Tree\Exception;

/**
 * Exception which will be thrown if a tree node's parent ID points to an inexistent node.
 *
 * @author  Carsten Bluem <carsten@bluem.net>
 * @license http://www.opensource.org/licenses/mit-license.html MIT License
 */
class InvalidParentException extends \RuntimeException
{
}
