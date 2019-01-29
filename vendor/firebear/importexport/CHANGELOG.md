1.5.0
=============
* Fixed bugs:
    * Fixed an issue where strategy validation did not work with value "skip on entries"
* Restructured code for form of Import Jobs:
    * Form at style of Accordeon
* Add features:
    * Add inline edit for field Cron in Grid
    * Add validate of file after entered data for file
* Add Export Jobs:
    * Add grid
    * Add form
    * Add commands
    * Add crontab
    
2.0.0
=============
- general refactoring
- add export jobs similar to import jobs with mapping
- refactoring and improvements for import mapping
- hardcoded values / default values on mapping export 
- Magento 1 pre set for import jobs
- export orders jobs with mapping and filters
- add file validation on import job
- advanced pricing import performance issue
- filtering for export for all entities by attributes
- interaction for default values when should be unique , x1, x2 etc. 
- default / hardcoded value suffix & prefix 
- detailed logging 
- sample files included to extension pack & download from manual 
- unzip / untar file before import 
- upload CSV directly to import job on import form (in web browser)

2.1.0
==============
* Import and Export Fixed Product Tax
* Fix bugs:
   - Hardvalue for field of Entity
   - Load page add Job in Export
   - Import and Export in Admin
   - Correct values for fields of bundle product
   - Check Area Code in Console
   - Delete element in Map
   - Off Logs to Console via Admin
* Add rules for new variations of Configurables Products
* Support Language in console
* Support Language in Cron
* Add Behaviour "Only Update" for Entity of Products
* Add fields for Export's Filter: product_type, attribute_set_code
* Unset special price
* Run re-index command line automatically after import processed
* Import category by Ids and Path of Ids instead of category name
* Generate unique url
* Divide Additional Attributes

2.1.1
==============
* Add Mapping of Categories
* Export Categories
* Load images via HTTP Auth
* Fix bugs:
   - Cannot set zero value for Default Column in Map Attributes table of Import Job
   - Column of Mapping is Empty after load
   - Cannot change Attribute Set
   - Cannot load file via url
   - Cannot minify js files
   - Cannot load image for Configurable product
   - Cannot open page of Export job fast
   - Cannot export bundles and grouped products
   - Cannot add some identicaly fields in mapping
   - Cannot load page of Export job fastly
   - Cannot category url if duplicate
   - Cannot update price and qty
   - Cannot set value for some attributes im mapping
   

2.1.2
==============
* Add presets of Shopify  
* Add price rules feature:
    - Change import price according to price rules
    - Set fixed or percent price margin
* Add Import of Coupons
* Add Import of Cms Pages
* Fix bugs:
   - Cannot create value of attribute from configurable_variations
   - Cannot set values for different stores
   - Cannot import product from Magento1 if empty lines

2.1.3
==============
* Fix core bug if create bundle item
* Fix bugs:
   - Cannot add category in Price rules
   - Duplicates Attributes in Price rules
   - Cannot create categories of different levels
   - Cannot correct import CMS Pages
   - Cannot change delimiter for Categories
   - Cannot create some values of attribute for type of multiselect
   - Cannot changes values of attributes of Categories if different stores

2.1.4
==============
* Fix bugs:
   - Cannot change mapping categories
   - Cannot add new values for attribute
   
2.1.5
==============
* Fix bugs:
   - Cannot import conditions for CartPriceRules
   - Cannot add categories in mapping
   - Cannot add Tier prices
   - Cannot change name of Category for different stores
   - Change CSV for Export Orders
   
2.1.6
==============
Add XSLT For Xml for Import

2.1.7
==============
Add XSLT For Xml for Export

2.1.8
==============
Add Custom field for mapping
Add reset mapping


2.2.0
==============
Add Import of Orders
Add import JSON

2.2.1
==============
* Fix bugs:
   - Cannot load additional images

2.2.2
==============
* Add buttons "Duplicate"

2.2.3
==============
* Fix bugs:
   - Export CMS Blocks and Pages
* Add REST API
* Order export new file format
* Add support ods, xlsx and xls

3.0.0
==============
* Added support of Excel XLSX file format
* Added support of OpenOffice ODS file format
* Added support of REST API  – XML files with XSLT templates and custom Json files
* Added support of SOAP API – XML files with XSLT templates and custom Json files
* Added improved Json file compatibility
* Added new entity Product Attributes – now all attributes, attribute sets and groups can be imported to Magento 2
* Added consecutive export procedure – the export jobs can now remember already exported entities and export only NEW entities added since the last run
* Export date and time can now be added automatically to the file name
* All files from the specified folder can now be imported in a single job
* Swatch attribute values, both color and image, can now be imported along with products
* Default product variations of Improved Configurable Product extension can now be imported
* Added compatibility with the following third-party extensions:
    - MageWorx Advanced Product Options
    - MageStore Inventory Management
    - Wyomind Advanced Inventory
    - MageDelight Price per Customer
        
3.0.1
==============
* Improvements:
    * Added support for split db solution
* Fixed bugs:
   - Url key duplicate issue when the file contains several products with the same url_key
   - Undefined index issue during update not existing bundle product
* Add presets of Magmi

3.1.0
==============
* Features:
    * Map Attributes – Apply Default Values to – decide if default value should be applied to empty or all rows
    * Attribute value mapping – decide which exact attribute values you want to update, paste them and the new value
    * Root Category – select root category to reference category paths in the import file
    * Round prices and special prices – automatically adjust prices to .99 or .49 whichever is closer
    * Export job event system – whenever the Magento 2 event happens the job is automatically executed
    * Attribute set update – an additional product attribute which defines if the existing product’s attribute set should or should not be updated
    * Configurable product custom logic – copy simple product attributes for configurable – now you can copy selected attribute values of the simple products to configurable product
    * Not remove current mappings for using same mapping for different file upload. 
* Bugfixes:
    * Simple custom options are not imported properly
    * Issue with ‘Category Levels separated by’ setting
    * additional_attributes attribute missing in the attribute mapping column
    * Issue with Only Update behavior importing stock values
    * Issue with text swatch attribute displaying as a dropdown
    * Fixed Product Tax issue with updating existing products
    * Job page loading speed improved
    * Imported configurable products are no longer automatically put in stock after import
    * Configurable products are no longer created if there are no variations or a single variation
    * Issue with importing products with the same URL key creating multiple products
    * Updated links to the sample files inside import jobs
    * Issue with product export missing bundle and downloadable attributes
    * Issue with downloadable product links not being updated on import
    * Issue with filter conditions
    * Issue with checking for existing SKUs in the database
    * Issue with customer composite entity type import
    * Issue with exporting products with ‘Divide Additional Attribute’s option enabled
    * Url key duplicate issue when the file contains several products with the same url_key
    * Undefined index issue during update not existing bundle product
    * The issue with EntityLinkField during product update 
    * Product attributes replace import issue 
    * Link to download sample files in import job issue
    * Price Rules tab issues. When a customer selects a category condition or an attribute which have options

3.1.1
==============
* Features:
    * Changed the default display of section 'Store filter'
* Bugfixes:
    * The issue when importing orders with empty country_id
    * Catalog product links import issue. When a file contains several rows for the same product
    * Bundle option products import issue. When a file contains several rows for the same product
    * Custom option products import issue. When a file contains one rows with a not null store_view_code value
    * Fix for configurable product when the configurable product is already created and the visibility of newly added simple products are not update.
    * Magento 2.3 compatibility issue(Import Products)
    * Product custom options import issue
    * Product custom options import issue
    * Importing products issue when the php version below 7.1
    * Issue with importing a product that contains an multi-select attribute
    * Incorrect display of fields in 'Map attributes' block
    * The issue with address duplicate during customer address import
    * The issue with default address attributes during customer address import

3.1.2
==============
* Features:
    * Magento 2.3 support added.
* Bugfixes:
    * Fix issue when importing empty attributes 'available_sort_by' and 'default_sort_by' (magento 2.3).
    * Fix issue with duplicated options (magento 2.3).
    * Fix issue when importing orders with empty country_id (magento 2.3).
    * Fix problem with "Clear Attribute Values" option (magento 2.3).
    * Fix import of customers and addresses (magento 2.3).
    * Fix error when replacing products: Invalid value for Attribute Set column (set doesn't exist?) (magento 2.3).
    * Fix error when simple products are not attached to configurable (magento 2.3).
    * Fix compilation error: Incompatible argument type. Magento compiler allow only one parent::__construct() calls.

3.1.3
==============
* Bugfixes:
    * Issue with cron expression is not set
    * Issue when the row does not contain complete information about custom options
    * Custom columns were added to the System Attribute drop-down in ‘Map attributes’ block
    * Remove extra whitespaces from xml import form definition
    * Issue with bundle product attributes: price_type, sku_type, weight_type
    * Added validation of the field “custom_layout_update”
    
3.1.4
==============
* Features:
    * Remove existing categories from imported products and assign only the categories from the imported file
    * Remove existing store views from imported products and assign only the store views from the imported file
    * Import product categories by IDs with categories_id attribute (categories should already exist at the store)
* Bugfixes:
    * Added support of increment_id for importing customer addresses, which gives the ability to update existing addresses
    * Added support for query type image URLs
    * Added UrlKey Manager to check existing product URLs
    * Removed extra whitespaces from REST api for JSON options
    * Issue with Magento 2.2.7 History Model defined as private in parent class
    * Issue when the row does not contain complete information about product custom options
    * Issue with mapping same attribute with different system attributes
    * Issue with additional images multivalue separator. Added a condition to check for the previous version
    * Foreign key issue when using ProxySQL
    * Issue with absolute path of хml file (magento 2.1.8)
    * Issue with ‘category’ and ‘product_websites’ attributes for products not exporting when multiple store_views are selected
    * Issue with fresh installation of the extension
    * Issue with importing a single product in several bunches
    * Issue with swatch option update during product import procedure
    * Issue with importing bundle products in Magento 2.1
    * Issue with the stop on error option during the import process
    * Issue with directory separator in the export file path