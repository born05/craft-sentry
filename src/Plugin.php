<?php
namespace born05\sentry;

use born05\sentry\models\Settings;
use born05\sentry\services\SentryService;

use Craft;
use craft\base\Plugin as CraftPlugin;
use craft\events\ExceptionEvent;
use craft\web\ErrorHandler;

use Sentry;
use Sentry\State\Scope;

use yii\base\Event;

class Plugin extends CraftPlugin
{
    /**
     * Static property that is an instance of this plugin class so that it can be accessed via
     * Plugin::$plugin
     *
     * @var Plugin
     */
    public static $plugin;

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        self::$plugin = $this;

        Event::on(Plugins::class, Plugins::EVENT_AFTER_LOAD_PLUGINS, function () {
            $this->setComponents([
                'sentry' => SentryService::class,
            ]);

            $app = Craft::$app;
            $info = $app->getInfo();
            $settings = $this->getSettings();

            if (!$this->isInstalled || !$settings->enabled) return;

            if (!$settings->clientDsn) {
                Craft::error('Failed to report exception due to missing client key (DSN)', $this->handle);
                return;
            }

            /**
             * Init Sentry
             */
            $options = [
                'dsn'         => $settings->clientDsn,
                'environment' => CRAFT_ENVIRONMENT,
                'release'     => $settings->release,
            ];

            // ErrorHandler doens't fire on console
            if (!Craft::$app->getRequest()->getIsConsoleRequest()) {
                $options['default_integrations'] = false;
            }

            Sentry\init($options);

            /**
             * Setup user
             */
            $user = $app->getUser()->getIdentity();

            Sentry\configureScope(function (Scope $scope) use ($app, $info, $settings, $user) {
                if ($user && !$settings->anonymous) {
                    $scope->setUser([
                        'ID'       => $user->id,
                        'Username' => $user->username,
                        'Email'    => $user->email,
                        'Admin'    => $user->admin ? 'Yes' : 'No',
                    ]);
                }

                $scope->setExtra('App Type', 'Craft CMS');
                $scope->setExtra('App Name', $info->name);
                $scope->setExtra('App Edition (licensed)', $app->getLicensedEditionName());
                $scope->setExtra('App Edition (running)', $app->getEditionName());
                $scope->setExtra('App Version', $info->version);
                $scope->setExtra('App Version (schema)', $info->schemaVersion);
                $scope->setExtra('PHP Version', phpversion());
            });

            Event::on(
                ErrorHandler::className(),
                ErrorHandler::EVENT_BEFORE_HANDLE_EXCEPTION,
                function(ExceptionEvent $event) {
                    $this->sentry->handleException($event->exception);
                }
            );
        });
    }

    /**
     * @inheritdoc
     */
    protected function createSettingsModel()
    {
        return new Settings();
    }
}
