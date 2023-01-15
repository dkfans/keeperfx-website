<?php

namespace App;

use Compwright\PhpSession\Session;



class FlashMessage
{
    private Session $session;

    private const SESSION_VAR = 'flash_messages';

    /**
     * Constructor
     *
     * @param Session $session
     */
    public function __construct(Session $session)
    {
        $this->session = $session;

        if (!isset($this->session[self::SESSION_VAR]) || !is_array($this->session[self::SESSION_VAR])) {
            $this->session[self::SESSION_VAR] = [];
        }
    }

    /**
     * Add a flash message
     *
     * @param string $type
     * @param string|null $message
     * @return FlashMessage
     */
    public function add(string $type, ?string $message): FlashMessage
    {
        $this->session[self::SESSION_VAR][] = [
            'type'    => \strtolower($type),
            'message' => $message,
        ];

        return $this;
    }

    /**
     * Add an info flash message
     *
     * @param string|null $message
     * @return FlashMessage
     */
    public function info(?string $message): FlashMessage
    {
        return $this->add('info', $message);
    }

    /**
     * Add a success flash message
     *
     * @param string|null $message
     * @return FlashMessage
     */
    public function success(?string $message): FlashMessage
    {
        return $this->add('success', $message);
    }

    /**
     * Add a warning flash message
     *
     * @param string|null $message
     * @return FlashMessage
     */
    public function warning(?string $message): FlashMessage
    {
        return $this->add('warning', $message);
    }

    /**
     * Add an error flash message
     *
     * @param string|null $message
     * @return FlashMessage
     */
    public function error(?string $message): FlashMessage
    {
        return $this->add('error', $message);
    }

    /**
     * Retrieve all flash messages and possibly remove them from the session
     *
     * @return array
     */
    public function getAll(bool $remove = true): array
    {
        $messages = $this->session[self::SESSION_VAR];

        if ($remove) {
            $this->session[self::SESSION_VAR] = [];
        }

        return $messages;
    }

    /**
     * Retrieve all flash messages as an array.
     *
     * @param boolean $remove_after Remove messages after grabbing them
     * @return array
     */
    public function getAllAsArray(bool $remove_after = true): array
    {
        $return = [];

        foreach ($this->session[self::SESSION_VAR] as $i => $data) {
            $return[] = $data;

            if ($remove_after) {
                unset($this->session[self::SESSION_VAR][$i]);
            }
        }

        return $return;
    }

    /**
     * Check if there is a message to be displayed. Can be checked if a specific type of message exists
     *
     * @return bool
     */
    public function hasMessage(?string $type = null): bool
    {
        if (empty($type)) {
            return !empty($this->session[self::SESSION_VAR]);
        } else {
            foreach ($this->session[self::SESSION_VAR] as $message) {
                if ($message['type'] == $type) {
                    return true;
                }
            }
        }

        return false;
    }
}
