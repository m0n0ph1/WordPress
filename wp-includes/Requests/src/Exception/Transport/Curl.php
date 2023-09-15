<?php

    namespace WpOrg\Requests\Exception\Transport;

    use WpOrg\Requests\Exception\Transport;

    final class Curl extends Transport
    {
        const EASY = 'cURLEasy';

        const MULTI = 'cURLMulti';

        const SHARE = 'cURLShare';

        protected $code = -1;

        protected $type = 'Unknown';

        protected $reason = 'Unknown';

        public function __construct($message, $type, $data = null, $code = 0)
        {
            if($type !== null)
            {
                $this->type = $type;
            }

            if($code !== null)
            {
                $this->code = (int) $code;
            }

            if($message !== null)
            {
                $this->reason = $message;
            }

            $message = sprintf('%d %s', $this->code, $this->reason);
            parent::__construct($message, $this->type, $data, $this->code);
        }

        public function getReason()
        {
            return $this->reason;
        }
    }
