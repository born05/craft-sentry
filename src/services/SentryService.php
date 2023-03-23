<?php

namespace born05\sentry\services;

use born05\sentry\Plugin as SentryPlugin;

use Craft;
use craft\base\Component;

use Sentry;

class SentryService extends Component
{
    public function handleException($exception)
    {
        $plugin = SentryPlugin::$plugin;
        $settings = $plugin->getSettings();

        // If this is a Twig Runtime exception, use the previous one instead
        if ($exception instanceof \Twig_Error_Runtime && ($previousException = $exception->getPrevious()) !== null) {
            $exception = $previousException;
        }

        $statusCode = $exception->statusCode ?? null;

        if (in_array($statusCode, $settings->excludedCodes)) {
            Craft::info('Exception status code excluded from being reported to Sentry.', $plugin->handle);
            return;
        }

        Sentry\configureScope(function (Sentry\State\Scope $scope) use ($statusCode) {
            $scope->setExtra('Status Code', $statusCode);
        });

        Sentry\captureException($exception);
    }

    public function initDataScrubbing($sensitiveFields, &$options)
    {
        $options['before_send'] =  function (Sentry\Event $event) use ($sensitiveFields) {
            $replaceValue = '[Filtered]';
            $request = $event->getRequest();

            if(isset($request['data'])) {
                foreach ($sensitiveFields as $sensitiveField) {
                    if(is_string($sensitiveField) && isset($request['data'][$sensitiveField])) {
                        $request['data'][$sensitiveField] = $replaceValue;
                    }
                }
            }
            $event->setRequest($request);
            return $event;
        };
    }
}