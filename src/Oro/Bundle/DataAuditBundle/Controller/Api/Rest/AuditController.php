<?php

namespace Oro\Bundle\DataAuditBundle\Controller\Api\Rest;

use Symfony\Component\HttpFoundation\Response;

use FOS\RestBundle\Routing\ClassResourceInterface;
use FOS\RestBundle\Controller\Annotations\NamePrefix;
use FOS\RestBundle\Controller\Annotations\QueryParam;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;

use Oro\Bundle\DataAuditBundle\Entity\Audit;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\SoapBundle\Entity\Manager\ApiEntityManager;
use Oro\Bundle\SoapBundle\Controller\Api\Rest\RestGetController;
use Oro\Bundle\SoapBundle\Request\Parameters\Filter\HttpDateTimeParameterFilter;
use Oro\Bundle\SoapBundle\Request\Parameters\Filter\IdentifierToReferenceFilter;
use Oro\Bundle\SoapBundle\Request\Parameters\Filter\HttpEntityNameParameterFilter;

/**
 * @NamePrefix("oro_api_")
 */
class AuditController extends RestGetController implements ClassResourceInterface
{
    /**
     * Get list of audit logs
     *
     * @QueryParam(
     *      name="page",
     *      requirements="\d+",
     *      nullable=true,
     *      description="Page number, starting from 1. Defaults to 1."
     * )
     * @QueryParam(
     *      name="limit",
     *      requirements="\d+",
     *      nullable=true,
     *      description="Number of items per page. defaults to 10."
     * )
     * @QueryParam(
     *     name="loggedAt",
     *     requirements="\d{4}(-\d{2}(-\d{2}([T ]\d{2}:\d{2}(:\d{2}(\.\d+)?)?(Z|([-+]\d{2}(:?\d{2})?))?)?)?)?",
     *     nullable=true,
     *     description="Date in RFC 3339 format. For example: 2009-11-05T13:15:30Z, 2008-07-01T22:35:17+08:00"
     * )
     * @QueryParam(
     *     name="action",
     *     requirements="create|update|remove",
     *     nullable=true,
     *     description="Logged action name"
     * )
     * @QueryParam(
     *     name="user",
     *     requirements="\d+",
     *     nullable=true,
     *     description="ID of User who has performed action"
     * )
     * @QueryParam(
     *     name="objectClass",
     *     requirements="\w+",
     *     nullable=true,
     *     description="Entity full class name; backslashes (\) should be replaced with underscore (_)."
     * )
     *
     * @ApiDoc(
     *  description="Get list of all logged entities",
     *  resource=true
     * )
     *
     * @AclAncestor("oro_dataaudit_history")
     */
    public function cgetAction()
    {
        $page             = (int)$this->getRequest()->get('page', 1);
        $limit = (int)$this->getRequest()->get('limit', self::ITEMS_PER_PAGE);
        $filterParameters = [
            'loggedAt'    => new HttpDateTimeParameterFilter(),
            'user'        => new IdentifierToReferenceFilter($this->getDoctrine(), 'OroUserBundle:User'),
            'objectClass' => new HttpEntityNameParameterFilter($this->get('oro_entity.routing_helper'))
        ];

        $criteria = $this->getFilterCriteria($this->getSupportedQueryParameters('cgetAction'), $filterParameters);

        return $this->handleGetListRequest($page, $limit, $criteria);
    }

    /**
     * Get page state
     *
     * @param int $id Page state id
     *
     * @return Response
     * @ApiDoc(
     *  description="Get audit entity",
     *  resource=true,
     *  requirements={
     *      {"name"="id", "dataType"="integer"},
     *  }
     * )
     *
     * @AclAncestor("oro_dataaudit_history")
     */
    public function getAction($id)
    {
        return $this->handleGetRequest($id);
    }

    /**
     * {@inheritdoc}
     */
    protected function getPreparedItem($entity, $resultFields = [])
    {
        /** @var Audit $entity */
        $result = parent::getPreparedItem($entity, $resultFields);

        // process relations
        $result['user'] = $entity->getUser() ? $entity->getUser()->getId() : null;

        // prevent BC breaks
        // @deprecated since 1.4.1
        $result['object_class'] = $result['objectClass'];
        $result['object_name']  = $result['objectName'];
        $result['username']     = $entity->getUser() ? $entity->getUser()->getUsername() : null;

        return $result;
    }

    /**
     * Get entity Manager
     *
     * @return ApiEntityManager
     */
    public function getManager()
    {
        return $this->get('oro_dataaudit.audit.manager.api');
    }
}
