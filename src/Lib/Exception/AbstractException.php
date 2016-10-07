<?php
namespace Satori\CatchException\Lib\Exception;

use Symfony\Component\HttpFoundation\Response;

/**
 * Class AbstractException
 *
 * @package Satori\CatchException\Lib\Exception
 */
abstract class AbstractException extends \Exception
{
    /** @var  Response */
    protected $response;

    /**
     * Set response
     *
     * @param Response $response
     */
    public function setResponse(Response $response)
    {
        $this->response = $response;
    }

    /**
     * Get response
     *
     * @return Response
     */
    public function getResponse()
    {
        return $this->response;
    }
}