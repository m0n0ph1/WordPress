<?php

    class WP_Filesystem_Direct extends WP_Filesystem_Base
    {
        public function __construct($arg)
        {
            $this->method = 'direct';
            $this->errors = new WP_Error();
        }

        public function get_contents($file)
        {
            return @file_get_contents($file);
        }

        public function get_contents_array($file)
        {
            return @file($file);
        }

        public function put_contents($file, $contents, $mode = false)
        {
            $fp = @fopen($file, 'wb');

            if(! $fp)
            {
                return false;
            }

            mbstring_binary_safe_encoding();

            $data_length = strlen($contents);

            $bytes_written = fwrite($fp, $contents);

            reset_mbstring_encoding();

            fclose($fp);

            if($data_length !== $bytes_written)
            {
                return false;
            }

            $this->chmod($file, $mode);

            return true;
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

            if(! $recursive || ! $this->is_dir($file))
            {
                return chmod($file, $mode);
            }

            // Is a directory, and we want recursive.
            $file = trailingslashit($file);
            $filelist = $this->dirlist($file);

            foreach((array) $filelist as $filename => $filemeta)
            {
                $this->chmod($file.$filename, $mode, $recursive);
            }

            return true;
        }

        public function is_file($file)
        {
            return @is_file($file);
        }

        public function is_dir($path)
        {
            return @is_dir($path);
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

            $dir = dir($path);

            if(! $dir)
            {
                return false;
            }

            $path = trailingslashit($path);
            $ret = [];

            while(false !== ($entry = $dir->read()))
            {
                $struc = [];
                $struc['name'] = $entry;

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
            return @is_readable($file);
        }

        public function owner($file)
        {
            $owneruid = @fileowner($file);

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
            $gid = @filegroup($file);

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
            return @filesize($file);
        }

        public function mtime($file)
        {
            return @filemtime($file);
        }

        public function cwd()
        {
            return getcwd();
        }

        public function chdir($dir)
        {
            return @chdir($dir);
        }

        public function getchmod($file)
        {
            return substr(decoct(@fileperms($file)), -3);
        }

        public function move($source, $destination, $overwrite = false)
        {
            if(! $overwrite && $this->exists($destination))
            {
                return false;
            }

            if($overwrite && $this->exists($destination) && ! $this->delete($destination, true))
            {
                // Can't overwrite if the destination couldn't be deleted.
                return false;
            }

            // Try using rename first. if that fails (for example, source is read only) try copy.
            if(@rename($source, $destination))
            {
                return true;
            }

            // Backward compatibility: Only fall back to `::copy()` for single files.
            if($this->is_file($source) && $this->copy($source, $destination, $overwrite) && $this->exists($destination))
            {
                $this->delete($source);

                return true;
            }
            else
            {
                return false;
            }
        }

        public function exists($path)
        {
            return @file_exists($path);
        }

        public function delete($file, $recursive = false, $type = false)
        {
            if(empty($file))
            {
                // Some filesystems report this as /, which can cause non-expected recursive deletion of all files in the filesystem.
                return false;
            }

            $file = str_replace('\\', '/', $file); // For Win32, occasional problems deleting files otherwise.

            if('f' === $type || $this->is_file($file))
            {
                return @unlink($file);
            }

            if(! $recursive && $this->is_dir($file))
            {
                return @rmdir($file);
            }

            // At this point it's a folder, and we're in recursive mode.
            $file = trailingslashit($file);
            $filelist = $this->dirlist($file, true);

            $retval = true;

            if(is_array($filelist))
            {
                foreach($filelist as $filename => $fileinfo)
                {
                    if(! $this->delete($file.$filename, $recursive, $fileinfo['type']))
                    {
                        $retval = false;
                    }
                }
            }

            if(file_exists($file) && ! @rmdir($file))
            {
                $retval = false;
            }

            return $retval;
        }

        public function copy($source, $destination, $overwrite = false, $mode = false)
        {
            if(! $overwrite && $this->exists($destination))
            {
                return false;
            }

            $rtval = copy($source, $destination);

            if($mode)
            {
                $this->chmod($destination, $mode);
            }

            return $rtval;
        }

        public function is_writable($path)
        {
            return @is_writable($path);
        }

        public function atime($file)
        {
            return @fileatime($file);
        }

        public function touch($file, $time = 0, $atime = 0)
        {
            if(0 === $time)
            {
                $time = time();
            }

            if(0 === $atime)
            {
                $atime = time();
            }

            return touch($file, $time, $atime);
        }

        public function mkdir($path, $chmod = false, $chown = false, $chgrp = false)
        {
            // Safe mode fails with a trailing slash under certain PHP versions.
            $path = untrailingslashit($path);

            if(empty($path))
            {
                return false;
            }

            if(! $chmod)
            {
                $chmod = FS_CHMOD_DIR;
            }

            if(! @mkdir($path))
            {
                return false;
            }

            $this->chmod($path, $chmod);

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

            if(! $recursive)
            {
                return chown($file, $owner);
            }

            if(! $this->is_dir($file))
            {
                return chown($file, $owner);
            }

            // Is a directory, and we want recursive.
            $filelist = $this->dirlist($file);

            foreach($filelist as $filename)
            {
                $this->chown($file.'/'.$filename, $owner, $recursive);
            }

            return true;
        }

        public function chgrp($file, $group, $recursive = false)
        {
            if(! $this->exists($file))
            {
                return false;
            }

            if(! $recursive)
            {
                return chgrp($file, $group);
            }

            if(! $this->is_dir($file))
            {
                return chgrp($file, $group);
            }

            // Is a directory, and we want recursive.
            $file = trailingslashit($file);
            $filelist = $this->dirlist($file);

            foreach($filelist as $filename)
            {
                $this->chgrp($file.$filename, $group, $recursive);
            }

            return true;
        }

        public function rmdir($path, $recursive = false)
        {
            return $this->delete($path, $recursive);
        }
    }
