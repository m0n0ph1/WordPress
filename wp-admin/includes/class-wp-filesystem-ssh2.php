<?php

    class WP_Filesystem_SSH2 extends WP_Filesystem_Base
    {
        public $link = false;

        public $sftp_link;

        public $keys = false;

        public function __construct($opt = '')
        {
            $this->method = 'ssh2';
            $this->errors = new WP_Error();

            // Check if possible to use ssh2 functions.
            if(! extension_loaded('ssh2'))
            {
                $this->errors->add('no_ssh2_ext', __('The ssh2 PHP extension is not available'));

                return;
            }

            // Set defaults:
            if(empty($opt['port']))
            {
                $this->options['port'] = 22;
            }
            else
            {
                $this->options['port'] = $opt['port'];
            }

            if(empty($opt['hostname']))
            {
                $this->errors->add('empty_hostname', __('SSH2 hostname is required'));
            }
            else
            {
                $this->options['hostname'] = $opt['hostname'];
            }

            // Check if the options provided are OK.
            if(! empty($opt['public_key']) && ! empty($opt['private_key']))
            {
                $this->options['public_key'] = $opt['public_key'];
                $this->options['private_key'] = $opt['private_key'];

                $this->options['hostkey'] = ['hostkey' => 'ssh-rsa,ssh-ed25519'];

                $this->keys = true;
            }
            elseif(empty($opt['username']))
            {
                $this->errors->add('empty_username', __('SSH2 username is required'));
            }

            if(! empty($opt['username']))
            {
                $this->options['username'] = $opt['username'];
            }

            if(empty($opt['password']))
            {
                // Password can be blank if we are using keys.
                if(! $this->keys)
                {
                    $this->errors->add('empty_password', __('SSH2 password is required'));
                }
                else
                {
                    $this->options['password'] = null;
                }
            }
            else
            {
                $this->options['password'] = $opt['password'];
            }
        }

        public function connect()
        {
            if(! $this->keys)
            {
                $this->link = @ssh2_connect($this->options['hostname'], $this->options['port']);
            }
            else
            {
                $this->link = @ssh2_connect($this->options['hostname'], $this->options['port'], $this->options['hostkey']);
            }

            if(! $this->link)
            {
                $this->errors->add('connect', sprintf(/* translators: %s: hostname:port */ __('Failed to connect to SSH2 Server %s'), $this->options['hostname'].':'.$this->options['port']));

                return false;
            }

            if(! $this->keys)
            {
                if(! @ssh2_auth_password($this->link, $this->options['username'], $this->options['password']))
                {
                    $this->errors->add('auth', sprintf(/* translators: %s: Username. */ __('Username/Password incorrect for %s'), $this->options['username']));

                    return false;
                }
            }
            else
            {
                if(! @ssh2_auth_pubkey_file($this->link, $this->options['username'], $this->options['public_key'], $this->options['private_key'], $this->options['password']))
                {
                    $this->errors->add('auth', sprintf(/* translators: %s: Username. */ __('Public and Private keys incorrect for %s'), $this->options['username']));

                    return false;
                }
            }

            $this->sftp_link = ssh2_sftp($this->link);

            if(! $this->sftp_link)
            {
                $this->errors->add('connect', sprintf(/* translators: %s: hostname:port */ __('Failed to initialize a SFTP subsystem session with the SSH2 Server %s'), $this->options['hostname'].':'.$this->options['port']));

                return false;
            }

            return true;
        }

        public function get_contents_array($file)
        {
            return file($this->sftp_path($file));
        }

        public function sftp_path($path)
        {
            if('/' === $path)
            {
                $path = '/./';
            }

            return 'ssh2.sftp://'.$this->sftp_link.'/'.ltrim($path, '/');
        }

        public function cwd()
        {
            $cwd = ssh2_sftp_realpath($this->sftp_link, '.');

            if($cwd)
            {
                $cwd = trailingslashit(trim($cwd));
            }

            return $cwd;
        }

        public function chdir($dir)
        {
            return $this->run_command('cd '.$dir, true);
        }

        public function run_command($command, $returnbool = false)
        {
            if(! $this->link)
            {
                return false;
            }

            $stream = ssh2_exec($this->link, $command);

            if(! $stream)
            {
                $this->errors->add('command', sprintf(/* translators: %s: Command. */ __('Unable to perform command: %s'), $command));
            }
            else
            {
                stream_set_blocking($stream, true);
                stream_set_timeout($stream, FS_TIMEOUT);
                $data = stream_get_contents($stream);
                fclose($stream);

                if($returnbool)
                {
                    return (false === $data) ? false : '' !== trim($data);
                }
                else
                {
                    return $data;
                }
            }

            return false;
        }

        public function getchmod($file)
        {
            return substr(decoct(@fileperms($this->sftp_path($file))), -3);
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

        public function exists($path)
        {
            return file_exists($this->sftp_path($path));
        }

        public function get_contents($file)
        {
            return file_get_contents($this->sftp_path($file));
        }

        public function put_contents($file, $contents, $mode = false)
        {
            $ret = file_put_contents($this->sftp_path($file), $contents);

            if(strlen($contents) !== $ret)
            {
                return false;
            }

            $this->chmod($file, $mode);

            return true;
        }

        public function chmod($file, $mode = false, $recursive = false)
        {
            if(! $this->exists($file))
            {
                return false;
            }

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

            if(! $recursive || ! $this->is_dir($file))
            {
                return $this->run_command(sprintf('chmod %o %s', $mode, escapeshellarg($file)), true);
            }

            return $this->run_command(sprintf('chmod -R %o %s', $mode, escapeshellarg($file)), true);
        }

        public function is_file($file)
        {
            return is_file($this->sftp_path($file));
        }

        public function is_dir($path)
        {
            return is_dir($this->sftp_path($path));
        }

        public function move($source, $destination, $overwrite = false)
        {
            if($this->exists($destination))
            {
                if($overwrite)
                {
                    // We need to remove the destination before we can rename the source.
                    $this->delete($destination, false, 'f');
                }
                else
                {
                    // If we're not overwriting, the rename will fail, so return early.
                    return false;
                }
            }

            return ssh2_sftp_rename($this->sftp_link, $source, $destination);
        }

        public function delete($file, $recursive = false, $type = false)
        {
            if('f' === $type || $this->is_file($file))
            {
                return ssh2_sftp_unlink($this->sftp_link, $file);
            }

            if(! $recursive)
            {
                return ssh2_sftp_rmdir($this->sftp_link, $file);
            }

            $filelist = $this->dirlist($file);

            if(is_array($filelist))
            {
                foreach($filelist as $filename => $fileinfo)
                {
                    $this->delete($file.'/'.$filename, $recursive, $fileinfo['type']);
                }
            }

            return ssh2_sftp_rmdir($this->sftp_link, $file);
        }

        public function dirlist($path, $include_hidden = true, $recursive = false)
        {
            if($this->is_file($path))
            {
                $limit_file = basename($path);
                $path = dirname($path);
            }
            else
            {
                $limit_file = false;
            }

            if(! $this->is_dir($path) || ! $this->is_readable($path))
            {
                return false;
            }

            $ret = [];
            $dir = dir($this->sftp_path($path));

            if(! $dir)
            {
                return false;
            }

            $path = trailingslashit($path);

            while(false !== ($entry = $dir->read()))
            {
                $struc = [];
                $struc['name'] = $entry;

                if('.' === $struc['name'] || '..' === $struc['name'])
                {
                    continue; // Do not care about these folders.
                }

                if(! $include_hidden && '.' === $struc['name'][0])
                {
                    continue;
                }

                if($limit_file && $struc['name'] !== $limit_file)
                {
                    continue;
                }

                $struc['perms'] = $this->gethchmod($path.$entry);
                $struc['permsn'] = $this->getnumchmodfromh($struc['perms']);
                $struc['number'] = false;
                $struc['owner'] = $this->owner($path.$entry);
                $struc['group'] = $this->group($path.$entry);
                $struc['size'] = $this->size($path.$entry);
                $struc['lastmodunix'] = $this->mtime($path.$entry);
                $struc['lastmod'] = gmdate('M j', $struc['lastmodunix']);
                $struc['time'] = gmdate('h:i:s', $struc['lastmodunix']);
                $struc['type'] = $this->is_dir($path.$entry) ? 'd' : 'f';

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

            $dir->close();
            unset($dir);

            return $ret;
        }

        public function is_readable($file)
        {
            return is_readable($this->sftp_path($file));
        }

        public function owner($file)
        {
            $owneruid = @fileowner($this->sftp_path($file));

            if(! $owneruid)
            {
                return false;
            }

            if(! function_exists('posix_getpwuid'))
            {
                return $owneruid;
            }

            $ownerarray = posix_getpwuid($owneruid);

            if(! $ownerarray)
            {
                return false;
            }

            return $ownerarray['name'];
        }

        public function group($file)
        {
            $gid = @filegroup($this->sftp_path($file));

            if(! $gid)
            {
                return false;
            }

            if(! function_exists('posix_getgrgid'))
            {
                return $gid;
            }

            $grouparray = posix_getgrgid($gid);

            if(! $grouparray)
            {
                return false;
            }

            return $grouparray['name'];
        }

        public function size($file)
        {
            return filesize($this->sftp_path($file));
        }

        public function mtime($file)
        {
            return filemtime($this->sftp_path($file));
        }

        public function is_writable($path)
        {
            // PHP will base its writable checks on system_user === file_owner, not ssh_user === file_owner.
            return true;
        }

        public function atime($file)
        {
            return fileatime($this->sftp_path($file));
        }

        public function touch($file, $time = 0, $atime = 0)
        {
            // Not implemented.
        }

        public function mkdir($path, $chmod = false, $chown = false, $chgrp = false)
        {
            $path = untrailingslashit($path);

            if(empty($path))
            {
                return false;
            }

            if(! $chmod)
            {
                $chmod = FS_CHMOD_DIR;
            }

            if(! ssh2_sftp_mkdir($this->sftp_link, $path, $chmod, true))
            {
                return false;
            }

            // Set directory permissions.
            ssh2_sftp_chmod($this->sftp_link, $path, $chmod);

            if($chown)
            {
                $this->chown($path, $chown);
            }

            if($chgrp)
            {
                $this->chgrp($path, $chgrp);
            }

            return true;
        }

        public function chown($file, $owner, $recursive = false)
        {
            if(! $this->exists($file))
            {
                return false;
            }

            if(! $recursive || ! $this->is_dir($file))
            {
                return $this->run_command(sprintf('chown %s %s', escapeshellarg($owner), escapeshellarg($file)), true);
            }

            return $this->run_command(sprintf('chown -R %s %s', escapeshellarg($owner), escapeshellarg($file)), true);
        }

        public function chgrp($file, $group, $recursive = false)
        {
            if(! $this->exists($file))
            {
                return false;
            }

            if(! $recursive || ! $this->is_dir($file))
            {
                return $this->run_command(sprintf('chgrp %s %s', escapeshellarg($group), escapeshellarg($file)), true);
            }

            return $this->run_command(sprintf('chgrp -R %s %s', escapeshellarg($group), escapeshellarg($file)), true);
        }

        public function rmdir($path, $recursive = false)
        {
            return $this->delete($path, $recursive);
        }
    }
