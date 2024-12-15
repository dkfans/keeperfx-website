<?php

namespace App\I18n;

use App\I18n\Exception\LocaleException;

class Locale {

    private const I18N_DIR = APP_ROOT . '/i18n';

    private string $code;

    private string $date_format;
    private string $date_format_long;

    private string $datetime_format;
    private string $datetime_format_long;

    private string $time_12hr_format;
    private string $time_24hr_format;

    private bool $time_is_24hr;

    public function __construct(string $code = 'en_US')
    {
        $this->load($code);
    }

    public function load(string $code)
    {
        $config_path = self::I18N_DIR . "/{$code}/{$code}.locale.php";
        $config      = require $config_path;

        if(!\is_array($config)){
            throw new LocaleException("Invalid locale config: '{$config_path}'. Must return an array.");
        }

        $this->code                 = $code;
        $this->date_format          = (string) $config['date_format'];
        $this->date_format_long     = (string) $config['date_format_long'];
        $this->datetime_format      = (string) $config['datetime_format'];
        $this->datetime_format_long = (string) $config['datetime_format_long'];
        $this->time_12hr_format     = (string) $config['time_12hr_format'];
        $this->time_24hr_format     = (string) $config['time_24hr_format'];
        $this->time_is_24hr         = (bool) $config['time_is_24hr'];
    }

    public function getCode(): string
    {
        return $this->code;
    }

    /**
     * Get the date format for use with \DateTime
     */
    public function getDateFormat(): string
    {
        return $this->date_format;
    }

    /**
     * Set the date format for use with \DateTime
     */
    public function setDateFormat(string $date_format): self
    {
        $this->date_format = $date_format;

        return $this;
    }

    /**
     * Get the long date format for use with \DateTime
     */
    public function getDateFormatLong(): string
    {
        return $this->date_format_long;
    }

    /**
     * Set the long date format for use with \DateTime
     */
    public function setDateFormatLong(string $date_format_long)
    {
        $this->date_format_long = $date_format_long;

        return $this;
    }

    /**
     * Get the datetime format for use with \DateTime
     */
    public function getDatetime_format(): string
    {
        return $this->datetime_format;
    }

    /**
     * Set the datetime format for use with \DateTime
     */
    public function setDatetimeFormat(string $datetime_format): self
    {
        $this->datetime_format = $datetime_format;

        return $this;
    }

    /**
     * Get the long datetime format for use with \DateTime
     */
    public function getDatetimeFormatLong(): string
    {
        return $this->datetime_format_long;
    }

    /**
     * Set the long datetime format for use with \DateTime
     */
    public function setDatetime_format_long(string $datetime_format_long): self
    {
        $this->datetime_format_long = $datetime_format_long;

        return $this;
    }

    /**
     * Get time format for use with \DateTime
     */
    public function getTime12hrFormat(): string
    {
        return $this->time_12hr_format;
    }

    /**
     * Set time format for use with \DateTime
     */
    public function setTime12hrFormat(string $time_12hr_format): self
    {
        $this->time_12hr_format = $time_12hr_format;

        return $this;
    }

    /**
     * Get time format for use with \DateTime
     */
    public function getTime24hrFormat(): string
    {
        return $this->time_24hr_format;
    }

    /**
     * Set time format for use with \DateTime
     */
    public function setTime24hrFormat(string $time_24hr_format): self
    {
        $this->time_24hr_format = $time_24hr_format;

        return $this;
    }

    /**
     * Get whether or not to display 24hr timestamps or not
     */
    public function getTimeIs24hr(): bool
    {
        return $this->time_is_24hr;
    }

    /**
     * Set whether or not to display 24hr timestamps or not
     */
    public function setTimeIs24hr(bool $time_is_24hr): self
    {
        $this->time_is_24hr = $time_is_24hr;

        return $this;
    }
}
