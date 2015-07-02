<?php

namespace OpenWide\ServiceBundle\Controller;


class ServiceFolderViewController extends ViewController
{
    protected function renderLocation( $location, $viewType, $layout = false, array $params = array() )
    {
        switch( $viewType ) {
            case 'full' :
                $params += $this->getViewFullParamsFull($location,$viewType);
                break;
            case 'bloc' :
                $params += $this->getViewFullParamsBloc($location,$viewType);
                break;
        }

        return parent::renderLocation( $location, $viewType, $layout, $params );
    }

    protected function getViewFullParamsFull($location,$viewType)
    {
        /* @var $location eZ\Publish\Core\Repository\Values\Content\Location */
        $repository = $this->getRepository();
        $request = $this->getRequest();
        $contentService = $repository->getContentService();
        $content = $contentService->loadContentByContentInfo( $location->getContentInfo() );

        $params = array(
            'location' => $location,
            'content' => $content
        );
        
        $currentUser = $repository->getCurrentUser();
        $currentPage = $request->query->get('page', 1);

        $result = $this->container->get('open_wide_service.fetch_by_legacy')->getFolderChildrens(
                    $location, 
                    $currentUser,
                    $this->container->getParameter('open_wide_service.paginate.max_per_page'), 
                    $currentPage
            );        
     
        $params['items'] = $result['items'];
        $params['current_page'] = $result['current_page'];
        $params['nb_pages'] = $result['nb_pages'];
        $params['prev_page'] = $result['prev_page'];
        $params['next_page'] = $result['next_page'];
        $params['href_pagination'] = $result['base_href'];
        $params['options'] = $result['options'];
        $params['currentUserId'] = $currentUser->id;

        return $params;        
        
        
    }

    protected function getViewFullParamsBloc($location,$viewType)
    {
        /* @var $location eZ\Publish\Core\Repository\Values\Content\Location */
        $repository = $this->getRepository();
        $request = $this->getRequest();
        $contentService = $repository->getContentService();
        $content = $contentService->loadContentByContentInfo( $location->getContentInfo() );
        $currentUser = $repository->getCurrentUser();
        $params = array(
            'location' => $location,
            'content' => $content
        );

        $result = $this->container->get('open_wide_service.fetch_by_legacy')->getLinkForUser(
                    $location, 
                    $currentUser,
                    $this->container->getParameter('open_wide_service.root.max_per_block')
            );        
        
        $params['items'] = $result;

        return $params;        
    }

    /**
     * Returns value for $parameterName and fallbacks to $defaultValue if not defined
     *
     * @param string $parameterName
     * @param mixed $defaultValue
     *
     * @return mixed
     */
    public function getConfigParameter( $parameterName, $namespace = null, $scope = null ) {
        if( $this->getConfigResolver()->hasParameter( $parameterName, $namespace, $scope ) ) {
            return $this->getConfigResolver()->getParameter( $parameterName, $namespace, $scope );
        }
    }

    /**
     * Checks if $parameterName is defined
     *
     * @param string $parameterName
     *
     * @return boolean
     */
    public function hasConfigParameter( $parameterName, $namespace = null, $scope = null ) {
        return $this->getConfigResolver()->hasParameter( $parameterName, $namespace, $scope );
    }

    /**
     * Return the legacy content service
     *
     * return OpenWide\Bundle\ServiceBundle\Helper\FetchByLegacy
     */
    public function getLegacyContentService() {
        return $this->container->get( 'open_wide_service.helper' );
    }
}
