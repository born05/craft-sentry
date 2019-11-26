<?php

namespace born05\sentry\services;

use born05\sentry\Plugin as SentryPlugin;

use Craft;
use craft\base\Component;

use Sentry;
use Sentry\State\Scope;

use yii\base\Exception;

class SentryService extends Component
{
    public function handleException($exception)
    {
        $app = Craft::$app;
        $info = $app->getInfo();
        $plugin = SentryPlugin::$plugin;
        $settings = $plugin->getSettings();

        if (!$settings->enabled || $app->getConfig()->getGeneral()->devMode) return;

        if (!$settings->clientDsn) {
            Craft::error('Failed to report exception due to missing client key (DSN)', $plugin->handle);
            return;
        }

        // If this is a Twig Runtime exception, use the previous one instead
        if ($exception instanceof \Twig_Error_Runtime && ($previousException = $exception->getPrevious()) !== null) {
            $exception = $previousException;
        }

        $statusCode = $exception->statusCode ?? null;

        if (in_array($statusCode, $settings->excludedCodes)) {
            Craft::info('Exception status code excluded from being reported to Sentry.', $plugin->handle);
            return;
        }

        Sentry\init([
            'dsn'         => $settings->clientDsn,
            'environment' => CRAFT_ENVIRONMENT,
            'release'     => $settings->release,
        ]);

        $user = $app->getUser()->getIdentity();

        Sentry\configureScope(function (Scope $scope) use ($app, $info, $plugin, $settings, $user, $statusCode) {
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
            $scope->setExtra('Status Code', $statusCode);
        });

        Sentry\captureException($exception);
    }
}
