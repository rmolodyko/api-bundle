<?php declare(strict_types=1);

namespace Satori\Api\Lib;

use Doctrine\ORM\EntityManager;
use Satori\CatchException\Lib\CatchExceptionTrait;
use Satori\CatchException\Lib\Exception\CatchResponseException;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class AbstractApiController
 *
 * @author Ruslan Molodyko <molodyko@samsonos.com>
 */
abstract class AbstractApiController extends Controller
{
    use CatchExceptionTrait;

    /**
     * @var RequestStack
     */
    protected $requestStack;

    /**
     * AbstractApiController constructor.
     *
     * @param RequestStack $requestStack
     */
    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    /**
     * Get entity manager
     * @return EntityManager
     * @throws \LogicException
     */
    public function getManager()
    {
        return $this->getDoctrine()->getManager();
    }

    /**
     * @return null|Request
     */
    public function getRequest()
    {
        return $this->requestStack->getCurrentRequest();
    }

    /**
     * Get post data
     *
     * @param Request $request
     * @param bool $asArray
     * @return mixed
     * @throws \LogicException
     */
    public function getData(Request $request, $asArray = false)
    {
        return json_decode($request->getContent(), $asArray);
    }

    /**
     * Return response
     *
     * @param $response
     * @return JsonResponse
     * @throws CatchResponseException
     */
    public function response($response)
    {
        return new JsonResponse($response, 200);
    }

    /**
     * Response entities data
     *
     * @param $data
     * @param array $groups
     * @return JsonResponse
     * @throws \InvalidArgumentException
     * @throws CatchResponseException
     */
    public function responseEntity($data, array $groups)
    {
        return $this->response($this->get('satori.api.serializer')->toArray($data, $groups));
    }
}
