<?php

    namespace WpOrg\Requests\Auth;

    use WpOrg\Requests\Auth;
    use WpOrg\Requests\Exception\ArgumentCount;
    use WpOrg\Requests\Exception\InvalidArgument;
    use WpOrg\Requests\Hooks;

    class Basic implements Auth
    {
        public $user;

        public $pass;

        public function __construct($args = null)
        {
            if(is_array($args))
            {
                if(count($args) !== 2)
                {
                    throw ArgumentCount::create('an array with exactly two elements', count($args), 'authbasicbadargs');
                }

                [$this->user, $this->pass] = $args;

                return;
            }

            if($args !== null)
            {
                throw InvalidArgument::create(1, '$args', 'array|null', gettype($args));
            }
        }

        public function register(Hooks $hooks)
        {
            $hooks->register('curl.before_send', [$this, 'curl_before_send']);
            $hooks->register('fsockopen.after_headers', [$this, 'fsockopen_header']);
        }

        public function curl_before_send(&$handle)
        {
            curl_setopt($handle, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
            curl_setopt($handle, CURLOPT_USERPWD, $this->getAuthString());
        }

        public function getAuthString()
        {
            return $this->user.':'.$this->pass;
        }

        public function fsockopen_header(&$out)
        {
            $out .= sprintf("Authorization: Basic %s\r\n", base64_encode($this->getAuthString()));
        }
    }
