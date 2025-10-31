<?php defined('MW_PATH') || exit('No direct script access allowed');

/**
 * Spotzee Email Proxy API Extension
 *
 * Integrates MailWizz email marketing platform with Spotzee's email delivery service,
 * providing automatic bounce and complaint handling through webhooks.
 *
 * @package MailWizz Extension
 * @author Spotzee Team <contact@spotzee.com>
 * @link https://spotzee.com
 * @copyright 2025 Spotzee Marketing
 * @license FSL-2.0 (Functional Source License 2.0)
 * @version 0.1.0
 */

class SpotzeeWebApiExt extends ExtensionInit
{
    // name of the extension as shown in the backend panel
    public $name = 'Spotzee Email Proxy API';

    // description of the extension as shown in backend panel
    public $description = 'Integrates MailWizz with Spotzee Email Proxy API for reliable email delivery with automatic bounce and complaint handling via webhooks.';

    // current version of this extension
    public $version = '0.1.0';

    // minimum app version
    public $minAppVersion = '2.0.0';

    // the author name
    public $author = 'Spotzee Team';

    // author website
    public $website = 'https://spotzee.com';

    // contact email address
    public $email = 'contact@spotzee.com';

    /**
     * in which apps this extension is allowed to run
     * '*' means all apps
     * available apps: customer, backend, frontend, api, console
     * so you can use any variation,
     * like: array('backend', 'customer'); or array('frontend');
     */
    public $allowedApps = array('backend', 'console', 'customer', 'frontend', 'api');

    /**
     * This is the reverse of the above
     * Instead of writing:
     * public $allowedApps = array('frontend', 'customer', 'api', 'console');
     * you could say:
     * public $notAllowedApps = array('backend');
     */
    public $notAllowedApps = array();

    // cli enabled
    // since cli is a special case, we need to explicitly enable it
    // do it only if you need to hook inside console hooks
    public $cliEnabled = true;

    // can this extension be deleted? this only applies to core extensions.
    protected $_canBeDeleted = false;

    // can this extension be disabled? this only applies to core extensions.
    protected $_canBeDisabled = true;

    /**
     * The run method is the entry point of the extension.
     * This method is called by mailwizz at the right time to run the extension.
     */
    public function run()
    {
        $this->importClasses('common.models.*');

        // add delivery server type
        hooks()->addFilter('delivery_servers_get_types_mapping', function ($mapping) {
            $mapping['spotzee-web-api'] = 'DeliveryServerSpotzeeWebApi';
            return $mapping;
        });

        // handle all customer related tasks
        if ($this->isAppName('customer')) {
            // inject view file
            hooks()->addFilter('delivery_servers_form_view_file', function ($view, $server, $controller) {
                if ('spotzee-web-api' === $server->type) {
                    $view = "ext-spotzee-web-api.customer.views.delivery_servers.$view";
                }
                return $view;
            });

            hooks()->addAction('controller_action_save_data', [$this, '_controllerActionSaveDataHandler']);
        }

        // handle all backend related tasks
        if ($this->isAppName('backend')) {
            container()->add(OptionCronProcessSendingDomains::class, OptionCronProcessSendingDomains::class);
            container()->add(OptionCronProcessTrackingDomains::class, OptionCronProcessTrackingDomains::class);

            // inject view file
            hooks()->addFilter('delivery_servers_form_view_file', function ($view, $server, $controller) {
                if ('spotzee-web-api' === $server->type) {
                    $view = "ext-spotzee-web-api.backend.views.delivery_servers.$view";
                }
                return $view;
            });

            hooks()->addAction('controller_action_save_data', [$this, '_controllerActionSaveDataHandler']);
        }

        // handle all frontend related tasks
        if ($this->isAppName('frontend')) {
            $this->addUrlRules(array(
                array('dswh/index', 'pattern' => 'dswh/spotzee-api/<id:([0-9]+)>'),
            ), false);

            $this->addControllerMap([
                'dswh' => array(
                    'class'     => 'frontend.controllers.SpotzeeCustomExtFrontendDswhController',
                    'extension' => $this,
                )
            ]);
        }
    }

    /**
     * Handler for controller action save data
     * Note: Automatic webhook creation is not implemented - users must manually configure webhooks
     *
     * @param CAttributeCollection $collection
     * @return void
     */
    public function _controllerActionSaveDataHandler($collection)
    {
        // No action needed on server save - webhook URLs must be manually configured
    }

    /**
     * Code to run before enabling the extension.
     * Make sure to call the parent implementation
     *
     * Please note that if you return false here
     * the extension will not be enabled.
     */
    public function beforeEnable()
    {
        // clean directories of old asset files.
        FileSystemHelper::clearCache();

        // remove the cache, can be redis for example
        Yii::app()->cache->flush();

        // rebuild the tables schema cache
        Yii::app()->db->schema->getTables();
        Yii::app()->db->schema->refresh();
        // call parent
        return parent::beforeEnable();
    }

    public function afterDisable()
    {
    }

    /**
     * Code to run after delete the extension.
     * Make sure to call the parent implementation
     */
    public function afterDelete()
    {
        // rebuild the tables schema cache
        Yii::app()->db->schema->getTables();
        Yii::app()->db->schema->refresh();
        // call parent
        parent::afterDelete();
    }

    /**
     * @return ExtensionInit
     */
    public function getExtension(): ExtensionInit
    {
        return extensionsManager()->getExtensionInstance('spotzee-web-api');
    }
}
