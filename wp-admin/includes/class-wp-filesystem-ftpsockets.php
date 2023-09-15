<?php

    class WP_Filesystem_ftpsockets extends WP_Filesystem_Base
    {
        public $ftp;

        public function __construct($opt = '')
        {
            $this->method = 'ftpsockets';
            $this->errors = new WP_Error();

            // Check if possible to use ftp functions.
            if(! require_once ABSPATH.'wp-admin/includes/class-ftp.php')
            {
                return;
            }

            $this->ftp = new ftp();

            if(empty($opt['port']))
            {
                $this->options['port'] = 21;
            }
            else
            {
                $this->options['port'] = (int) $opt['port'];
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
        }

        public function connect()
        {
            if(! $this->ftp)
            {
                return false;
            }

            $this->ftp->setTimeout(FS_CONNECT_TIMEOUT);

            if(! $this->ftp->SetServer($this->options['hostname'], $this->options['port']))
            {
                $this->errors->add('connect', sprintf(/* translators: %s: hostname:port */ __('Failed to connect to FTP Server %s'), $this->options['hostname'].':'.$this->options['port']));

                return false;
            }

            if(! $this->ftp->connect())
            {
                $this->errors->add('connect', sprintf(/* translators: %s: hostname:port */ __('Failed to connect to FTP Server %s'), $this->options['hostname'].':'.$this->options['port']));

                return false;
            }

            if(! $this->ftp->login($this->options['username'], $this->options['password']))
            {
                $this->errors->add('auth', sprintf(/* translators: %s: Username. */ __('Username/Password incorrect for %s'), $this->options['username']));

                return false;
            }

            $this->ftp->SetType(FTP_BINARY);
            $this->ftp->Passive(true);
            $this->ftp->setTimeout(FS_TIMEOUT);

            return true;
        }

        public function get_contents_array($file)
        {
            return explode("\n", $this->get_contents($file));
        }

        public function get_contents($file)
        {
            if(! $this->exists($file))
            {
                return false;
            }

            $tempfile = wp_tempnam($file);
            $temphandle = fopen($tempfile, 'w+');

            if(! $temphandle)
            {
                unlink($tempfile);

                return false;
            }

            mbstring_binary_safe_encoding();

            if(! $this->ftp->fget($temphandle, $file))
            {
                fclose($temphandle);
                unlink($tempfile);

                reset_mbstring_encoding();

                return ''; // Blank document. File does exist, it's just blank.
            }

            reset_mbstring_encoding();

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

        public function exists($path)
        {
            /*
             * Check for empty path. If ftp::nlist() receives an empty path,
             * it checks the current working directory and may return true.
             *
             * See https://core.trac.wordpress.org/ticket/33058.
             */
            if('' === $path)
            {
                return false;
            }

            $list = $this->ftp->nlist($path);

            if(empty($list) && $this->is_dir($path))
            {
                return true; // File is an empty directory.
            }

            return ! empty($list); // Empty list = no file, so invert.
            // Return $this->ftp->is_exists($file); has issues with ABOR+426 responses on the ncFTPd server.
        }

        public function is_dir($path)
        {
            $cwd = $this->cwd();

            if($this->chdir($path))
            {
                $this->chdir($cwd);

                return true;
            }

            return false;
        }

        public function cwd()
        {
            $cwd = $this->ftp->pwd();

            if($cwd)
            {
                $cwd = trailingslashit($cwd);
            }

            return $cwd;
        }

        public function chdir($dir)
        {
            return $this->ftp->chdir($dir);
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

            mbstring_binary_safe_encoding();

            $list = $this->ftp->dirlist($path);

            if(empty($list) && ! $this->exists($path))
            {
                reset_mbstring_encoding();

                return false;
            }

            $path = trailingslashit($path);
            $ret = [];

            foreach($list as $struc)
            {
                if('.' === $struc['name'] || '..' === $struc['name'])
                {
                    continue;
                }

                if(! $include_hidden && '.' === $struc['name'][0])
                {
                    continue;
                }

                if($limit_file && $struc['name'] !== $limit_file)
                {
                    continue;
                }

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

                // Replace symlinks formatted as "source -> target" with just the source name.
                if($struc['islink'])
                {
                    $struc['name'] = preg_replace('/(\s*->\s*.*)$/', '', $struc['name']);
                }

                // Add the octal representation of the file permissions.
                $struc['permsn'] = $this->getnumchmodfromh($struc['perms']);

                $ret[$struc['name']] = $struc;
            }

            reset_mbstring_encoding();

            return $ret;
        }

        public function is_file($file)
        {
            if($this->is_dir($file))
            {
                return false;
            }

            if($this->exists($file))
            {
                return true;
            }

            return false;
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
            $temphandle = @fopen($tempfile, 'w+');

            if(! $temphandle)
            {
                unlink($tempfile);

                return false;
            }

            // The FTP class uses string functions internally during file download/upload.
            mbstring_binary_safe_encoding();

            $bytes_written = fwrite($temphandle, $contents);

            if(false === $bytes_written || strlen($contents) !== $bytes_written)
            {
                fclose($temphandle);
                unlink($tempfile);

                reset_mbstring_encoding();

                return false;
            }

            fseek($temphandle, 0); // Skip back to the start of the file being written to.

            $ret = $this->ftp->fput($file, $temphandle);

            reset_mbstring_encoding();

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
            return $this->ftp->chmod($file, $mode);
        }

        public function move($source, $destination, $overwrite = false)
        {
            return $this->ftp->rename($source, $destination);
        }

        public function delete($file, $recursive = false, $type = false)
        {
            if(empty($file))
            {
                return false;
            }

            if('f' === $type || $this->is_file($file))
            {
                return $this->ftp->delete($file);
            }

            if(! $recursive)
            {
                return $this->ftp->rmdir($file);
            }

            return $this->ftp->mdel($file);
        }

        public function rmdir($path, $recursive = false)
        {
            return $this->delete($path, $recursive);
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
            return $this->ftp->mdtm($file);
        }

        public function size($file)
        {
            return $this->ftp->filesize($file);
        }

        public function touch($file, $time = 0, $atime = 0)
        {
            return false;
        }

        public function mkdir($path, $chmod = false, $chown = false, $chgrp = false)
        {
            $path = untrailingslashit($path);

            if(empty($path) || ! $this->ftp->mkdir($path))
            {
                return false;
            }

            if(! $chmod)
            {
                $chmod = FS_CHMOD_DIR;
            }

            $this->chmod($path, $chmod);

            return true;
        }

        public function __destruct()
        {
            $this->ftp->quit();
        }
    }
