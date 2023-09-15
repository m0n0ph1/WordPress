<?php

    class WP_Filesystem_FTPext extends WP_Filesystem_Base
    {
        public $link;

        public function __construct($opt = '')
        {
            $this->method = 'ftpext';
            $this->errors = new WP_Error();

            // Check if possible to use ftp functions.
            if(! extension_loaded('ftp'))
            {
                $this->errors->add('no_ftp_ext', __('The ftp PHP extension is not available'));

                return;
            }

            // This class uses the timeout on a per-connection basis, others use it on a per-action basis.
            if(! defined('FS_TIMEOUT'))
            {
                define('FS_TIMEOUT', 4 * MINUTE_IN_SECONDS);
            }

            if(empty($opt['port']))
            {
                $this->options['port'] = 21;
            }
            else
            {
                $this->options['port'] = $opt['port'];
            }

            if(empty($opt['hostname']))
            {
                $this->errors->add('empty_hostname', __('FTP hostname is required'));
            }
            else
            {
                $this->options['hostname'] = $opt['hostname'];
            }

            // Check if the options provided are OK.
            if(empty($opt['username']))
            {
                $this->errors->add('empty_username', __('FTP username is required'));
            }
            else
            {
                $this->options['username'] = $opt['username'];
            }

            if(empty($opt['password']))
            {
                $this->errors->add('empty_password', __('FTP password is required'));
            }
            else
            {
                $this->options['password'] = $opt['password'];
            }

            $this->options['ssl'] = false;

            if(isset($opt['connection_type']) && 'ftps' === $opt['connection_type'])
            {
                $this->options['ssl'] = true;
            }
        }

        public function connect()
        {
            if(isset($this->options['ssl']) && $this->options['ssl'] && function_exists('ftp_ssl_connect'))
            {
                $this->link = @ftp_ssl_connect($this->options['hostname'], $this->options['port'], FS_CONNECT_TIMEOUT);
            }
            else
            {
                $this->link = @ftp_connect($this->options['hostname'], $this->options['port'], FS_CONNECT_TIMEOUT);
            }

            if(! $this->link)
            {
                $this->errors->add('connect', sprintf(/* translators: %s: hostname:port */ __('Failed to connect to FTP Server %s'), $this->options['hostname'].':'.$this->options['port']));

                return false;
            }

            if(! @ftp_login($this->link, $this->options['username'], $this->options['password']))
            {
                $this->errors->add('auth', sprintf(/* translators: %s: Username. */ __('Username/Password incorrect for %s'), $this->options['username']));

                return false;
            }

            // Set the connection to use Passive FTP.
            ftp_pasv($this->link, true);

            if(@ftp_get_option($this->link, FTP_TIMEOUT_SEC) < FS_TIMEOUT)
            {
                @ftp_set_option($this->link, FTP_TIMEOUT_SEC, FS_TIMEOUT);
            }

            return true;
        }

        public function get_contents_array($file)
        {
            return explode("\n", $this->get_contents($file));
        }

        public function get_contents($file)
        {
            $tempfile = wp_tempnam($file);
            $temphandle = fopen($tempfile, 'w+');

            if(! $temphandle)
            {
                unlink($tempfile);

                return false;
            }

            if(! ftp_fget($this->link, $temphandle, $file, FTP_BINARY))
            {
                fclose($temphandle);
                unlink($tempfile);

                return false;
            }

            fseek($temphandle, 0); // Skip back to the start of the file being written to.
            $contents = '';

            while(! feof($temphandle))
            {
                $contents .= fread($temphandle, 8 * KB_IN_BYTES);
            }

            fclose($temphandle);
            unlink($tempfile);

            return $contents;
        }

        public function chdir($dir)
        {
            return @ftp_chdir($this->link, $dir);
        }

        public function owner($file)
        {
            $dir = $this->dirlist($file);

            return $dir[$file]['owner'];
        }

        public function dirlist($path = '.', $include_hidden = true, $recursive = false)
        {
            if($this->is_file($path))
            {
                $limit_file = basename($path);
                $path = dirname($path).'/';
            }
            else
            {
                $limit_file = false;
            }

            $pwd = ftp_pwd($this->link);

            if(! @ftp_chdir($this->link, $path))
            { // Can't change to folder = folder doesn't exist.
                return false;
            }

            $list = ftp_rawlist($this->link, '-a', false);

            @ftp_chdir($this->link, $pwd);

            if(empty($list))
            { // Empty array = non-existent folder (real folder will show . at least).
                return false;
            }

            $dirlist = [];

            foreach($list as $k => $v)
            {
                $entry = $this->parselisting($v);

                if(empty($entry))
                {
                    continue;
                }

                if('.' === $entry['name'] || '..' === $entry['name'])
                {
                    continue;
                }

                if(! $include_hidden && '.' === $entry['name'][0])
                {
                    continue;
                }

                if($limit_file && $entry['name'] !== $limit_file)
                {
                    continue;
                }

                $dirlist[$entry['name']] = $entry;
            }

            $path = trailingslashit($path);
            $ret = [];

            foreach((array) $dirlist as $struc)
            {
                if('d' === $struc['type'])
                {
                    if($recursive)
                    {
                        $struc['files'] = $this->dirlist($path.$struc['name'], $include_hidden, $recursive);
                    }
                    else
                    {
                        $struc['files'] = [];
                    }
                }

                $ret[$struc['name']] = $struc;
            }

            return $ret;
        }

        public function is_file($file)
        {
            return $this->exists($file) && ! $this->is_dir($file);
        }

        public function exists($path)
        {
            /*
             * Check for empty path. If ftp_nlist() receives an empty path,
             * it checks the current working directory and may return true.
             *
             * See https://core.trac.wordpress.org/ticket/33058.
             */
            if('' === $path)
            {
                return false;
            }

            $list = ftp_nlist($this->link, $path);

            if(empty($list) && $this->is_dir($path))
            {
                return true; // File is an empty directory.
            }

            return ! empty($list); // Empty list = no file, so invert.
        }

        public function is_dir($path)
        {
            $cwd = $this->cwd();
            $result = @ftp_chdir($this->link, trailingslashit($path));

            if($result && $path === $this->cwd() || $this->cwd() !== $cwd)
            {
                @ftp_chdir($this->link, $cwd);

                return true;
            }

            return false;
        }

        public function cwd()
        {
            $cwd = ftp_pwd($this->link);

            if($cwd)
            {
                $cwd = trailingslashit($cwd);
            }

            return $cwd;
        }

        public function parselisting($line)
        {
            static $is_windows = null;

            if(is_null($is_windows))
            {
                $is_windows = stripos(ftp_systype($this->link), 'win') !== false;
            }

            if($is_windows && preg_match('/([0-9]{2})-([0-9]{2})-([0-9]{2}) +([0-9]{2}):([0-9]{2})(AM|PM) +([0-9]+|<DIR>) +(.+)/', $line, $lucifer))
            {
                $b = [];

                if($lucifer[3] < 70)
                {
                    $lucifer[3] += 2000;
                }
                else
                {
                    $lucifer[3] += 1900; // 4-digit year fix.
                }

                $b['isdir'] = ('<DIR>' === $lucifer[7]);

                if($b['isdir'])
                {
                    $b['type'] = 'd';
                }
                else
                {
                    $b['type'] = 'f';
                }

                $b['size'] = $lucifer[7];
                $b['month'] = $lucifer[1];
                $b['day'] = $lucifer[2];
                $b['year'] = $lucifer[3];
                $b['hour'] = $lucifer[4];
                $b['minute'] = $lucifer[5];
                $b['time'] = mktime($lucifer[4] + (strcasecmp($lucifer[6], 'PM') === 0 ? 12 : 0), $lucifer[5], 0, $lucifer[1], $lucifer[2], $lucifer[3]);
                $b['am/pm'] = $lucifer[6];
                $b['name'] = $lucifer[8];
            }
            elseif(! $is_windows)
            {
                $lucifer = preg_split('/[ ]/', $line, 9, PREG_SPLIT_NO_EMPTY);

                if($lucifer)
                {
                    // echo $line."\n";
                    $lcount = count($lucifer);

                    if($lcount < 8)
                    {
                        return '';
                    }

                    $b = [];
                    $b['isdir'] = 'd' === $lucifer[0][0];
                    $b['islink'] = 'l' === $lucifer[0][0];

                    if($b['isdir'])
                    {
                        $b['type'] = 'd';
                    }
                    elseif($b['islink'])
                    {
                        $b['type'] = 'l';
                    }
                    else
                    {
                        $b['type'] = 'f';
                    }

                    $b['perms'] = $lucifer[0];
                    $b['permsn'] = $this->getnumchmodfromh($b['perms']);
                    $b['number'] = $lucifer[1];
                    $b['owner'] = $lucifer[2];
                    $b['group'] = $lucifer[3];
                    $b['size'] = $lucifer[4];

                    if(8 === $lcount)
                    {
                        sscanf($lucifer[5], '%d-%d-%d', $b['year'], $b['month'], $b['day']);
                        sscanf($lucifer[6], '%d:%d', $b['hour'], $b['minute']);

                        $b['time'] = mktime($b['hour'], $b['minute'], 0, $b['month'], $b['day'], $b['year']);
                        $b['name'] = $lucifer[7];
                    }
                    else
                    {
                        $b['month'] = $lucifer[5];
                        $b['day'] = $lucifer[6];

                        if(preg_match('/([0-9]{2}):([0-9]{2})/', $lucifer[7], $l2))
                        {
                            $b['year'] = gmdate('Y');
                            $b['hour'] = $l2[1];
                            $b['minute'] = $l2[2];
                        }
                        else
                        {
                            $b['year'] = $lucifer[7];
                            $b['hour'] = 0;
                            $b['minute'] = 0;
                        }

                        $b['time'] = strtotime(sprintf('%d %s %d %02d:%02d', $b['day'], $b['month'], $b['year'], $b['hour'], $b['minute']));
                        $b['name'] = $lucifer[8];
                    }
                }
            }

            // Replace symlinks formatted as "source -> target" with just the source name.
            if(isset($b['islink']) && $b['islink'])
            {
                $b['name'] = preg_replace('/(\s*->\s*.*)$/', '', $b['name']);
            }

            return $b;
        }

        public function getchmod($file)
        {
            $dir = $this->dirlist($file);

            return $dir[$file]['permsn'];
        }

        public function group($file)
        {
            $dir = $this->dirlist($file);

            return $dir[$file]['group'];
        }

        public function copy($source, $destination, $overwrite = false, $mode = false)
        {
            if(! $overwrite && $this->exists($destination))
            {
                return false;
            }

            $content = $this->get_contents($source);

            if(false === $content)
            {
                return false;
            }

            return $this->put_contents($destination, $content, $mode);
        }

        public function put_contents($file, $contents, $mode = false)
        {
            $tempfile = wp_tempnam($file);
            $temphandle = fopen($tempfile, 'wb+');

            if(! $temphandle)
            {
                unlink($tempfile);

                return false;
            }

            mbstring_binary_safe_encoding();

            $data_length = strlen($contents);
            $bytes_written = fwrite($temphandle, $contents);

            reset_mbstring_encoding();

            if($data_length !== $bytes_written)
            {
                fclose($temphandle);
                unlink($tempfile);

                return false;
            }

            fseek($temphandle, 0); // Skip back to the start of the file being written to.

            $ret = ftp_fput($this->link, $file, $temphandle, FTP_BINARY);

            fclose($temphandle);
            unlink($tempfile);

            $this->chmod($file, $mode);

            return $ret;
        }

        public function chmod($file, $mode = false, $recursive = false)
        {
            if(! $mode)
            {
                if($this->is_file($file))
                {
                    $mode = FS_CHMOD_FILE;
                }
                elseif($this->is_dir($file))
                {
                    $mode = FS_CHMOD_DIR;
                }
                else
                {
                    return false;
                }
            }

            // chmod any sub-objects if recursive.
            if($recursive && $this->is_dir($file))
            {
                $filelist = $this->dirlist($file);

                foreach((array) $filelist as $filename => $filemeta)
                {
                    $this->chmod($file.'/'.$filename, $mode, $recursive);
                }
            }

            // chmod the file or directory.
            if(! function_exists('ftp_chmod'))
            {
                return (bool) ftp_site($this->link, sprintf('CHMOD %o %s', $mode, $file));
            }

            return (bool) ftp_chmod($this->link, $mode, $file);
        }

        public function move($source, $destination, $overwrite = false)
        {
            return ftp_rename($this->link, $source, $destination);
        }

        public function is_readable($file)
        {
            return true;
        }

        public function is_writable($path)
        {
            return true;
        }

        public function atime($file)
        {
            return false;
        }

        public function mtime($file)
        {
            return ftp_mdtm($this->link, $file);
        }

        public function size($file)
        {
            $size = ftp_size($this->link, $file);

            return ($size > -1) ? $size : false;
        }

        public function touch($file, $time = 0, $atime = 0)
        {
            return false;
        }

        public function mkdir($path, $chmod = false, $chown = false, $chgrp = false)
        {
            $path = untrailingslashit($path);

            if(empty($path))
            {
                return false;
            }

            if(! ftp_mkdir($this->link, $path))
            {
                return false;
            }

            $this->chmod($path, $chmod);

            return true;
        }

        public function rmdir($path, $recursive = false)
        {
            return $this->delete($path, $recursive);
        }

        public function delete($file, $recursive = false, $type = false)
        {
            if(empty($file))
            {
                return false;
            }

            if('f' === $type || $this->is_file($file))
            {
                return ftp_delete($this->link, $file);
            }

            if(! $recursive)
            {
                return ftp_rmdir($this->link, $file);
            }

            $filelist = $this->dirlist(trailingslashit($file));

            if(! empty($filelist))
            {
                foreach($filelist as $delete_file)
                {
                    $this->delete(trailingslashit($file).$delete_file['name'], $recursive, $delete_file['type']);
                }
            }

            return ftp_rmdir($this->link, $file);
        }

        public function __destruct()
        {
            if($this->link)
            {
                ftp_close($this->link);
            }
        }
    }
