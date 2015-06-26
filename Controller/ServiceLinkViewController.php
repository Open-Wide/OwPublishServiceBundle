<?php

namespace OpenWide\ServiceBundle\Controller;

class ServiceLinkViewController extends ViewController
{
    protected function renderLocation( $location, $viewType, $layout = false, array $params = array() )
    {
        switch( $viewType ) {
            case 'full' :
            case 'line' :
            case 'embed' :
                $params += $this->getViewFullParams($location);
                break;            
        }

        return parent::renderLocation( $location, $viewType, $layout, $params );
    }

    protected function getViewFullParams($location)
    {
        $repository = $this->getRepository();
        $contentService = $repository->getContentService();

        $content = $contentService->loadContentByContentInfo( $location->getContentInfo() );
        
        $params = array(
            'location' => $location,
            'content' => $content
        );

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
