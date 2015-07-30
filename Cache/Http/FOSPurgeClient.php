<?php
/**
 * File containing the FOSClient class.
 *
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 * @version //autogentag//
 */

namespace OpenWide\Publish\ServiceBundle\Cache\Http;

use eZ\Publish\Core\MVC\Symfony\Cache\PurgeClientInterface;
use FOS\HttpCacheBundle\CacheManager;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

/**
 * Purge client based on FOSHttpCacheBundle.
 *
 * Only support BAN requests on purpose, to be able to invalidate cache for a
 * collection of Location/Content objects.
 */
class FOSPurgeClient implements PurgeClientInterface
{
    /**
     * @var CacheManager
     */
    private $cacheManager;

    public function __construct( CacheManager $cacheManager )
    {
        $this->cacheManager = $cacheManager;
    }

    public function purge( $locationIds )
    {
        if ( empty( $locationIds ) )
        {
            return;
        }

        if ( !is_array( $locationIds ) )
        {
            $locationIds = array( $locationIds );
        }

        $this->cacheManager->invalidate( array( 'X-Location-Id' => '(' . implode( '|', $locationIds ) . ')' ) );

        /*$monolog = new Logger('purgecache');
        $monolog->pushHandler(new StreamHandler('purgecache.log', Logger::NOTICE));
        $monolog->addNotice('Invalidation : ' . implode( '|', $locationIds ));*/
    }

    public function purgeAll()
    {
        $this->cacheManager->invalidate( array( 'X-Location-Id' => '.*' ) );
    }
}
