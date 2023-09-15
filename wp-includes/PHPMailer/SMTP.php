<?php

    namespace PHPMailer\PHPMailer;

    class SMTP
    {
        const VERSION = '6.8.1';

        const LE = "\r\n";

        const DEFAULT_PORT = 25;

        const DEFAULT_SECURE_PORT = 465;

        const MAX_LINE_LENGTH = 998;

        const MAX_REPLY_LENGTH = 512;

        const DEBUG_OFF = 0;

        const DEBUG_CLIENT = 1;

        const DEBUG_SERVER = 2;

        const DEBUG_CONNECTION = 3;

        const DEBUG_LOWLEVEL = 4;

        public $do_debug = self::DEBUG_OFF;

        public $Debugoutput = 'echo';

        public $do_verp = false;

        public $Timeout = 300;

        public $Timelimit = 300;

        protected $smtp_transaction_id_patterns = [
            'exim' => '/[\d]{3} OK id=(.*)/',
            'sendmail' => '/[\d]{3} 2.0.0 (.*) Message/',
            'postfix' => '/[\d]{3} 2.0.0 Ok: queued as (.*)/',
            'Microsoft_ESMTP' => '/[0-9]{3} 2.[\d].0 (.*)@(?:.*) Queued mail for delivery/',
            'Amazon_SES' => '/[\d]{3} Ok (.*)/',
            'SendGrid' => '/[\d]{3} Ok: queued as (.*)/',
            'CampaignMonitor' => '/[\d]{3} 2.0.0 OK:([a-zA-Z\d]{48})/',
            'Haraka' => '/[\d]{3} Message Queued \((.*)\)/',
            'ZoneMTA' => '/[\d]{3} Message queued as (.*)/',
            'Mailjet' => '/[\d]{3} OK queued as (.*)/',
        ];

        protected $last_smtp_transaction_id;

        protected $smtp_conn;

        protected $error = [
            'error' => '',
            'detail' => '',
            'smtp_code' => '',
            'smtp_code_ex' => '',
        ];

        protected $helo_rply;

        protected $server_caps;

        protected $last_reply = '';

        public function connect($host, $port = null, $timeout = 30, $options = [])
        {
            // Clear errors to avoid confusion
            $this->setError('');
            // Make sure we are __not__ connected
            if($this->connected())
            {
                // Already connected, generate error
                $this->setError('Already connected to a server');

                return false;
            }
            if(empty($port))
            {
                $port = self::DEFAULT_PORT;
            }
            // Connect to the SMTP server
            $this->edebug("Connection: opening to $host:$port, timeout=$timeout, options=".(count($options) > 0 ? var_export($options, true) : 'array()'), self::DEBUG_CONNECTION);

            $this->smtp_conn = $this->getSMTPConnection($host, $port, $timeout, $options);

            if($this->smtp_conn === false)
            {
                // Error info already set inside `getSMTPConnection()`
                return false;
            }

            $this->edebug('Connection: opened', self::DEBUG_CONNECTION);

            // Get any announcement
            $this->last_reply = $this->get_lines();
            $this->edebug('SERVER -> CLIENT: '.$this->last_reply, self::DEBUG_SERVER);
            $responseCode = (int) substr($this->last_reply, 0, 3);
            if($responseCode === 220)
            {
                return true;
            }
            // Anything other than a 220 response means something went wrong
            // RFC 5321 says the server will wait for us to send a QUIT in response to a 554 error
            // https://tools.ietf.org/html/rfc5321#section-3.1
            if($responseCode === 554)
            {
                $this->quit();
            }
            // This will handle 421 responses which may not wait for a QUIT (e.g. if the server is being shut down)
            $this->edebug('Connection: closing due to error', self::DEBUG_CONNECTION);
            $this->close();

            return false;
        }

        public function connected()
        {
            if(is_resource($this->smtp_conn))
            {
                $sock_status = stream_get_meta_data($this->smtp_conn);
                if($sock_status['eof'])
                {
                    // The socket is valid but we are not connected
                    $this->edebug('SMTP NOTICE: EOF caught while checking if connected', self::DEBUG_CLIENT);
                    $this->close();

                    return false;
                }

                return true; // everything looks good
            }

            return false;
        }

        protected function edebug($str, $level = 0)
        {
            if($level > $this->do_debug)
            {
                return;
            }
            // Is this a PSR-3 logger?
            if($this->Debugoutput instanceof \Psr\Log\LoggerInterface)
            {
                $this->Debugoutput->debug($str);

                return;
            }
            // Avoid clash with built-in function names
            if(is_callable($this->Debugoutput) && ! in_array($this->Debugoutput, ['error_log', 'html', 'echo']))
            {
                call_user_func($this->Debugoutput, $str, $level);

                return;
            }
            switch($this->Debugoutput)
            {
                case 'error_log':
                    // Don't output, just log
                    error_log($str);
                    break;
                case 'html':
                    // Cleans up output a bit for a better looking, HTML-safe output
                    echo gmdate('Y-m-d H:i:s'), ' ', htmlentities(preg_replace('/[\r\n]+/', '', $str), ENT_QUOTES, 'UTF-8'), "<br>\n";
                    break;
                case 'echo':
                default:
                    // Normalize line breaks
                    $str = preg_replace('/\r\n|\r/m', "\n", $str);
                    echo gmdate('Y-m-d H:i:s'), "\t", // Trim trailing space
                    trim(// Indent for readability, except for trailing break
                        str_replace("\n", "\n                   \t                  ", trim($str))
                    ), "\n";
            }
        }

        public function close()
        {
            $this->server_caps = null;
            $this->helo_rply = null;
            if(is_resource($this->smtp_conn))
            {
                // Close the connection and cleanup
                fclose($this->smtp_conn);
                $this->smtp_conn = null; // Makes for cleaner serialization
                $this->edebug('Connection: closed', self::DEBUG_CONNECTION);
            }
        }

        protected function getSMTPConnection($host, $port = null, $timeout = 30, $options = [])
        {
            static $streamok;
            // This is enabled by default since 5.0.0 but some providers disable it
            // Check this once and cache the result
            if(null === $streamok)
            {
                $streamok = function_exists('stream_socket_client');
            }

            $errno = 0;
            $errstr = '';
            if($streamok)
            {
                $socket_context = stream_context_create($options);
                set_error_handler([$this, 'errorHandler']);
                $connection = stream_socket_client($host.':'.$port, $errno, $errstr, $timeout, STREAM_CLIENT_CONNECT, $socket_context);
            }
            else
            {
                // Fall back to fsockopen which should work in more places, but is missing some features
                $this->edebug('Connection: stream_socket_client not available, falling back to fsockopen', self::DEBUG_CONNECTION);
                set_error_handler([$this, 'errorHandler']);
                $connection = fsockopen($host, $port, $errno, $errstr, $timeout);
            }
            restore_error_handler();

            // Verify we connected properly
            if(! is_resource($connection))
            {
                $this->setError('Failed to connect to server', '', (string) $errno, $errstr);
                $this->edebug('SMTP ERROR: '.$this->error['error'].": $errstr ($errno)", self::DEBUG_CLIENT);

                return false;
            }

            // SMTP server can take longer to respond, give longer timeout for first read
            // Windows does not have support for this timeout function
            if(strpos(PHP_OS, 'WIN') !== 0)
            {
                $max = (int) ini_get('max_execution_time');
                // Don't bother if unlimited, or if set_time_limit is disabled
                if(0 !== $max && $timeout > $max && strpos(ini_get('disable_functions'), 'set_time_limit') === false)
                {
                    @set_time_limit($timeout);
                }
                stream_set_timeout($connection, $timeout, 0);
            }

            return $connection;
        }

        protected function get_lines()
        {
            // If the connection is bad, give up straight away
            if(! is_resource($this->smtp_conn))
            {
                return '';
            }
            $data = '';
            $endtime = 0;
            stream_set_timeout($this->smtp_conn, $this->Timeout);
            if($this->Timelimit > 0)
            {
                $endtime = time() + $this->Timelimit;
            }
            $selR = [$this->smtp_conn];
            $selW = null;
            while(is_resource($this->smtp_conn) && ! feof($this->smtp_conn))
            {
                // Must pass vars in here as params are by reference
                // solution for signals inspired by https://github.com/symfony/symfony/pull/6540
                set_error_handler([$this, 'errorHandler']);
                $n = stream_select($selR, $selW, $selW, $this->Timelimit);
                restore_error_handler();

                if($n === false)
                {
                    $message = $this->getError()['detail'];

                    $this->edebug('SMTP -> get_lines(): select failed ('.$message.')', self::DEBUG_LOWLEVEL);

                    // stream_select returns false when the `select` system call is interrupted
                    // by an incoming signal, try the select again
                    if(stripos($message, 'interrupted system call') !== false)
                    {
                        $this->edebug('SMTP -> get_lines(): retrying stream_select', self::DEBUG_LOWLEVEL);
                        $this->setError('');
                        continue;
                    }

                    break;
                }

                if(! $n)
                {
                    $this->edebug('SMTP -> get_lines(): select timed-out in ('.$this->Timelimit.' sec)', self::DEBUG_LOWLEVEL);
                    break;
                }

                // Deliberate noise suppression - errors are handled afterwards
                $str = @fgets($this->smtp_conn, self::MAX_REPLY_LENGTH);
                $this->edebug('SMTP INBOUND: "'.trim($str).'"', self::DEBUG_LOWLEVEL);
                $data .= $str;
                // If response is only 3 chars (not valid, but RFC5321 S4.2 says it must be handled),
                // or 4th character is a space or a line break char, we are done reading, break the loop.
                // String array access is a significant micro-optimisation over strlen
                if(! isset($str[3]) || $str[3] === ' ' || $str[3] === "\r" || $str[3] === "\n")
                {
                    break;
                }
                // Timed-out? Log and break
                $info = stream_get_meta_data($this->smtp_conn);
                if($info['timed_out'])
                {
                    $this->edebug('SMTP -> get_lines(): stream timed-out ('.$this->Timeout.' sec)', self::DEBUG_LOWLEVEL);
                    break;
                }
                // Now check if reads took too long
                if($endtime && time() > $endtime)
                {
                    $this->edebug('SMTP -> get_lines(): timelimit reached ('.$this->Timelimit.' sec)', self::DEBUG_LOWLEVEL);
                    break;
                }
            }

            return $data;
        }

        public function getError()
        {
            return $this->error;
        }

        protected function setError($message, $detail = '', $smtp_code = '', $smtp_code_ex = '')
        {
            $this->error = [
                'error' => $message,
                'detail' => $detail,
                'smtp_code' => $smtp_code,
                'smtp_code_ex' => $smtp_code_ex,
            ];
        }

        public function quit($close_on_error = true)
        {
            $noerror = $this->sendCommand('QUIT', 'QUIT', 221);
            $err = $this->error; // Save any error
            if($noerror || $close_on_error)
            {
                $this->close();
                $this->error = $err; // Restore any error from the quit command
            }

            return $noerror;
        }

        protected function sendCommand($command, $commandstring, $expect)
        {
            if(! $this->connected())
            {
                $this->setError("Called $command without being connected");

                return false;
            }
            // Reject line breaks in all commands
            if((strpos($commandstring, "\n") !== false) || (strpos($commandstring, "\r") !== false))
            {
                $this->setError("Command '$command' contained line breaks");

                return false;
            }
            $this->client_send($commandstring.static::LE, $command);

            $this->last_reply = $this->get_lines();
            // Fetch SMTP code and possible error code explanation
            $matches = [];
            if(preg_match('/^([\d]{3})[ -](?:([\d]\\.[\d]\\.[\d]{1,2}) )?/', $this->last_reply, $matches))
            {
                $code = (int) $matches[1];
                $code_ex = (count($matches) > 2 ? $matches[2] : null);
                // Cut off error code from each response line
                $detail = preg_replace("/{$code}[ -]".($code_ex ? str_replace('.', '\\.', $code_ex).' ' : '').'/m', '', $this->last_reply);
            }
            else
            {
                // Fall back to simple parsing if regex fails
                $code = (int) substr($this->last_reply, 0, 3);
                $code_ex = null;
                $detail = substr($this->last_reply, 4);
            }

            $this->edebug('SERVER -> CLIENT: '.$this->last_reply, self::DEBUG_SERVER);

            if(! in_array($code, (array) $expect, true))
            {
                $this->setError("$command command failed", $detail, $code, $code_ex);
                $this->edebug('SMTP ERROR: '.$this->error['error'].': '.$this->last_reply, self::DEBUG_CLIENT);

                return false;
            }

            // Don't clear the error store when using keepalive
            if($command !== 'RSET')
            {
                $this->setError('');
            }

            return true;
        }

        public function client_send($data, $command = '')
        {
            // If SMTP transcripts are left enabled, or debug output is posted online
            // it can leak credentials, so hide credentials in all but lowest level
            if(
                self::DEBUG_LOWLEVEL > $this->do_debug && in_array($command, [
                    'User & Password',
                    'Username',
                    'Password'
                ],                                                 true)
            )
            {
                $this->edebug('CLIENT -> SERVER: [credentials hidden]', self::DEBUG_CLIENT);
            }
            else
            {
                $this->edebug('CLIENT -> SERVER: '.$data, self::DEBUG_CLIENT);
            }
            set_error_handler([$this, 'errorHandler']);
            $result = fwrite($this->smtp_conn, $data);
            restore_error_handler();

            return $result;
        }

        public function startTLS()
        {
            if(! $this->sendCommand('STARTTLS', 'STARTTLS', 220))
            {
                return false;
            }

            // Allow the best TLS version(s) we can
            $crypto_method = STREAM_CRYPTO_METHOD_TLS_CLIENT;

            // PHP 5.6.7 dropped inclusion of TLS 1.1 and 1.2 in STREAM_CRYPTO_METHOD_TLS_CLIENT
            // so add them back in manually if we can
            if(defined('STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT'))
            {
                $crypto_method |= STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT;
                $crypto_method |= STREAM_CRYPTO_METHOD_TLSv1_1_CLIENT;
            }

            // Begin encrypted connection
            set_error_handler([$this, 'errorHandler']);
            $crypto_ok = stream_socket_enable_crypto($this->smtp_conn, true, $crypto_method);
            restore_error_handler();

            return (bool) $crypto_ok;
        }

        public function authenticate(
            $username, $password, $authtype = null, $OAuth = null
        ) {
            if(! $this->server_caps)
            {
                $this->setError('Authentication is not allowed before HELO/EHLO');

                return false;
            }

            if(array_key_exists('EHLO', $this->server_caps))
            {
                // SMTP extensions are available; try to find a proper authentication method
                if(! array_key_exists('AUTH', $this->server_caps))
                {
                    $this->setError('Authentication is not allowed at this stage');
                    //'at this stage' means that auth may be allowed after the stage changes
                    // e.g. after STARTTLS

                    return false;
                }

                $this->edebug('Auth method requested: '.($authtype ?: 'UNSPECIFIED'), self::DEBUG_LOWLEVEL);
                $this->edebug('Auth methods available on the server: '.implode(',', $this->server_caps['AUTH']), self::DEBUG_LOWLEVEL);

                // If we have requested a specific auth type, check the server supports it before trying others
                if(null !== $authtype && ! in_array($authtype, $this->server_caps['AUTH'], true))
                {
                    $this->edebug('Requested auth method not available: '.$authtype, self::DEBUG_LOWLEVEL);
                    $authtype = null;
                }

                if(empty($authtype))
                {
                    // If no auth mechanism is specified, attempt to use these, in this order
                    // Try CRAM-MD5 first as it's more secure than the others
                    foreach(['CRAM-MD5', 'LOGIN', 'PLAIN', 'XOAUTH2'] as $method)
                    {
                        if(in_array($method, $this->server_caps['AUTH'], true))
                        {
                            $authtype = $method;
                            break;
                        }
                    }
                    if(empty($authtype))
                    {
                        $this->setError('No supported authentication methods found');

                        return false;
                    }
                    $this->edebug('Auth method selected: '.$authtype, self::DEBUG_LOWLEVEL);
                }

                if(! in_array($authtype, $this->server_caps['AUTH'], true))
                {
                    $this->setError("The requested authentication method \"$authtype\" is not supported by the server");

                    return false;
                }
            }
            elseif(empty($authtype))
            {
                $authtype = 'LOGIN';
            }
            switch($authtype)
            {
                case 'PLAIN':
                    // Start authentication
                    if(! $this->sendCommand('AUTH', 'AUTH PLAIN', 334))
                    {
                        return false;
                    }
                    // Send encoded username and password
                    if(// Format from https://tools.ietf.org/html/rfc4616#section-2
                        // We skip the first field (it's forgery), so the string starts with a null byte
                    ! $this->sendCommand('User & Password', base64_encode("\0".$username."\0".$password), 235)
                    )
                    {
                        return false;
                    }
                    break;
                case 'LOGIN':
                    // Start authentication
                    if(! $this->sendCommand('AUTH', 'AUTH LOGIN', 334))
                    {
                        return false;
                    }
                    if(! $this->sendCommand('Username', base64_encode($username), 334))
                    {
                        return false;
                    }
                    if(! $this->sendCommand('Password', base64_encode($password), 235))
                    {
                        return false;
                    }
                    break;
                case 'CRAM-MD5':
                    // Start authentication
                    if(! $this->sendCommand('AUTH CRAM-MD5', 'AUTH CRAM-MD5', 334))
                    {
                        return false;
                    }
                    // Get the challenge
                    $challenge = base64_decode(substr($this->last_reply, 4));

                    // Build the response
                    $response = $username.' '.$this->hmac($challenge, $password);

                    // send encoded credentials
                    return $this->sendCommand('Username', base64_encode($response), 235);
                case 'XOAUTH2':
                    // The OAuth instance must be set up prior to requesting auth.
                    if(null === $OAuth)
                    {
                        return false;
                    }
                    $oauth = $OAuth->getOauth64();

                    // Start authentication
                    if(! $this->sendCommand('AUTH', 'AUTH XOAUTH2 '.$oauth, 235))
                    {
                        return false;
                    }
                    break;
                default:
                    $this->setError("Authentication method \"$authtype\" is not supported");

                    return false;
            }

            return true;
        }

        protected function hmac($data, $key)
        {
            if(function_exists('hash_hmac'))
            {
                return hash_hmac('md5', $data, $key);
            }

            // The following borrowed from
            // http://php.net/manual/en/function.mhash.php#27225

            // RFC 2104 HMAC implementation for php.
            // Creates an md5 HMAC.
            // Eliminates the need to install mhash to compute a HMAC
            // by Lance Rushing

            $bytelen = 64; // byte length for md5
            if(strlen($key) > $bytelen)
            {
                $key = pack('H*', md5($key));
            }
            $key = str_pad($key, $bytelen, chr(0x00));
            $ipad = str_pad('', $bytelen, chr(0x36));
            $opad = str_pad('', $bytelen, chr(0x5c));
            $k_ipad = $key ^ $ipad;
            $k_opad = $key ^ $opad;

            return md5($k_opad.pack('H*', md5($k_ipad.$data)));
        }

        public function data($msg_data)
        {
            // This will use the standard timelimit
            if(! $this->sendCommand('DATA', 'DATA', 354))
            {
                return false;
            }

            /* The server is ready to accept data!
             * According to rfc821 we should not send more than 1000 characters on a single line (including the LE)
             * so we will break the data up into lines by \r and/or \n then if needed we will break each of those into
             * smaller lines to fit within the limit.
             * We will also look for lines that start with a '.' and prepend an additional '.'.
             * NOTE: this does not count towards line-length limit.
             */

            // Normalize line breaks before exploding
            $lines = explode("\n", str_replace(["\r\n", "\r"], "\n", $msg_data));

            /* To distinguish between a complete RFC822 message and a plain message body, we check if the first field
             * of the first line (':' separated) does not contain a space then it _should_ be a header, and we will
             * process all lines before a blank line as headers.
             */

            $field = substr($lines[0], 0, strpos($lines[0], ':'));
            $in_headers = false;
            if(! empty($field) && strpos($field, ' ') === false)
            {
                $in_headers = true;
            }

            foreach($lines as $line)
            {
                $lines_out = [];
                if($in_headers && $line === '')
                {
                    $in_headers = false;
                }
                // Break this line up into several smaller lines if it's too long
                // Micro-optimisation: isset($str[$len]) is faster than (strlen($str) > $len),
                while(isset($line[self::MAX_LINE_LENGTH]))
                {
                    // Working backwards, try to find a space within the last MAX_LINE_LENGTH chars of the line to break on
                    // so as to avoid breaking in the middle of a word
                    $pos = strrpos(substr($line, 0, self::MAX_LINE_LENGTH), ' ');
                    // Deliberately matches both false and 0
                    if(! $pos)
                    {
                        // No nice break found, add a hard break
                        $pos = self::MAX_LINE_LENGTH - 1;
                        $lines_out[] = substr($line, 0, $pos);
                        $line = substr($line, $pos);
                    }
                    else
                    {
                        // Break at the found point
                        $lines_out[] = substr($line, 0, $pos);
                        // Move along by the amount we dealt with
                        $line = substr($line, $pos + 1);
                    }
                    // If processing headers add a LWSP-char to the front of new line RFC822 section 3.1.1
                    if($in_headers)
                    {
                        $line = "\t".$line;
                    }
                }
                $lines_out[] = $line;

                // Send the lines to the server
                foreach($lines_out as $line_out)
                {
                    // Dot-stuffing as per RFC5321 section 4.5.2
                    // https://tools.ietf.org/html/rfc5321#section-4.5.2
                    if(! empty($line_out) && $line_out[0] === '.')
                    {
                        $line_out = '.'.$line_out;
                    }
                    $this->client_send($line_out.static::LE, 'DATA');
                }
            }

            // Message data has been sent, complete the command
            // Increase timelimit for end of DATA command
            $savetimelimit = $this->Timelimit;
            $this->Timelimit *= 2;
            $result = $this->sendCommand('DATA END', '.', 250);
            $this->recordLastTransactionID();
            // Restore timelimit
            $this->Timelimit = $savetimelimit;

            return $result;
        }

        protected function recordLastTransactionID()
        {
            $reply = $this->getLastReply();

            if(empty($reply))
            {
                $this->last_smtp_transaction_id = null;
            }
            else
            {
                $this->last_smtp_transaction_id = false;
                foreach($this->smtp_transaction_id_patterns as $smtp_transaction_id_pattern)
                {
                    $matches = [];
                    if(preg_match($smtp_transaction_id_pattern, $reply, $matches))
                    {
                        $this->last_smtp_transaction_id = trim($matches[1]);
                        break;
                    }
                }
            }

            return $this->last_smtp_transaction_id;
        }

        public function getLastReply()
        {
            return $this->last_reply;
        }

        public function hello($host = '')
        {
            // Try extended hello first (RFC 2821)
            if($this->sendHello('EHLO', $host))
            {
                return true;
            }

            // Some servers shut down the SMTP service here (RFC 5321)
            if(substr($this->helo_rply, 0, 3) == '421')
            {
                return false;
            }

            return $this->sendHello('HELO', $host);
        }

        protected function sendHello($hello, $host)
        {
            $noerror = $this->sendCommand($hello, $hello.' '.$host, 250);
            $this->helo_rply = $this->last_reply;
            if($noerror)
            {
                $this->parseHelloFields($hello);
            }
            else
            {
                $this->server_caps = null;
            }

            return $noerror;
        }

        protected function parseHelloFields($type)
        {
            $this->server_caps = [];
            $lines = explode("\n", $this->helo_rply);

            foreach($lines as $n => $s)
            {
                // First 4 chars contain response code followed by - or space
                $s = trim(substr($s, 4));
                if(empty($s))
                {
                    continue;
                }
                $fields = explode(' ', $s);
                if(! empty($fields))
                {
                    if(! $n)
                    {
                        $name = $type;
                        $fields = $fields[0];
                    }
                    else
                    {
                        $name = array_shift($fields);
                        switch($name)
                        {
                            case 'SIZE':
                                $fields = ($fields ? $fields[0] : 0);
                                break;
                            case 'AUTH':
                                if(! is_array($fields))
                                {
                                    $fields = [];
                                }
                                break;
                            default:
                                $fields = true;
                        }
                    }
                    $this->server_caps[$name] = $fields;
                }
            }
        }

        public function mail($from)
        {
            $useVerp = ($this->do_verp ? ' XVERP' : '');

            return $this->sendCommand('MAIL FROM', 'MAIL FROM:<'.$from.'>'.$useVerp, 250);
        }

        public function recipient($address, $dsn = '')
        {
            if(empty($dsn))
            {
                $rcpt = 'RCPT TO:<'.$address.'>';
            }
            else
            {
                $dsn = strtoupper($dsn);
                $notify = [];

                if(strpos($dsn, 'NEVER') !== false)
                {
                    $notify[] = 'NEVER';
                }
                else
                {
                    foreach(['SUCCESS', 'FAILURE', 'DELAY'] as $value)
                    {
                        if(strpos($dsn, $value) !== false)
                        {
                            $notify[] = $value;
                        }
                    }
                }

                $rcpt = 'RCPT TO:<'.$address.'> NOTIFY='.implode(',', $notify);
            }

            return $this->sendCommand('RCPT TO', $rcpt, [250, 251]);
        }

        public function reset()
        {
            return $this->sendCommand('RSET', 'RSET', 250);
        }

        public function sendAndMail($from)
        {
            return $this->sendCommand('SAML', "SAML FROM:$from", 250);
        }

        public function verify($name)
        {
            return $this->sendCommand('VRFY', "VRFY $name", [250, 251]);
        }

        public function noop()
        {
            return $this->sendCommand('NOOP', 'NOOP', 250);
        }

        public function turn()
        {
            $this->setError('The SMTP TURN command is not implemented');
            $this->edebug('SMTP NOTICE: '.$this->error['error'], self::DEBUG_CLIENT);

            return false;
        }

        public function getServerExtList()
        {
            return $this->server_caps;
        }

        public function getServerExt($name)
        {
            if(! $this->server_caps)
            {
                $this->setError('No HELO/EHLO was sent');

                return null;
            }

            if(! array_key_exists($name, $this->server_caps))
            {
                if('HELO' === $name)
                {
                    return $this->server_caps['EHLO'];
                }
                if('EHLO' === $name || array_key_exists('EHLO', $this->server_caps))
                {
                    return false;
                }
                $this->setError('HELO handshake was used; No information about server extensions available');

                return null;
            }

            return $this->server_caps[$name];
        }

        public function setVerp($enabled = false)
        {
            $this->do_verp = $enabled;
        }

        public function getVerp()
        {
            return $this->do_verp;
        }

        public function getDebugOutput()
        {
            return $this->Debugoutput;
        }

        public function setDebugOutput($method = 'echo')
        {
            $this->Debugoutput = $method;
        }

        public function setDebugLevel($level = 0)
        {
            $this->do_debug = $level;
        }

        public function getDebugLevel()
        {
            return $this->do_debug;
        }

        public function getTimeout()
        {
            return $this->Timeout;
        }

        public function setTimeout($timeout = 0)
        {
            $this->Timeout = $timeout;
        }

        public function getLastTransactionID()
        {
            return $this->last_smtp_transaction_id;
        }

        protected function errorHandler($errno, $errmsg, $errfile = '', $errline = 0)
        {
            $notice = 'Connection failed.';
            $this->setError($notice, $errmsg, (string) $errno);
            $this->edebug("$notice Error #$errno: $errmsg [$errfile line $errline]", self::DEBUG_CONNECTION);
        }
    }
