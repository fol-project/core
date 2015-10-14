<?php
namespace Fol;

use Exception;
use Interop\Container\Exception\NotFoundException;

/**
 * Exception throwed by the container when the item is not found
 */
class ContainerNotFoundException extends Exception implements NotFoundException
{
}
