<?php

namespace SMG\Zip\Controller\Zip;

use Magento\Framework\App\Action\Action;
use \Magento\Widget\Model\Widget\Instance;
use \Magento\Cms\Model\Page;
use Magento\Framework\App\Action\Context;
use Magento\Cms\Api\PageRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;

class Index extends Action
{
    /**
     * @var PageRepositoryInterface
     */
    protected $_pageRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    protected $_searchCriteriaBuilder;

    /**
     * @param Context $context
     * @param PageRepositoryInterface $pageRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     */
    public function __construct(
        Context $context,
        PageRepositoryInterface $pageRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder
    ) {
        parent::__construct($context);

        $this->_pageRepository = $pageRepository;
        $this->_searchCriteriaBuilder = $searchCriteriaBuilder;
    }

    public function execute() {
        // set the redirect to no zip page
        $current_page_url = parse_url($this->_url->getUrl(), PHP_URL_SCHEME) . '://' . parse_url($this->_url->getUrl(), PHP_URL_HOST);
        $redirect = $current_page_url . '?no_zip_results';

		// get the grass type from the request params if it exists
        // otherwise get the zip code from the request params if it exists
		if(isset($_REQUEST['grass_type'])):
			$grass_type = $_REQUEST['grass_type'];

			$tbtwo_grass = array("Bahia", "Bermuda", "Bluegrass / Rye / Fescue", "Bluegrass (Kentucky Bluegrass)", "Fescue", "Ryegrass");
			$sobo_grass = array("Carpetgrass", "Centipede", "St. Augustine", "Zoysia");

			// set a page title based on the grass type selected
            $page_title = '';
			if(in_array($grass_type, $sobo_grass)):
				$page_title = '2 - SOBO';
			elseif(in_array($grass_type, $tbtwo_grass)):
				$page_title = '4 - TB2';
			endif;

			// Get the $redirect page url
			$pageUrl = $this->getRedirectPageUrl($page_title);
			if ($pageUrl):
			    $redirect = $pageUrl;
			endif;
		elseif(isset($_REQUEST['zip'])):
            $zip_code = preg_replace( '/[^0-9]/', '', $_REQUEST['zip']);
            $zip_code = substr($zip_code, 0, 5);

            // Get the widget title from the created widgets
            $page_title = $this->getPageTitle($zip_code);
			if($page_title == '3 - SOMIX'):
				$redirect = $current_page_url . '?select_grass&zip=' . $zip_code;
			else:
                // Get the redirect page url
                $pageUrl = $this->getRedirectPageUrl($page_title);

			    // if the pageUrl has been set then change the redirect URL to the pageURL
                // otherwise leave the redirect URL as the default
                if ($pageUrl):
                    $redirect = $pageUrl;
                endif;
			endif;
		endif;

		// put the redirect location in the header
		header("Location: " . $redirect);

		die();
	}

    /**
     * Retrieves data from the database and returns the results
     *
     * @param $query
     * @return mixed
     */
	private function getData($query)
    {
        // Injection wouldn't work in a controller for some reason kept getting "Type Error occurred when creating object:"
        // Used the Object Manager to get the connection
        $resourceConnection = $this->_objectManager->create('\Magento\Framework\App\ResourceConnection');

        // Get the database connection
        $readConnection = $resourceConnection->getConnection();

        // Get the results from the query
        $results = $readConnection->fetchAll($query);

        // Return the results
        return $results;
    }

    /**
     * Retrieves the page URL from the Page Helper.
     * If the pageUrl can not be found then it returns an empty string
     *
     * @param $page_title
     * @return mixed
     */
    private function getRedirectPageUrl($page_title)
    {
        $pageUrl = '';

        if($page_title):
            // Get the page id from the table
            $searchCriteria = $this->_searchCriteriaBuilder->addFilter('title', "%FAQ%", 'like')->create();
            $pages = $this->_pageRepository->getList($searchCriteria)->getItems();
            $pages = array_values($pages);

            if(isset($pages[0])):
                // Get the page Id from the query results
                $page_id = $pages[0]->getId();

                // Injection wouldn't work in a controller for some reason kept getting "Type Error occurred when creating object:"
                // Used the Object Manager to get the Page Helper
                $helper = $this->_objectManager->create('\Magento\Cms\Helper\Page');

                // Set the redirect to the page url that was found
                $pageUrl = $helper->getPageUrl($page_id);
            endif;
        endif;

        // Return the redirect URL
        return $pageUrl;
    }

    /**
     * Get the page title from the created zip code list widgets.
     * If the page title can not be found then it returns an empty string
     *
     * @param $zip_code
     * @return string
     */
    private function getPageTitle($zip_code)
    {
        $page_title = '';

        if ($zip_code):
            // get an instance of the widget model
            $widgetFactory = $this->_objectManager->create('\Magento\Widget\Model\Widget\Instance');

            // get a collection of widgets that have been collected and filter out the one we want
            $collection_of_widgets = $widgetFactory->getCollection();
            $collection_of_widgets->addFieldToFilter('widget_parameters', array(
                array('like'=> '%' . $zip_code . '%')
            ));

            // loop through the collection to get the zip code segment (aka page title)
            foreach($collection_of_widgets as $widget):
                $widget_params = $widget->getWidgetParameters();
                if(!isset($widget_params['zipcodesegment'])):
                    continue;
                endif;

                // if we made it here then we found the page title
                $page_title = $widget_params['zipcodesegment'];
            endforeach;
        endif;

        // return the page title
        return $page_title;
    }
}
