<?php

    namespace WpOrg\Requests\Exception\Http;

    use WpOrg\Requests\Exception\Http;
    use WpOrg\Requests\Response;

    final class StatusUnknown extends Http
    {
        protected $code = 0;

        protected $reason = 'Unknown';

        public function __construct($reason = null, $data = null)
        {
            if($data instanceof Response)
            {
                $this->code = (int) $data->status_code;
            }

            parent::__construct($reason, $data);
        }
    }
