<?php
namespace born05\sentry;

use born05\sentry\models\Settings;
use born05\sentry\services\SentryService;

use Craft;
use craft\base\Plugin as CraftPlugin;
use craft\events\ExceptionEvent;
use craft\web\ErrorHandler;

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

        $this->setComponents([
            'sentry' => SentryService::class,
        ]);

        Event::on(
            ErrorHandler::className(),
            ErrorHandler::EVENT_BEFORE_HANDLE_EXCEPTION,
            function(ExceptionEvent $event) {
                $this->sentry->handleException($event->exception);
            }
        );
    }

    /**
     * @inheritdoc
     */
    protected function createSettingsModel()
    {
        return new Settings();
    }
}
