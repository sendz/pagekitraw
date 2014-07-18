<?php

namespace Pagekit\Framework;

trait ApplicationTrait
{
    /**
     * @var Application
     */
    protected static $app;

    /**
     * Gets the application.
     *
     * @return Application
     */
    public function getApplication()
    {
        return self::$app;
    }

    /**
     * Sets the application.
     *
     * @param Application $app
     */
    public static function setApplication(Application $app)
    {
        self::$app = $app;
    }

    /**
     * Whether an application parameter or an object exists.
     *
     * @param  string $offset
     * @return mixed
     */
    public function offsetExists($offset)
    {
        return isset(self::$app[$offset]);
    }

    /**
     * Gets an application parameter or an object.
     *
     * @param  string $offset
     * @return mixed
     */
    public function offsetGet($offset)
    {
        return self::$app[$offset];
    }

    /**
     * Sets an application parameter or an object.
     *
     * @param  string $offset
     * @param  mixed  $value
     */
    public function offsetSet($offset, $value)
    {
        self::$app[$offset] = $value;
    }

    /**
     * Unsets an application parameter or an object.
     *
     * @param  string $offset
     */
    public function offsetUnset($offset)
    {
        unset(self::$app[$offset]);
    }
}
