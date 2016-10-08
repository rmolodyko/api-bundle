<?php declare(strict_types=1);

namespace Satori\Api\Lib;

use Doctrine\ORM\EntityManager;
use Satori\Api\Lib\Configurator\EntityConfigurator;
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
     * @var EntityConfigurator
     */
    protected $configurator;

    /**
     * AbstractApiController constructor.
     *
     * @param RequestStack $requestStack
     * @param EntityConfigurator $entityConfigurator
     */
    public function __construct(RequestStack $requestStack, EntityConfigurator $entityConfigurator)
    {
        $this->requestStack = $requestStack;
        $this->configurator = $entityConfigurator;
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
        return $this->response($this->get('satori.api.serializer.serializer')->toArray($data, $groups));
    }

    /**
     * Create entity by de-serialized data
     *
     * @param array|Request $data
     * @param string $className
     * @return array Filling errors
     * @throws \LogicException
     * @throws \Doctrine\ORM\ORMInvalidArgumentException
     * @throws \Doctrine\Common\Persistence\Mapping\MappingException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \InvalidArgumentException
     * @throws \ReflectionException
     * @throws \Satori\CatchException\Lib\Exception\CatchResponseException
     */
    public function createEntity($data, string $className): array
    {
        return $this->getConfigurator()->create($this->handleData($data), $className);
    }

    /**
     * Save entity by de-serialized data
     *
     * @param array|Request $data
     * @param string $className
     * @return array Filling errors
     * @throws \LogicException
     * @throws \Doctrine\ORM\ORMInvalidArgumentException
     * @throws \Doctrine\Common\Persistence\Mapping\MappingException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \InvalidArgumentException
     * @throws \ReflectionException
     * @throws \Satori\CatchException\Lib\Exception\CatchResponseException
     */
    public function saveEntity($data, string $className): array
    {
        return $this->getConfigurator()->save($this->handleData($data), $className);
    }

    /**
     * Get de-serialized data
     *
     * @param array|Request $data
     * @return array
     * @throws \LogicException
     */
    protected function handleData($data): array
    {
        // Check if its request object then de-serialize and return the data
        if ($data instanceof Request) {
            return $this->getData($data, true);
        }
        return $data;
    }

    /**
     * Get entity configurator
     *
     * @return EntityConfigurator
     */
    public function getConfigurator(): EntityConfigurator
    {
        return $this->configurator;
    }

    /**
     * Get entity context
     *
     * @return EntityContext
     */
    protected function getEntityContext(): EntityContext
    {
        return $this->getConfigurator()->getEntityContext();
    }
}
