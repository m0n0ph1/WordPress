<?php

    namespace WpOrg\Requests\Utility;

    use ArrayIterator;
    use ReturnTypeWillChange;
    use WpOrg\Requests\Exception\InvalidArgument;

    final class FilteredIterator extends ArrayIterator
    {
        private $callback;

        public function __construct($data, $callback)
        {
            if(InputValidator::is_iterable($data) === false)
            {
                throw InvalidArgument::create(1, '$data', 'iterable', gettype($data));
            }

            parent::__construct($data);

            if(is_callable($callback))
            {
                $this->callback = $callback;
            }
        }

        #[ReturnTypeWillChange]
        public function __unserialize($data) {}

        // phpcs:enable

        public function __wakeup()
        {
            unset($this->callback);
        }

        #[ReturnTypeWillChange]
        public function current()
        {
            $value = parent::current();

            if(is_callable($this->callback))
            {
                $value = call_user_func($this->callback, $value);
            }

            return $value;
        }

        #[ReturnTypeWillChange]
        public function unserialize($data) {}
    }
