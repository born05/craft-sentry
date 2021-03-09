<?php
namespace born05\sentry;

use born05\sentry\models\Settings;
use born05\sentry\services\SentryService;

use Craft;
use craft\base\Plugin as CraftPlugin;
use craft\events\ExceptionEvent;
use craft\events\TemplateEvent;
use craft\services\Plugins;
use craft\web\ErrorHandler;
use craft\web\View;

use Sentry;
use Sentry\State\Scope;

use yii\base\Event;

/**
 * Sentry craft cms plugin
 *
 * @property SentryService $sentry The sentry component
 * @method SentryService getSentry()      Returns the sentry component.
 */
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

            // Prevent ExceptionListenerIntegration from loading.
            'integrations' => static function (array $integrations) {
                return array_filter($integrations, static function (\Sentry\Integration\IntegrationInterface $integration): bool {
                    if ($integration instanceof \Sentry\Integration\ExceptionListenerIntegration) {
                        return false;
                    }

                    return true;
                });
            },
        ];

        Sentry\init($options);

        /**
         * Setup user
         */
        Event::on(Plugins::class, Plugins::EVENT_AFTER_LOAD_PLUGINS, function () use ($app, $settings) {
            $user = $app->getUser()->getIdentity();

            Sentry\configureScope(function (Scope $scope) use ($settings, $user) {
                if ($user && !$settings->anonymous) {
                    $scope->setUser([
                        'ID'       => $user->id,
                        'Username' => $user->username,
                        'Email'    => $user->email,
                        'Admin'    => $user->admin ? 'Yes' : 'No',
                    ]);
                }
            });
        });

        Sentry\configureScope(function (Scope $scope) use ($app, $info) {
            $scope->setExtra('App Type', 'Craft CMS');
            $scope->setExtra('App Name', $info->name);
            $scope->setExtra('App Edition (licensed)', $app->getLicensedEditionName());
            $scope->setExtra('App Edition (running)', $app->getEditionName());
            $scope->setExtra('App Version', $info->version);
            $scope->setExtra('App Version (schema)', $info->schemaVersion);
            $scope->setExtra('PHP Version', phpversion());
        });

        /**
         * Init Sentry JS SDK (Front end)
         */
        if (Craft::$app->request->isSiteRequest && $settings->reportJsErrors) {
            Event::on(
                View::class,
                View::EVENT_BEFORE_RENDER_TEMPLATE,
                function (TemplateEvent $event) {
                    $settings = $this->getSettings();
                    $view = Craft::$app->getView();

                    $view->registerScript(
                        "",
                        View::POS_END,
                        array_merge([
                            'src' => 'https://browser.sentry-cdn.com/5.29.2/bundle.tracing.min.js',
                            'crossorigin' => 'anonymous',
                            'integrity' => 'sha384-4zxA5Bnxor/VkZae20EqPP3A/6vDlw1ZhqF7EvpmeTfWYFjPIDdaUSOk/q7G/bYw',
                        ], $this->getScriptOptions())
                    );

                    // Returns devMode boolean as a string so it can be passed to the debug parameter properly.
                    $isDevMode = Craft::$app->config->general->devMode ? 'true' : 'false';

                    $view->registerScript("
                    Sentry.init({
                      dsn: '$settings->clientDsn',
                      release: '$settings->release',
                      environment: '".CRAFT_ENVIRONMENT."',
                      debug: $isDevMode,
                      integrations: [new Sentry.Integrations.BrowserTracing()],
                      tracesSampleRate: $settings->sampleRate,
                    });", View::POS_END, $this->getScriptOptions());
                }
            );
        }

        /**
         * Listen to exceptions
         */
        Event::on(
            ErrorHandler::className(),
            ErrorHandler::EVENT_BEFORE_HANDLE_EXCEPTION,
            function(ExceptionEvent $event) {
                $this->sentry->handleException($event->exception);
            }
        );
    }

    /**
     * @return SentryService
     */
    public function getSentry(): SentryService
    {
        return $this->sentry;
    }

    private function getScriptOptions() {
        $options = [];
        
        if (class_exists('\born05\contentsecuritypolicy\Plugin')) {
            $options['nonce'] = \born05\contentsecuritypolicy\Plugin::$plugin->headers->registerNonce('script-src');
        }

        return $options;
    }

    /**
     * @inheritdoc
     */
    protected function createSettingsModel()
    {
        return new Settings();
    }
}
