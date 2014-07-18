<?php

namespace Pagekit\Component\Routing\Loader;

use Pagekit\Component\Routing\Controller\ControllerReaderInterface;
use Pagekit\Component\Routing\Exception\LoaderException;

class RouteLoader implements LoaderInterface
{
    /**
     * @var ControllerReaderInterface
     */
    protected $reader;

    /**
     * Constructor.
     *
     * @param ControllerReaderInterface $reader
     */
    public function __construct(ControllerReaderInterface $reader)
    {
        $this->reader = $reader;
    }

    /**
     * {@inheritdoc}
     */
    public function load($controller, array $options = [])
    {
        if ('.php' == substr($controller, -4) && file_exists($controller)) {
            $controller = $this->findClass($controller);
        }

        if (!class_exists($controller)) {
            throw new LoaderException(sprintf('Controller class "%s" does not exist.', $controller));
        }

        return $this->reader->read(new \ReflectionClass($controller), $options);
    }

    /**
     * Returns the full class name for the first class in the file.
     *
     * @param  string  $file
     * @return string|false
     */
    protected function findClass($file)
    {
        $class = false;
        $namespace = false;
        $tokens = token_get_all(file_get_contents($file));

        for ($i = 0, $count = count($tokens); $i < $count; $i++) {

            $token = $tokens[$i];

            if (!is_array($token)) {
                continue;
            }

            if (true === $class && T_STRING === $token[0]) {
                return $namespace.'\\'.$token[1];
            }

            if (true === $namespace && T_STRING === $token[0]) {
                $namespace = '';
                do {
                    $namespace .= $token[1];
                    $token = $tokens[++$i];
                } while ($i < $count && is_array($token) && in_array($token[0], [T_NS_SEPARATOR, T_STRING]));
            }

            if (T_CLASS === $token[0]) {
                $class = true;
            }

            if (T_NAMESPACE === $token[0]) {
                $namespace = true;
            }
        }

        return false;
    }
}
