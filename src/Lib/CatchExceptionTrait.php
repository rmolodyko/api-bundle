<?php
/**
 * Created by PhpStorm.
 * User: molodyko
 * Date: 16.08.2016
 * Time: 12:46
 */

namespace Satori\CatchException\Lib;

use Satori\CatchException\Lib\Exception\CatchResponseException;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Trait CatchExceptionTrait
 *
 * @package Satori\CatchException\Lib
 */
trait CatchExceptionTrait
{
    /**
     * Create and send error message immediately
     *
     * @param bool $condition Condition which indicates that error was happened
     * @param string $error Error text
     * @param int $status Status code for response
     * @param array $opt
     * @throws CatchResponseException
     */
    public function error($condition, $error, $status = 500, array $opt = [])
    {
        // When condition is true send error
        if ($condition) {

            // Init options
            $opt['field'] = array_key_exists('field', $opt) ? $opt['field'] : 'error';

            // Create bundle exception which indicates that such error will send custom response
            $exception = new CatchResponseException();

            // Set response which will be send
            $exception->setResponse(new JsonResponse([
                'status' => 0,
                $opt['field'] => $error
                // Set status code for response
            ], $status, [
                'X-Status-Code' => $status
            ]));

            // Throw exception
            throw $exception;
        }
    }
}