<?php

namespace OpenWide\Publish\ServiceBundle\Helper;

use Symfony\Component\DependencyInjection\ContainerAware;
use eZ\Publish\API\Repository\Values\Content\Query;
use eZ\Publish\API\Repository\Values\Content\Query\Criterion;
use eZ\Publish\API\Repository\Values\Content\Query\SortClause;
use Pagerfanta\Adapter\ArrayAdapter;
use Pagerfanta\Pagerfanta;
use ServiceSubscription;
use Monolog\Logger;

class FetchByLegacy extends ContainerAware {

    /**
     * @var \Closure
     */
    protected $legacyKernelClosure;

    /**
     * @var repository
     */
    protected $repository;

    /**
     * @var container
     */
    protected $container;
    protected $ContentService;
    protected $LocationService;
    protected $SearchService;
    protected $logger;

    public function __construct($container) {
        $this->container = $container;
        $this->repository = $this->container->get('ezpublish.api.repository');
        $this->logger = $this->container->get('logger');
    }

    protected function getLegacyKernel() {
        if (!isset($this->legacyKernelClosure)) {
            $this->legacyKernelClosure = $this->container->get('ezpublish_legacy.kernel');
        }

        $legacyKernelClosure = $this->legacyKernelClosure;
        return $legacyKernelClosure();
    }

    /**
     * Return list of children node
     * @param \eZ\Publish\Core\Repository\Values\Content\Location $location
     * @param type $maxPerPage
     * @param type $currentPage
     * @return type
     */
    public function getFolderChildrens(\eZ\Publish\Core\Repository\Values\Content\Location $location, $currentUser, $maxPerPage, $currentPage = 1, $category) {

        $criteria = array(
            new Criterion\ParentLocationId($location->id),
            new Criterion\ContentTypeIdentifier(array('service_link')),
            new Criterion\Visibility(Criterion\Visibility::VISIBLE),
        );

        if (isset($category) && $category!="all") {
            $criteria[] = new Criterion\Field('category', Criterion\Operator::CONTAINS, $category);
        }

        $query = new Query();
        $query->filter = new Criterion\LogicalAnd($criteria);
        $query->sortClauses = array(
            $this->sortClauseAuto($location)
        );

        $searchResult = $this->repository->getSearchService()->findContent($query);


        $subscritions = $this->fetchByUserId($currentUser->id);
        //$this->debug($subscritions);
        $content = array();
        $contentId = null;
        foreach ($searchResult->searchHits as $serviceLink) {

            if (!$contentId) {
                $contentId = $serviceLink->valueObject->getVersionInfo()->getContentInfo()->id;
            }

            $content[] = array(
                'serviceLink' => $serviceLink->valueObject->contentInfo->mainLocationId,
                'subscrition' => $this->hasSubscription($subscritions, $serviceLink->valueObject->getVersionInfo()->getContentInfo()->id)
            );
        }

        $result['offset'] = ($currentPage - 1) * $maxPerPage;
        $adapter = new ArrayAdapter($content);
        $pagerfanta = new Pagerfanta($adapter);

        $pagerfanta->setMaxPerPage($maxPerPage);
        $pagerfanta->setCurrentPage($currentPage);


        $httpReferer = $_SERVER['SCRIPT_URL'];

        if (isset($category)) {
            $httpReferer .= "?category=" . $category;
        } else {
            $httpReferer .= "?";
        }

        $result['offset'] = ($currentPage - 1) * $maxPerPage;
        $result['prev_page'] = $pagerfanta->hasPreviousPage() ? $pagerfanta->getPreviousPage() : 0;
        $result['next_page'] = $pagerfanta->hasNextPage() ? $pagerfanta->getNextPage() : 0;
        $result['nb_pages'] = $pagerfanta->getNbPages();
        $result['items'] = $pagerfanta->getCurrentPageResults();
        $result['base_href'] = $httpReferer;
        $result['current_page'] = $pagerfanta->getCurrentPage();
        $result['options'] = isset($contentId) ? $this->getCategorie($contentId, "ezselection") : array();

        return $result;
    }

    /**
     * Test si un user est abonné à un service
     * @param type $subscritions
     * @param type $servicelinkId
     * @return boolean
     */
    public function hasSubscription($subscritions, $servicelinkId) {
        foreach ($subscritions as $subscrition) {
            if ($subscrition['service_link_id'] == $servicelinkId) {
                return true;
            }
        }
        return false;
    }

    /**
     * Return list of event sorted 
     * @param \eZ\Publish\Core\Repository\Values\Content\Location $location
     * @param type $maxPerPage
     * @param type $currentPage
     * @return type
     */
    public function getLinkForUser(\eZ\Publish\Core\Repository\Values\Content\Location $location, $currentUser, $maxPerBlock = 9) {

        $criteria = array(
            new Criterion\ParentLocationId($location->id),
            new Criterion\ContentTypeIdentifier(array('service_link')),
            new Criterion\Visibility(Criterion\Visibility::VISIBLE),
        );
        $query = new Query();
        $query->filter = new Criterion\LogicalAnd($criteria);
        $query->sortClauses = array(
            $this->sortClauseAuto($location)
        );
        $query->limit = $maxPerBlock;

        $searchResult = $this->repository->getSearchService()->findContent($query);
        $subscritions = $this->fetchByUserId($currentUser->id);

        $children = array();
        foreach ($searchResult->searchHits as $searchHit) {
            if ($this->hasSubscription($subscritions, $searchHit->valueObject->getVersionInfo()->getContentInfo()->id)) {
                $children[]['serviceLink'] = $searchHit->valueObject;
            }
        }

        return $children;
    }

    public function getChildren($parentNodeId) {

        $criteria = array(
            new Criterion\ParentLocationId($parentNodeId->valueObject->contentInfo->mainLocationId),
            new Criterion\ContentTypeIdentifier(array('event_date')),
            new Criterion\Visibility(Criterion\Visibility::VISIBLE),
        );

        $query = new Query();
        $query->filter = new Criterion\LogicalAnd($criteria);

        $searchResult = $this->repository->getSearchService()->findContent($query);

        return $searchResult;
    }

    /**
     * Renvoie le tri paramétré dans un node
     * @param \eZ\Publish\Core\Repository\Values\Content\Location $location
     * @return \eZ\Publish\API\Repository\Values\Content\Query\SortClause\SectionName|\eZ\Publish\API\Repository\Values\Content\Query\SortClause\LocationDepth|\eZ\Publish\API\Repository\Values\Content\Query\SortClause\DateModified|\eZ\Publish\API\Repository\Values\Content\Query\SortClause\LocationPriority|\eZ\Publish\API\Repository\Values\Content\Query\SortClause\LocationPathString|\eZ\Publish\API\Repository\Values\Content\Query\SortClause\ContentName|\eZ\Publish\API\Repository\Values\Content\Query\SortClause\ContentId|\eZ\Publish\API\Repository\Values\Content\Query\SortClause\DatePublished
     */
    public function sortClauseAuto(\eZ\Publish\Core\Repository\Values\Content\Location $location) {
        $sortField = $location->sortField;
        $sortOrder = $location->sortOrder == 1 ? Query::SORT_ASC : Query::SORT_DESC;
        switch ($sortField) {

            case 1 : // Fil d'Ariane
                return new SortClause\LocationPathString($sortOrder);

            case 2 : // Date de création
                return new SortClause\DatePublished($sortOrder);

            case 3 : // Date de modification
                return new SortClause\DateModified($sortOrder);

            case 4 : // Section
                return new SortClause\SectionName($sortOrder);

            case 5 : // Profondeur
                return new SortClause\LocationDepth($sortOrder);

            case 6 : // Identifiant
                return new SortClause\ContentId($sortOrder);

            case 7 : // Nom
                return new SortClause\ContentName($sortOrder);

            case 8 : // Priorité
                return new SortClause\LocationPriority($sortOrder);

            case 9 : // Nom du node
                return new SortClause\ContentName($sortOrder);

            default :
                return new SortClause\LocationPriority($sortOrder);
        }
    }

    /**
     * Renvoie l'objet de l'image correspondant au contentId
     * @param type $contentId
     * @return type
     */
    public function getImageByContentId($contentId) {
        $contentImage = null;
        if ($contentId) {
            $image_info = $this->loadService('Content')->loadContentInfo($contentId);
            $contentImage = $this->loadService('Content')->loadContentByContentInfo($image_info);
        }
        return $contentImage;
    }

    public function loadService($service) {
        $attribut = $service . 'Service';
        $function = 'get' . $attribut;
        if (!$this->{$attribut}) {
            $this->{$attribut} = call_user_func(array($this->repository, $function));
        }
        return $this->{$attribut};
    }

    public function fetchByUserId($userId, $asObject = false) {
        return $this->getLegacyKernel()->runCallback(
                        function () use ( $userId) {
                    return ServiceSubscription::fetchByUserId($userId, $asObject);
                }
        );
    }

    /**
     * Renvoie l'abonnement correspondant au user et service
     * @param type $userId
     * @param type $serviceLinkId
     * @param type $callback
     * @param type $asObject
     * @return type
     */
    public function fetchByUserAndServiceLink($userId, $serviceLinkId, $callback = true, $asObject = false) {

        if ($callback) {
            return $this->getLegacyKernel()->runCallback(
                            function () use ( $userId, $serviceLinkId, $asObject) {
                        $result = ServiceSubscription::fetchByUserAndServiceLink($userId, $serviceLinkId, $asObject);
                        return $result;
                    }
            );
        } else {
            return ServiceSubscription::fetchByUserAndServiceLink($userId, $serviceLinkId, $asObject);
        }
    }

    /**
     * Ajouter un abonnement pour un utilisateur
     * @param type $userId
     * @param type $serviceLinkId
     * @param type $callback
     * @return type
     */
    public function addServiceLink($userId, $serviceLinkId, $callback = true) {

        try {
            $params = array('user_id' => $userId, 'service_link_id' => $serviceLinkId);

            if ($callback) {
                $serviceSubscription = $this->getLegacyKernel()->runCallback(
                        function () use ( $params ) {
                    return ServiceSubscription::create($params);
                }
                );
            } else {
                $serviceSubscription = ServiceSubscription::create($params);
            }
            $serviceSubscription->store();
            return $serviceSubscription;
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    /**
     * 
     * @param type $contentId
     * @param type $identifier
     * @return array
     * Array
     *   (
     *   [isMultiple] => 1
     *   [options] => Array
     *   (
     *       [0] => Catégorie 1
     *       [1] => Catégorie 2 
     *   )
     *   )
     */
    public function getCategorie($contentId, $identifier) {

        $contentTypeService = $this->repository->getContentTypeService();
        $serviceInfo = $this->loadService('Content')->loadContentInfo($contentId);

        /* @var $contentImage \eZ\Publish\Core\Repository\Values\ContentType\FieldDefinition */
        $fieldDefinitionCategorie = $contentTypeService->loadContentType($serviceInfo->contentTypeId)->getFieldDefinition("category");
        $fieldSettings = $fieldDefinitionCategorie->getFieldSettings();
        return $fieldSettings['options'];
    }

    public function getServiceByCode($code) {

        $criteria = array(
            new Criterion\ContentTypeIdentifier(array('service_link')),
            new Criterion\Visibility(Criterion\Visibility::VISIBLE),
            new Criterion\Field('code', Criterion\Operator::EQ, $code)
        );

        $query = new Query();
        $query->filter = new Criterion\LogicalAnd($criteria);
        $searchResult = $this->repository->getSearchService()->findContent($query);

        if (isset($searchResult->searchHits)) {
            return $searchResult->searchHits;
        } else {
            return array();
        }
    }

    public function debug($var) {
        print "<pre>" . print_r($var, true) . "</pre>";
        exit();
    }

}
