<?php

namespace ToolboxBundle\Document\Areabrick\Download;

use Pimcore\Db\ZendCompatibility\QueryBuilder;
use ToolboxBundle\Connector\BundleConnector;
use ToolboxBundle\Document\Areabrick\AbstractAreabrick;
use Pimcore\Model\Document\Tag\Area\Info;

use Pimcore\Model\Asset;

class Download extends AbstractAreabrick
{
    /**
     * @var BundleConnector
     */
    protected $bundleConnector;

    /**
     * Download constructor.
     *
     * @param BundleConnector $bundleConnector
     */
    public function __construct(BundleConnector $bundleConnector)
    {
        $this->bundleConnector = $bundleConnector;
    }

    /**
     * @param Info $info
     */
    public function action(Info $info)
    {
        parent::action($info);

        $view = $info->getView();

        //check if member extension exist
        $hasMembers = $this->bundleConnector->hasBundle('MembersBundle\MembersBundle');
        $downloadField = $this->getDocumentTag($info->getDocument(),'multihref', 'downloads');

        $assets = [];
        if (!$downloadField->isEmpty()) {

            /** @var \Pimcore\Model\Asset $node */
            foreach ($downloadField->getElements() as $node) {

                //it's a folder. get all sub assets
                if($node instanceof Asset\Folder) {

                    $assetListing = new Asset\Listing();
                    $fullPath = rtrim($node->getFullPath(), '/') . '/';
                    $assetListing->addConditionParam('path LIKE ?', $fullPath . '%');

                    if($hasMembers) {
                        $assetListing->onCreateQuery(function (QueryBuilder $query) use ($assetListing) {
                            $this->bundleConnector->getBundleService('members.security.restriction.query')
                                ->addRestrictionInjection($query, $assetListing, 'assets.id');
                        });
                    }

                    /** @var Asset $entry */
                    foreach ($assetListing->getAssets() as $entry) {
                        if (!$entry instanceof Asset\Folder) {
                            $assets[] = $entry;
                        }
                    }

                //default asset
                } else {

                    if($hasMembers) {
                        $assetRestriction = $this->bundleConnector->getBundleService('members.manager.restriction')->getElementRestrictionStatus($node);
                        if($assetRestriction['section'] === \MembersBundle\Manager\RestrictionManager::RESTRICTION_SECTION_ALLOWED) {
                            $assets[] = $node;
                        }
                    } else {
                        $assets[] = $node;
                    }
                }
            }
        }

        $view->downloads = $assets;

    }

    public function getName()
    {
        return 'Downloads';
    }

    public function getDescription()
    {
        return 'Toolbox Downloads';
    }
}