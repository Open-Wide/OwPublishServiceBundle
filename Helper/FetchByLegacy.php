<?php

namespace OpenWide\ServiceBundle\Helper;

use Symfony\Component\DependencyInjection\ContainerAware;
use eZ\Publish\API\Repository\Values\Content\Query;
use eZ\Publish\API\Repository\Values\Content\Query\Criterion;
use eZ\Publish\API\Repository\Values\Content\Query\SortClause;
use Pagerfanta\Adapter\ArrayAdapter;
use eZ\Publish\Core\Pagination\Pagerfanta\ContentSearchAdapter;
use Pagerfanta\Pagerfanta;

class FetchByLegacy extends ContainerAware {

    /**
     * @var \Closure
     */
    private $legacyKernelClosure;

    /**
     * @var array
     */
    private $criterion = array();

    /**
     * @var array
     */
    private $fetchParams = array();

    /**
     * @var string
     */
    private $fetchModule = 'content';

    /**
     * @var string
     */
    private $fetchFunction;

    /**
     * @var repository
     */
    protected $repository;

    /**
     * @var container
     */
    protected $container;
    private $ContentService;
    private $LocationService;
    private $SearchService;

    public function __construct($container) {
        $this->container = $container;
        $this->repository = $this->container->get('ezpublish.api.repository');
    }

    public function fetchContent($criterion) {
        $this->fetchModule = 'content';
        $this->fetchFunction = 'object';
        return $this->setCriterion($criterion)->performFetch();
    }

    public function fetchNode($criterion) {
        $this->fetchModule = 'content';
        $this->fetchFunction = 'node';
        return $this->setCriterion($criterion)->performFetch();
    }

    public function fetchNodeList($criterion) {
        $this->fetchModule = 'content';
        $this->fetchFunction = 'list';
        return $this->setCriterion($criterion)->performFetch();
    }

    public function countNodeList($criterion) {
        $this->fetchModule = 'content';
        $this->fetchFunction = 'list_count';
        return (int) $this->setCriterion($criterion)->performFetch();
    }

    public function fetchNodeTree($criterion) {
        $this->fetchModule = 'content';
        $this->fetchFunction = 'tree';
        return $this->setCriterion($criterion)->performFetch();
    }

    public function countNodeTree($criterion) {
        $this->fetchModule = 'content';
        $this->fetchFunction = 'tree_count';
        return (int) $this->setCriterion($criterion)->performFetch();
    }

    public function fetchObjectState($criterion) {
        if (isset($criterion['ObjectStateIdentifier'])) {
            list( $stateGroupIdentifier, $stateIdentifier ) = explode('/', $criterion['ObjectStateIdentifier']);
            return $this->getLegacyKernel()->runCallback(
                            function () use ( $stateGroupIdentifier, $stateIdentifier ) {
                        $objectStateGroup = \eZContentObjectStateGroup::fetchByIdentifier($stateGroupIdentifier);
                        return $state = $objectStateGroup->stateByIdentifier($stateIdentifier);
                    });
        }
        if (isset($criterion['ObjectStateId'])) {
            $stateId = $criterion['ObjectStateId'];
            return $this->getLegacyKernel()->runCallback(
                            function () use ( $stateId ) {
                        return \eZContentObjectState::fetchById($stateId);
                    });
        }
    }

    public function fetchMoreLikeThis($criterion) {
        $this->fetchModule = 'ezfind';
        $this->fetchFunction = 'moreLikeThis';
        return $this->setCriterion($criterion)->performFetch();
    }

    protected function performFetch() {
        $fetchModule = $this->fetchModule;
        $fetchFunction = $this->fetchFunction;
        $fetchParams = $this->fetchParams;
        return $this->getLegacyKernel()->runCallback(
                        function () use ( $fetchModule, $fetchFunction, $fetchParams ) {
                    return \eZFunctionHandler::execute($fetchModule, $fetchFunction, $fetchParams);
                });
    }

    protected function transformCriterionInFetchParams() {
        $this->fetchParams = array();
        foreach ($this->criterion as $paramName => $value) {
            $paramName = $this->fromCamelCaseToUnderscores($paramName);
            switch ($paramName) {
                case 'visibility':
                    if ($value == Criterion\Visibility::HIDDEN) {
                        $this->fetchParams['ignore_visibility'] = true;
                    }
                    break;
                case 'content_type_identifier':
                    if (!is_array($value)) {
                        $value = array($value);
                    }
                    $this->fetchParams['class_filter_array'] = $value;
                    break;
                case 'content_type_identifier_operator':
                    $this->fetchParams['class_filter_type'] = $value;
                    break;
                case 'object_state_id':
                    $this->fetchParams['attribute_filter'][] = array('state', "=", $value);
                    break;
                case 'object_state_identifier':
                    $objectState = $this->fetchObjectState(array('ObjectStateIdentifier' => $value));
                    if ($objectState) {
                        $this->fetchParams['attribute_filter'][] = array('state', "=", $objectState->attribute('id'));
                    }
                    break;
                default:
                    $this->fetchParams[$paramName] = $value;
                    break;
            }
        }
        if (isset($this->fetchParams['class_filter_array']) && !isset($this->fetchParams['class_filter_type'])) {
            $this->fetchParams['class_filter_type'] = 'include';
        }

        if (isset($this->fetchParams['parent_node_id']) && !isset($this->fetchParams['sort_by']) && ( $this->fetchFunction == 'list' || $this->fetchFunction == 'tree')) {
            $parentNodeId = $this->fetchParams['parent_node_id'];
            $this->fetchParams['sort_by'] = $this->getLegacyKernel()->runCallback(
                    function () use ( $parentNodeId ) {
                $parentNode = \eZContentObjectTreeNode::fetch($parentNodeId);
                return $parentNode->attribute('sort_array');
            });
        }
    }

    protected function getLegacyKernel() {
        if (!isset($this->legacyKernelClosure)) {
            $this->legacyKernelClosure = $this->container->get('ezpublish_legacy.kernel');
        }

        $legacyKernelClosure = $this->legacyKernelClosure;
        return $legacyKernelClosure();
    }

    /**
     * Return fetch criterion
     *
     * @return array
     */
    protected function getCriterion() {
        return $this->criterion;
    }

    /**
     * Set fetch criterion
     *
     * @param array $criterion
     * @return \Ow\Bundle\AgendaBundle\Helper\FetchByLegacy
     */
    protected function setCriterion($criterion) {
        $this->criterion = $criterion;
        $this->transformCriterionInFetchParams();
        return $this;
    }

    /**
     * Set fetch criterion
     *
     * @return \Ow\Bundle\AgendaBundle\Helper\FetchByLegacy
     */
    protected function removeCriterion() {
        $this->criterion = array();
        $this->transformCriterionInFetchParams();
        return $this;
    }

    /**
     * Add a critera in the $criterion
     *
     * @param string $type
     * @param mixed $value
     * @return \Ow\Bundle\AgendaBundle\Helper\FetchByLegacy
     */
    protected function addCriteria($type, $value) {
        $this->criterion[$type] = $value;
        $this->transformCriterionInFetchParams();
        return $this;
    }

    /**
     * @param $str
     * @return mixed
     */
    protected function fromCamelCaseToUnderscores($str) {
        $str[0] = strtolower($str[0]);
        $func = create_function('$c', 'return "_" . strtolower($c[1]);');
        return preg_replace_callback('/([A-Z])/', $func, $str);
    }

    /**
     * Return list of event sorted 
     * @param \eZ\Publish\Core\Repository\Values\Content\Location $location
     * @param type $maxPerPage
     * @param type $currentPage
     * @return type
     */
    public function getFolderChildrens(\eZ\Publish\Core\Repository\Values\Content\Location $location, $currentUser, $maxPerPage, $currentPage = 1) {

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

        $searchResult = $this->repository->getSearchService()->findContent($query);

        $content = array();
        foreach ($searchResult->searchHits as $serviceLink) {
            $content[] = array(
                'serviceLink' => $serviceLink->valueObject->contentInfo->mainLocationId,
            );
        }

        $result['offset'] = ($currentPage - 1) * $maxPerPage;
        $adapter = new ArrayAdapter($content);
        $pagerfanta = new Pagerfanta($adapter);

        $pagerfanta->setMaxPerPage($maxPerPage);
        $pagerfanta->setCurrentPage($currentPage);

        $result['offset'] = ($currentPage - 1) * $maxPerPage;
        $result['prev_page'] = $pagerfanta->hasPreviousPage() ? $pagerfanta->getPreviousPage() : 0;
        $result['next_page'] = $pagerfanta->hasNextPage() ? $pagerfanta->getNextPage() : 0;
        $result['nb_pages'] = $pagerfanta->getNbPages();
        $result['items'] = $pagerfanta->getCurrentPageResults();
        $result['base_href'] = "?";
        $result['current_page'] = $pagerfanta->getCurrentPage();
        return $result;
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

        $children = array();
        foreach ($searchResult->searchHits as $searchHit) {
            $children[]['serviceLink'] = $searchHit->valueObject;
        }

        return $children;
    }

    function getChildren($parentNodeId) {

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

}
