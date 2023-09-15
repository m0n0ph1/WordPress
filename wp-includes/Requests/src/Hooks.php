<?php

    namespace WpOrg\Requests;

    use WpOrg\Requests\Exception\InvalidArgument;
    use WpOrg\Requests\Utility\InputValidator;

    class Hooks implements HookManager
    {
        protected $hooks = [];

        public function register($hook, $callback, $priority = 0)
        {
            if(is_string($hook) === false)
            {
                throw InvalidArgument::create(1, '$hook', 'string', gettype($hook));
            }

            if(is_callable($callback) === false)
            {
                throw InvalidArgument::create(2, '$callback', 'callable', gettype($callback));
            }

            if(InputValidator::is_numeric_array_key($priority) === false)
            {
                throw InvalidArgument::create(3, '$priority', 'integer', gettype($priority));
            }

            if(! isset($this->hooks[$hook]))
            {
                $this->hooks[$hook] = [
                    $priority => [],
                ];
            }
            elseif(! isset($this->hooks[$hook][$priority]))
            {
                $this->hooks[$hook][$priority] = [];
            }

            $this->hooks[$hook][$priority][] = $callback;
        }

        public function dispatch($hook, $parameters = [])
        {
            if(is_string($hook) === false)
            {
                throw InvalidArgument::create(1, '$hook', 'string', gettype($hook));
            }

            // Check strictly against array, as Array* objects don't work in combination with `call_user_func_array()`.
            if(is_array($parameters) === false)
            {
                throw InvalidArgument::create(2, '$parameters', 'array', gettype($parameters));
            }

            if(empty($this->hooks[$hook]))
            {
                return false;
            }

            if(! empty($parameters))
            {
                // Strip potential keys from the array to prevent them being interpreted as parameter names in PHP 8.0.
                $parameters = array_values($parameters);
            }

            ksort($this->hooks[$hook]);

            foreach($this->hooks[$hook] as $priority => $hooked)
            {
                foreach($hooked as $callback)
                {
                    $callback(...$parameters);
                }
            }

            return true;
        }
    }
