<?php

    namespace WpOrg\Requests\Exception;

    use WpOrg\Requests\Exception;
    use WpOrg\Requests\Exception\Http\StatusUnknown;

    class Http extends Exception
    {
        protected $code = 0;

        protected $reason = 'Unknown';

        public function __construct($reason = null, $data = null)
        {
            if($reason !== null)
            {
                $this->reason = $reason;
            }

            $message = sprintf('%d %s', $this->code, $this->reason);
            parent::__construct($message, 'httpresponse', $data, $this->code);
        }

        public static function get_class($code)
        {
            if(! $code)
            {
                return StatusUnknown::class;
            }

            $class = sprintf('\WpOrg\Requests\Exception\Http\Status%d', $code);
            if(class_exists($class))
            {
                return $class;
            }

            return StatusUnknown::class;
        }

        public function getReason()
        {
            return $this->reason;
        }
    }
