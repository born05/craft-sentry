<?php

namespace born05\sentry\models;

use craft\base\Model;

class Settings extends Model
{
    public $enabled = true;
    public $anonymous = false; // Determines to log user info or not
    public $clientDsn;
    public $excludedCodes = ['404'];
    public $release; // Release number/name used by sentry.
    public $reportJsErrors = false;
    public $sampleRate = 1.0;

    public function rules()
    {
        return [
            [['enabled', 'anonymous', 'reportJsErrors'], 'boolean'],
            [['clientDsn', 'excludedCodes', 'release'], 'string'],
            [['clientDsn'], 'required'],
            [['sampleRate'], 'number'],
        ];
    }
}
