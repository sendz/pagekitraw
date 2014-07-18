<?php

namespace Pagekit\Framework\Event;

use Pagekit\Framework\ApplicationTrait;

abstract class EventSubscriber implements EventSubscriberInterface, \ArrayAccess
{
    use ApplicationTrait;
}
